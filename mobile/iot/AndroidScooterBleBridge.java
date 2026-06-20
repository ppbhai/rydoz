package com.example.taurus;

import android.annotation.SuppressLint;
import android.bluetooth.BluetoothAdapter;
import android.bluetooth.BluetoothDevice;
import android.bluetooth.BluetoothGatt;
import android.bluetooth.BluetoothGattCallback;
import android.bluetooth.BluetoothGattCharacteristic;
import android.bluetooth.BluetoothGattDescriptor;
import android.bluetooth.BluetoothProfile;
import android.bluetooth.le.ScanCallback;
import android.bluetooth.le.ScanResult;
import android.bluetooth.le.ScanSettings;
import android.content.Context;
import android.os.Build;
import android.os.Handler;
import android.os.Looper;
import android.os.ParcelUuid;
import android.util.SparseArray;
import android.webkit.JavascriptInterface;
import android.webkit.WebView;

import org.json.JSONException;
import org.json.JSONObject;

import java.nio.charset.StandardCharsets;
import java.util.Arrays;
import java.util.List;
import java.util.UUID;

public class AndroidScooterBleBridge {
    private static final UUID SERVICE_UUID = UUID.fromString("7b6a1000-2f6d-4e7f-9b2e-30a0bbd80101");
    private static final UUID COMMAND_UUID = UUID.fromString("7b6a1001-2f6d-4e7f-9b2e-30a0bbd80101");
    private static final UUID TELEMETRY_UUID = UUID.fromString("7b6a1002-2f6d-4e7f-9b2e-30a0bbd80101");
    private static final UUID CCCD_UUID = UUID.fromString("00002902-0000-1000-8000-00805f9b34fb");
    private static final int NEARBY_MANUFACTURER_ID = 0xFFFF;
    private static final int NEARBY_PROTOCOL_VERSION = 1;

    private final Context context;
    private final WebView webView;
    private final BluetoothAdapter bluetoothAdapter;

    private BluetoothGatt gatt;
    private BluetoothGattCharacteristic commandCharacteristic;
    private BluetoothGattCharacteristic telemetryCharacteristic;
    private String pendingScooterId = "";
    private String pendingCommand = "";
    private String lastWrittenCommand = "";
    private String lastTelemetryJson = "";
    private ScanCallback activeScanCallback;
    private ScanCallback nearbyScanCallback;
    private boolean nearbyScanRequested = false;
    private boolean connecting = false;
    private int nearbyPacketCount = 0;
    private int nearbyScooterCount = 0;
    private long lastNearbyDebugMs = 0;
    private final Handler mainHandler = new Handler(Looper.getMainLooper());

    public AndroidScooterBleBridge(Context context, WebView webView, BluetoothAdapter bluetoothAdapter) {
        this.context = context;
        this.webView = webView;
        this.bluetoothAdapter = bluetoothAdapter;
    }

    @JavascriptInterface
    public String connect(String json) {
        try {
            JSONObject payload = new JSONObject(json);
            String scooterId = payload.optString("scooterId").trim();
            String mac = payload.optString("mac").trim();

            if (scooterId.equals(pendingScooterId) && commandCharacteristic != null && gatt != null) {
                emitStatus("Already connected to " + scooterId);
                emitConnected(scooterId);
                return "{\"ok\":true,\"alreadyConnected\":true}";
            }

            if (scooterId.equals(pendingScooterId) && connecting) {
                emitStatus("Bluetooth connection already in progress.");
                return "{\"ok\":true,\"connecting\":true}";
            }

            pendingScooterId = scooterId;

            if (!mac.isEmpty()) {
                connectByMac(mac);
                return "{\"ok\":true,\"mac\":true}";
            }

            scanForScooter(pendingScooterId);
            return "{\"ok\":true}";
        } catch (JSONException error) {
            emitStatus("Invalid Bluetooth request.");
            return "{\"ok\":false,\"error\":\"invalid_json\"}";
        }
    }

    @JavascriptInterface
    public String sendCommand(String json) {
        try {
            JSONObject payload = new JSONObject(json);
            String command = payload.optString("command").trim();

            if (commandCharacteristic == null || gatt == null) {
                pendingCommand = command;
                emitStatus("Command queued until Bluetooth connects.");
                return "{\"ok\":true,\"queued\":true}";
            }

            writeCommand(command);
            return "{\"ok\":true}";
        } catch (JSONException error) {
            return "{\"ok\":false,\"error\":\"invalid_json\"}";
        }
    }

    @JavascriptInterface
    @SuppressLint("MissingPermission")
    public String readTelemetry(String json) {
        if (gatt == null || telemetryCharacteristic == null) {
            return "{\"ok\":false,\"error\":\"not_connected\"}";
        }

        boolean started = gatt.readCharacteristic(telemetryCharacteristic);

        if (!started) {
            emitStatus("Telemetry read could not start.");
        }

        return "{\"ok\":" + (started ? "true" : "false") + ",\"telemetry\":" + (lastTelemetryJson.isEmpty() ? "null" : lastTelemetryJson) + "}";
    }

    @JavascriptInterface
    public String getLastTelemetry() {
        return lastTelemetryJson.isEmpty() ? "{}" : lastTelemetryJson;
    }

    @JavascriptInterface
    @SuppressLint("MissingPermission")
    public String disconnect(String json) {
        if (gatt != null) {
            gatt.disconnect();
            gatt.close();
            gatt = null;
        }

        commandCharacteristic = null;
        telemetryCharacteristic = null;
        connecting = false;
        emitStatus("Bluetooth disconnected after command.");
        resumeNearbyScan();

        return "{\"ok\":true}";
    }

    @JavascriptInterface
    @SuppressLint("MissingPermission")
    public String startNearbyScan() {
        nearbyScanRequested = true;

        if (bluetoothAdapter == null || bluetoothAdapter.getBluetoothLeScanner() == null) {
            emitNearbyScanStatus("Bluetooth scanner unavailable.");
            return "{\"ok\":false,\"error\":\"scanner_unavailable\"}";
        }

        if (nearbyScanCallback != null) {
            emitNearbyScanStatus(
                    "BLE scanner active: " + nearbyPacketCount
                            + " packets, " + nearbyScooterCount + " scooters."
            );
            return "{\"ok\":true,\"alreadyScanning\":true}";
        }

        ScanSettings settings = new ScanSettings.Builder()
                .setScanMode(ScanSettings.SCAN_MODE_BALANCED)
                .build();

        nearbyScanCallback = new ScanCallback() {
            @Override
            public void onScanResult(int callbackType, ScanResult result) {
                handleNearbyResult(result);
            }

            @Override
            public void onBatchScanResults(List<ScanResult> results) {
                for (ScanResult result : results) {
                    handleNearbyResult(result);
                }
            }

            @Override
            public void onScanFailed(int errorCode) {
                nearbyScanCallback = null;
                emitNearbyScanStatus("Nearby scooter scan failed: " + errorCode);
            }
        };

        nearbyPacketCount = 0;
        nearbyScooterCount = 0;
        lastNearbyDebugMs = 0;
        bluetoothAdapter.getBluetoothLeScanner().startScan(null, settings, nearbyScanCallback);
        emitNearbyScanStatus("BLE scanner started. Waiting for advertisements...");
        return "{\"ok\":true}";
    }

    @SuppressLint("MissingPermission")
    private void pauseNearbyScan() {
        if (nearbyScanCallback == null || bluetoothAdapter == null
                || bluetoothAdapter.getBluetoothLeScanner() == null) {
            return;
        }

        bluetoothAdapter.getBluetoothLeScanner().stopScan(nearbyScanCallback);
        nearbyScanCallback = null;
    }

    private void resumeNearbyScan() {
        if (nearbyScanRequested) {
            startNearbyScan();
        }
    }

    @SuppressLint("MissingPermission")
    private void connectByMac(String mac) {
        if (bluetoothAdapter == null) {
            emitStatus("Bluetooth adapter unavailable.");
            return;
        }

        try {
            pauseNearbyScan();

            if (gatt != null) {
                gatt.disconnect();
                gatt.close();
                gatt = null;
                commandCharacteristic = null;
            }

            connecting = true;
            BluetoothDevice device = bluetoothAdapter.getRemoteDevice(mac);
            emitStatus("Connecting by MAC " + mac);
            connectGatt(device);
            scheduleConnectTimeout();
        } catch (IllegalArgumentException error) {
            connecting = false;
            emitStatus("Invalid scooter MAC: " + mac);
        }
    }

    @SuppressLint("MissingPermission")
    private void scanForScooter(String scooterId) {
        if (bluetoothAdapter == null || bluetoothAdapter.getBluetoothLeScanner() == null) {
            emitStatus("Bluetooth scanner unavailable.");
            return;
        }

        if (scooterId == null || scooterId.trim().isEmpty()) {
            emitStatus("Scan scooter IoT QR first.");
            return;
        }

        pauseNearbyScan();

        if (gatt != null) {
            gatt.disconnect();
            gatt.close();
            gatt = null;
            commandCharacteristic = null;
        }

        String deviceName = "RYDOZ-" + scooterId;

        ScanSettings settings = new ScanSettings.Builder()
                .setScanMode(ScanSettings.SCAN_MODE_LOW_LATENCY)
                .build();

        emitStatus("Scanning for " + deviceName);
        connecting = true;

        activeScanCallback = new ScanCallback() {
            @Override
            public void onScanResult(int callbackType, ScanResult result) {
                handleScanResult(result, deviceName);
            }

            @Override
            public void onBatchScanResults(List<ScanResult> results) {
                for (ScanResult result : results) {
                    handleScanResult(result, deviceName);
                }
            }

            @Override
            public void onScanFailed(int errorCode) {
                connecting = false;
                emitStatus("BLE scan failed: " + errorCode);
                resumeNearbyScan();
            }
        };

        bluetoothAdapter.getBluetoothLeScanner().startScan(null, settings, activeScanCallback);

        mainHandler.postDelayed(() -> {
            if (activeScanCallback != null && commandCharacteristic == null) {
                bluetoothAdapter.getBluetoothLeScanner().stopScan(activeScanCallback);
                activeScanCallback = null;
                connecting = false;
                emitStatus("Scooter not found. Keep ESP32 on and close nRF Connect.");
                resumeNearbyScan();
            }
        }, 12000);
    }

    @SuppressLint("MissingPermission")
    private void handleScanResult(ScanResult result, String expectedName) {
        if (activeScanCallback == null || result == null) {
            return;
        }

        String deviceName = result.getDevice().getName();
        boolean hasScooterService = false;

        if ((deviceName == null || deviceName.isEmpty()) && result.getScanRecord() != null) {
            deviceName = result.getScanRecord().getDeviceName();
        }

        if (result.getScanRecord() != null && result.getScanRecord().getServiceUuids() != null) {
            hasScooterService = result.getScanRecord().getServiceUuids().contains(new ParcelUuid(SERVICE_UUID));
        }

        if ((deviceName == null || !deviceName.equals(expectedName)) && !hasScooterService) {
            return;
        }

        bluetoothAdapter.getBluetoothLeScanner().stopScan(activeScanCallback);
        activeScanCallback = null;
        emitStatus("Connecting to " + (deviceName != null ? deviceName : "scooter service"));
        connectGatt(result.getDevice());
        scheduleConnectTimeout();
    }

    private void handleNearbyResult(ScanResult result) {
        if (result == null || result.getScanRecord() == null) {
            return;
        }

        nearbyPacketCount++;
        SparseArray<byte[]> manufacturerData = result.getScanRecord().getManufacturerSpecificData();
        byte[] payload = manufacturerData == null
                ? null
                : manufacturerData.get(NEARBY_MANUFACTURER_ID);

        if (payload == null || payload.length < 3 || (payload[0] & 0xFF) != NEARBY_PROTOCOL_VERSION) {
            long now = System.currentTimeMillis();

            if (now - lastNearbyDebugMs >= 3000) {
                lastNearbyDebugMs = now;
                emitNearbyScanStatus(
                        "BLE scanner active: " + nearbyPacketCount
                                + " packets seen, no RYDOZ battery packet."
                );
            }
            return;
        }

        int battery = payload[1] & 0xFF;
        String scooterId = new String(
                Arrays.copyOfRange(payload, 2, payload.length),
                StandardCharsets.UTF_8
        ).trim();

        if (scooterId.isEmpty() || battery > 100) {
            emitNearbyScanStatus("RYDOZ packet found but its battery data is invalid.");
            return;
        }

        nearbyScooterCount++;
        emitNearbyScanStatus("Found " + scooterId + " at " + battery + "%.");
        emitNearbyScooter(scooterId, battery, result.getRssi(), result.getDevice().getAddress());
    }

    @SuppressLint("MissingPermission")
    private void connectGatt(BluetoothDevice device) {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
            gatt = device.connectGatt(context, false, gattCallback, BluetoothDevice.TRANSPORT_LE);
        } else {
            gatt = device.connectGatt(context, false, gattCallback);
        }
    }

    private void scheduleConnectTimeout() {
        mainHandler.postDelayed(() -> {
            if (connecting && commandCharacteristic == null) {
                connecting = false;
                if (gatt != null) {
                    gatt.disconnect();
                    gatt.close();
                    gatt = null;
                }
                emitStatus("Bluetooth connect timeout. Restart ESP32 and try again.");
                resumeNearbyScan();
            }
        }, 12000);
    }

    private final BluetoothGattCallback gattCallback = new BluetoothGattCallback() {
        @SuppressLint("MissingPermission")
        @Override
        public void onConnectionStateChange(BluetoothGatt bluetoothGatt, int status, int newState) {
            if (newState == BluetoothProfile.STATE_CONNECTED) {
                connecting = true;
                bluetoothGatt.discoverServices();
            } else if (newState == BluetoothProfile.STATE_DISCONNECTED) {
                commandCharacteristic = null;
                connecting = false;
                if (status != BluetoothGatt.GATT_SUCCESS) {
                    bluetoothGatt.close();
                    if (gatt == bluetoothGatt) {
                        gatt = null;
                    }
                }
                resumeNearbyScan();
            }
        }

        @SuppressLint("MissingPermission")
        @Override
        public void onServicesDiscovered(BluetoothGatt bluetoothGatt, int status) {
            if (status != BluetoothGatt.GATT_SUCCESS) {
                connecting = false;
                emitStatus("Service discovery failed: " + status);
                return;
            }

            if (bluetoothGatt.getService(SERVICE_UUID) == null) {
                connecting = false;
                emitStatus("Scooter BLE service not found.");
                return;
            }

            commandCharacteristic = bluetoothGatt.getService(SERVICE_UUID).getCharacteristic(COMMAND_UUID);
            telemetryCharacteristic = bluetoothGatt.getService(SERVICE_UUID).getCharacteristic(TELEMETRY_UUID);

            if (telemetryCharacteristic != null) {
                bluetoothGatt.setCharacteristicNotification(telemetryCharacteristic, true);
                BluetoothGattDescriptor descriptor = telemetryCharacteristic.getDescriptor(CCCD_UUID);

                if (descriptor != null) {
                    descriptor.setValue(BluetoothGattDescriptor.ENABLE_NOTIFICATION_VALUE);
                    bluetoothGatt.writeDescriptor(descriptor);
                }
            }

            connecting = false;
            emitConnected(pendingScooterId);

            if (pendingCommand != null && !pendingCommand.isEmpty()) {
                writeCommand(pendingCommand);
                pendingCommand = "";
            }
        }

        @Override
        public void onCharacteristicChanged(BluetoothGatt bluetoothGatt, BluetoothGattCharacteristic characteristic) {
            if (TELEMETRY_UUID.equals(characteristic.getUuid())) {
                String telemetry = new String(characteristic.getValue(), StandardCharsets.UTF_8);
                handleTelemetry(telemetry);
            }
        }

        @Override
        public void onCharacteristicRead(BluetoothGatt bluetoothGatt, BluetoothGattCharacteristic characteristic, int status) {
            if (TELEMETRY_UUID.equals(characteristic.getUuid()) && status == BluetoothGatt.GATT_SUCCESS) {
                String telemetry = new String(characteristic.getValue(), StandardCharsets.UTF_8);
                handleTelemetry(telemetry);
            }
        }

        @Override
        public void onCharacteristicWrite(BluetoothGatt bluetoothGatt, BluetoothGattCharacteristic characteristic, int status) {
            if (COMMAND_UUID.equals(characteristic.getUuid())) {
                if (status != BluetoothGatt.GATT_SUCCESS) {
                    emitStatus("Command write failed: " + status);
                }
            }
        }
    };

    private void emitStatus(String message) {
        emitJs("window.dispatchEvent(new CustomEvent('scooter:ble-status',{detail:{message:" + JSONObject.quote(message) + "}}))");
    }

    @SuppressLint("MissingPermission")
    private void writeCommand(String command) {
        if (commandCharacteristic == null || gatt == null) {
            pendingCommand = command;
            return;
        }

        commandCharacteristic.setWriteType(BluetoothGattCharacteristic.WRITE_TYPE_DEFAULT);
        commandCharacteristic.setValue((command + "\n").getBytes(StandardCharsets.UTF_8));
        lastWrittenCommand = command;

        boolean started = gatt.writeCharacteristic(commandCharacteristic);

        if (!started) {
            emitStatus("Command write could not start: " + command);
        }
    }

    private void emitConnected(String scooterId) {
        emitJs("window.dispatchEvent(new CustomEvent('scooter:ble-connected',{detail:{scooterId:" + JSONObject.quote(scooterId) + "}}))");
    }

    private void handleTelemetry(String json) {
        lastTelemetryJson = json;
        emitTelemetry(json);
    }

    private void emitTelemetry(String json) {
        emitJs("window.dispatchEvent(new CustomEvent('scooter:telemetry',{detail:" + json + "}))");
    }

    private void emitNearbyScooter(String scooterId, int battery, int rssi, String mac) {
        emitJs(
                "window.dispatchEvent(new CustomEvent('scooter:nearby-telemetry',{detail:{"
                        + "scooterId:" + JSONObject.quote(scooterId)
                        + ",battery:" + battery
                        + ",rssi:" + rssi
                        + ",mac:" + JSONObject.quote(mac)
                        + "}}))"
        );
    }

    private void emitNearbyScanStatus(String message) {
        emitJs(
                "window.dispatchEvent(new CustomEvent('scooter:nearby-scan-status',{detail:{message:"
                        + JSONObject.quote(message)
                        + "}}))"
        );
    }

    private void emitJs(String script) {
        webView.post(() -> webView.evaluateJavascript(script, null));
    }
}
