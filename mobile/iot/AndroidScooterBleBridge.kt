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
    private var pendingScooterId: String = ""

    @JavascriptInterface
    fun connect(json: String): String {
        val payload = JSONObject(json)
        pendingScooterId = payload.optString("scooterId").trim()
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
                emitStatus("Bluetooth disconnected")
            }
        }

        @SuppressLint("MissingPermission")
        override fun onServicesDiscovered(gatt: BluetoothGatt, status: Int) {
            val service = gatt.getService(serviceUuid) ?: return emitStatus("Scooter service not found")
            commandCharacteristic = service.getCharacteristic(commandUuid)
            val telemetry = service.getCharacteristic(telemetryUuid)

            if (telemetry != null) {
                gatt.setCharacteristicNotification(telemetry, true)
                telemetry.getDescriptor(cccdUuid)?.let { descriptor ->
                    descriptor.value = BluetoothGattDescriptor.ENABLE_NOTIFICATION_VALUE
                    gatt.writeDescriptor(descriptor)
                }
            }

            emitConnected(pendingScooterId)
        }

        override fun onCharacteristicChanged(gatt: BluetoothGatt, characteristic: BluetoothGattCharacteristic) {
            if (characteristic.uuid == telemetryUuid) {
                emitTelemetry(characteristic.value.toString(Charsets.UTF_8))
            }
        }
    }

    private fun emitStatus(message: String) {
        emitJs("window.dispatchEvent(new CustomEvent('scooter:ble-status',{detail:{message:${JSONObject.quote(message)}}}))")
    }

    private fun emitConnected(scooterId: String) {
        emitJs("window.dispatchEvent(new CustomEvent('scooter:ble-connected',{detail:{scooterId:${JSONObject.quote(scooterId)}}}))")
    }

    private fun emitTelemetry(json: String) {
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
