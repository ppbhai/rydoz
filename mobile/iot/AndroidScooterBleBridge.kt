package com.rydoz.app.iot

import android.Manifest
import android.annotation.SuppressLint
import android.bluetooth.BluetoothAdapter
import android.bluetooth.BluetoothGatt
import android.bluetooth.BluetoothGattCallback
import android.bluetooth.BluetoothGattCharacteristic
import android.bluetooth.BluetoothGattDescriptor
import android.bluetooth.BluetoothProfile
import android.bluetooth.le.ScanCallback
import android.bluetooth.le.ScanFilter
import android.bluetooth.le.ScanResult
import android.bluetooth.le.ScanSettings
import android.content.Context
import android.os.Build
import android.os.ParcelUuid
import android.webkit.JavascriptInterface
import android.webkit.WebView
import org.json.JSONObject
import java.util.UUID

class AndroidScooterBleBridge(
    private val context: Context,
    private val webView: WebView,
    private val bluetoothAdapter: BluetoothAdapter
) {
    private val serviceUuid = UUID.fromString("7b6a1000-2f6d-4e7f-9b2e-30a0bbd80101")
    private val commandUuid = UUID.fromString("7b6a1001-2f6d-4e7f-9b2e-30a0bbd80101")
    private val telemetryUuid = UUID.fromString("7b6a1002-2f6d-4e7f-9b2e-30a0bbd80101")
    private val cccdUuid = UUID.fromString("00002902-0000-1000-8000-00805f9b34fb")

    private var gatt: BluetoothGatt? = null
    private var commandCharacteristic: BluetoothGattCharacteristic? = null
    private var telemetryCharacteristic: BluetoothGattCharacteristic? = null
    private var latestTelemetryJson: String? = null
    private var pendingScooterId: String = ""
    private var nearbyScanCallback: ScanCallback? = null

    @JavascriptInterface
    fun connect(json: String): String {
        val payload = JSONObject(json)
        pendingScooterId = payload.optString("scooterId").trim()
        disconnectGatt()
        scanForScooter(pendingScooterId)
        return """{"ok":true}"""
    }

    @JavascriptInterface
    fun sendCommand(json: String): String {
        val command = JSONObject(json).optString("command").trim()
        val characteristic = commandCharacteristic ?: return """{"ok":false,"error":"not_connected"}"""
        characteristic.value = "$command\n".toByteArray(Charsets.UTF_8)
        gatt?.writeCharacteristic(characteristic)
        return """{"ok":true}"""
    }

    @JavascriptInterface
    fun readTelemetry(json: String = "{}"): String {
        telemetryCharacteristic?.let { characteristic ->
            gatt?.readCharacteristic(characteristic)
        }

        val telemetry = latestTelemetryJson
        return if (telemetry.isNullOrBlank()) {
            """{"ok":true,"telemetry":null}"""
        } else {
            """{"ok":true,"telemetry":$telemetry}"""
        }
    }

    @JavascriptInterface
    fun getLastTelemetry(): String {
        return latestTelemetryJson ?: """{"raw":null}"""
    }

    @JavascriptInterface
    fun disconnect(json: String = "{}"): String {
        disconnectGatt()
        emitStatus("Bluetooth disconnected")
        return """{"ok":true}"""
    }

    @JavascriptInterface
    fun startNearbyScan(json: String = "{}"): String {
        scanNearbyScooters()
        return """{"ok":true}"""
    }

    @SuppressLint("MissingPermission")
    private fun scanForScooter(scooterId: String) {
        val scanner = bluetoothAdapter.bluetoothLeScanner ?: return emitStatus("Bluetooth scanner unavailable")
        val filters = listOf(
            ScanFilter.Builder()
                .setDeviceName("RYDOZ-$scooterId")
                .setServiceUuid(ParcelUuid(serviceUuid))
                .build()
        )
        val settings = ScanSettings.Builder()
            .setScanMode(ScanSettings.SCAN_MODE_LOW_LATENCY)
            .build()

        emitStatus("Scanning for RYDOZ-$scooterId")
        scanner.startScan(filters, settings, object : ScanCallback() {
            override fun onScanResult(callbackType: Int, result: ScanResult) {
                scanner.stopScan(this)
                emitStatus("Connecting to ${result.device.name ?: scooterId}")
                gatt = result.device.connectGatt(context, false, gattCallback)
            }

            override fun onScanFailed(errorCode: Int) {
                emitStatus("BLE scan failed: $errorCode")
            }
        })
    }

    private val gattCallback = object : BluetoothGattCallback() {
        @SuppressLint("MissingPermission")
        override fun onConnectionStateChange(gatt: BluetoothGatt, status: Int, newState: Int) {
            if (newState == BluetoothProfile.STATE_CONNECTED) {
                gatt.discoverServices()
            } else if (newState == BluetoothProfile.STATE_DISCONNECTED) {
                commandCharacteristic = null
                telemetryCharacteristic = null
                emitStatus("Bluetooth disconnected")
            }
        }

        @SuppressLint("MissingPermission")
        override fun onServicesDiscovered(gatt: BluetoothGatt, status: Int) {
            val service = gatt.getService(serviceUuid) ?: return emitStatus("Scooter service not found")
            commandCharacteristic = service.getCharacteristic(commandUuid)
            val telemetry = service.getCharacteristic(telemetryUuid)
            telemetryCharacteristic = telemetry

            if (telemetry != null) {
                gatt.setCharacteristicNotification(telemetry, true)
                telemetry.getDescriptor(cccdUuid)?.let { descriptor ->
                    descriptor.value = BluetoothGattDescriptor.ENABLE_NOTIFICATION_VALUE
                    gatt.writeDescriptor(descriptor)
                }
            }

            emitConnected(pendingScooterId)
            telemetry?.let { gatt.readCharacteristic(it) }
        }

        override fun onCharacteristicRead(
            gatt: BluetoothGatt,
            characteristic: BluetoothGattCharacteristic,
            status: Int
        ) {
            if (characteristic.uuid == telemetryUuid && status == BluetoothGatt.GATT_SUCCESS) {
                emitTelemetry(characteristic.value.toString(Charsets.UTF_8))
            }
        }

        override fun onCharacteristicChanged(gatt: BluetoothGatt, characteristic: BluetoothGattCharacteristic) {
            if (characteristic.uuid == telemetryUuid) {
                emitTelemetry(characteristic.value.toString(Charsets.UTF_8))
            }
        }
    }

    @SuppressLint("MissingPermission")
    private fun scanNearbyScooters() {
        val scanner = bluetoothAdapter.bluetoothLeScanner ?: return emitNearbyScanStatus("Bluetooth scanner unavailable")
        nearbyScanCallback?.let { scanner.stopScan(it) }

        val filters = listOf(
            ScanFilter.Builder()
                .setServiceUuid(ParcelUuid(serviceUuid))
                .build()
        )
        val settings = ScanSettings.Builder()
            .setScanMode(ScanSettings.SCAN_MODE_LOW_LATENCY)
            .build()

        nearbyScanCallback = object : ScanCallback() {
            override fun onScanResult(callbackType: Int, result: ScanResult) {
                emitNearbyTelemetry(result)
            }

            override fun onBatchScanResults(results: MutableList<ScanResult>) {
                results.forEach { emitNearbyTelemetry(it) }
            }

            override fun onScanFailed(errorCode: Int) {
                emitNearbyScanStatus("Nearby scan failed: $errorCode")
            }
        }

        emitNearbyScanStatus("Searching for powered scooters...")
        scanner.startScan(filters, settings, nearbyScanCallback)
    }

    @SuppressLint("MissingPermission")
    private fun emitNearbyTelemetry(result: ScanResult) {
        val deviceName = result.device.name ?: return
        if (!deviceName.startsWith("RYDOZ-")) {
            return
        }

        val scooterId = deviceName.removePrefix("RYDOZ-")
        val telemetry = parseTelemetry(result.scanRecord?.serviceData?.values?.firstOrNull())
        val battery = telemetry?.optDouble("battery", Double.NaN)

        if (battery == null || battery.isNaN()) {
            return
        }

        val json = JSONObject()
            .put("scooterId", scooterId)
            .put("battery", battery.toInt())
            .put("rssi", result.rssi)
            .put("mac", result.device.address)

        emitJs("window.dispatchEvent(new CustomEvent('scooter:nearby-telemetry',{detail:$json}))")
    }

    private fun parseTelemetry(bytes: ByteArray?): JSONObject? {
        if (bytes == null || bytes.isEmpty()) {
            return null
        }

        return try {
            JSONObject(bytes.toString(Charsets.UTF_8))
        } catch (_: Exception) {
            null
        }
    }

    @SuppressLint("MissingPermission")
    private fun disconnectGatt() {
        commandCharacteristic = null
        telemetryCharacteristic = null
        gatt?.disconnect()
        gatt?.close()
        gatt = null
    }

    private fun emitStatus(message: String) {
        emitJs("window.dispatchEvent(new CustomEvent('scooter:ble-status',{detail:{message:${JSONObject.quote(message)}}}))")
    }

    private fun emitNearbyScanStatus(message: String) {
        emitJs("window.dispatchEvent(new CustomEvent('scooter:nearby-scan-status',{detail:{message:${JSONObject.quote(message)}}}))")
    }

    private fun emitConnected(scooterId: String) {
        emitJs("window.dispatchEvent(new CustomEvent('scooter:ble-connected',{detail:{scooterId:${JSONObject.quote(scooterId)}}}))")
    }

    private fun emitTelemetry(json: String) {
        latestTelemetryJson = json
        emitJs("window.dispatchEvent(new CustomEvent('scooter:telemetry',{detail:$json}))")
    }

    private fun emitJs(script: String) {
        webView.post { webView.evaluateJavascript(script, null) }
    }
}

/*
MainActivity setup notes:

webView.settings.javaScriptEnabled = true
webView.addJavascriptInterface(
    AndroidScooterBleBridge(this, webView, BluetoothAdapter.getDefaultAdapter()),
    "ScooterBle"
)

AndroidManifest permissions:
<uses-permission android:name="android.permission.BLUETOOTH_SCAN" />
<uses-permission android:name="android.permission.BLUETOOTH_CONNECT" />
<uses-permission android:name="android.permission.ACCESS_FINE_LOCATION" android:maxSdkVersion="30" />

At runtime request BLUETOOTH_SCAN and BLUETOOTH_CONNECT on Android 12+.
Request ACCESS_FINE_LOCATION on Android 11 and lower for BLE scanning.
*/
