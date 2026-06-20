(function () {
    const SERVICE_UUID = '7b6a1000-2f6d-4e7f-9b2e-30a0bbd80101';
    const COMMAND_UUID = '7b6a1001-2f6d-4e7f-9b2e-30a0bbd80101';
    const TELEMETRY_UUID = '7b6a1002-2f6d-4e7f-9b2e-30a0bbd80101';
    const textEncoder = new TextEncoder();
    const textDecoder = new TextDecoder();

    let webDevice = null;
    let commandCharacteristic = null;
    let telemetryCharacteristic = null;
    let activeScooterId = '';
    let pendingSubmit = null;
    let activeForm = null;

    function setStatus(form, message, state) {
        const status = form?.querySelector('[data-iot-status]');

        if (!status) {
            return;
        }

        status.textContent = message;
        status.dataset.state = state || 'idle';
    }

    function setGlobalStatus(message, state) {
        const form = activeForm || document.querySelector('form[data-iot-command]');
        setStatus(form, message, state);
    }

    function normalizeScooterId(value) {
        return parseScooterPayload(value).scooterId;
    }

    function parseScooterPayload(value) {
        const raw = (value || '').trim();
        const lowerRaw = raw.toLowerCase();
        const parsed = {
            raw,
            scooterId: '',
            mac: '',
        };

        if (lowerRaw.startsWith('scooter:')) {
            const parts = raw.split(';').map((part) => part.trim()).filter(Boolean);
            parsed.scooterId = parts[0].slice(8).trim();

            parts.slice(1).forEach((part) => {
                const separatorIndex = part.indexOf(':');

                if (separatorIndex === -1) {
                    return;
                }

                const key = part.slice(0, separatorIndex).trim().toLowerCase();
                const partValue = part.slice(separatorIndex + 1).trim();

                if (key === 'mac') {
                    parsed.mac = partValue;
                }
            });

            return parsed;
        }

        try {
            const url = new URL(raw);
            parsed.scooterId = url.searchParams.get('scooter') || url.searchParams.get('iot') || url.pathname.split('/').filter(Boolean).pop() || raw;
            parsed.mac = url.searchParams.get('mac') || '';
            return parsed;
        } catch (error) {
            parsed.scooterId = raw;
            return parsed;
        }
    }

    function syncVehicleNumberFromIotInput(input) {
        const form = input?.closest('form');
        const scooterId = normalizeScooterId(input?.value || '');

        if (!form || !scooterId) {
            return scooterId;
        }

        input.value = scooterId;

        const targetId = input.dataset.rideNumberTarget;
        const vehicleNumberInput = targetId
            ? document.getElementById(targetId)
            : form.querySelector('input[name="ride_number"]');

        if (vehicleNumberInput) {
            vehicleNumberInput.value = scooterId;
            vehicleNumberInput.dispatchEvent(new Event('input', { bubbles: true }));
            vehicleNumberInput.dispatchEvent(new Event('change', { bubbles: true }));
        }

        return scooterId;
    }

    function hasNativeBridge() {
        return Boolean(window.ScooterBle && typeof window.ScooterBle.connect === 'function');
    }

    async function nativeCall(name, payload) {
        const result = window.ScooterBle[name](JSON.stringify(payload || {}));

        if (result && typeof result.then === 'function') {
            return result;
        }

        return result;
    }

    async function connectNative(scooterId, mac) {
        await nativeCall('connect', { scooterId, mac, serviceUuid: SERVICE_UUID });
        activeScooterId = scooterId;
        return true;
    }

    async function sendNative(command) {
        await nativeCall('sendCommand', { scooterId: activeScooterId, command });
        return true;
    }

    async function connectWebBluetooth(scooterId) {
        if (!navigator.bluetooth) {
            throw new Error('Bluetooth is not available in this browser. Use the installed WebView app.');
        }

        webDevice = await navigator.bluetooth.requestDevice({
            filters: [{ namePrefix: `RYDOZ-${scooterId}` }],
            optionalServices: [SERVICE_UUID],
        });

        const server = await webDevice.gatt.connect();
        const service = await server.getPrimaryService(SERVICE_UUID);
        commandCharacteristic = await service.getCharacteristic(COMMAND_UUID);
        telemetryCharacteristic = await service.getCharacteristic(TELEMETRY_UUID);

        telemetryCharacteristic.addEventListener('characteristicvaluechanged', (event) => {
            const payload = textDecoder.decode(event.target.value);
            window.dispatchEvent(new CustomEvent('scooter:telemetry', { detail: safeJson(payload) }));
        });

        await telemetryCharacteristic.startNotifications();
        activeScooterId = scooterId;
        return true;
    }

    async function sendWebBluetooth(command) {
        if (!commandCharacteristic) {
            throw new Error('Scooter Bluetooth is not connected.');
        }

        await commandCharacteristic.writeValue(textEncoder.encode(`${command}\n`));
        return true;
    }

    function safeJson(value) {
        try {
            return JSON.parse(value);
        } catch (error) {
            return { raw: value };
        }
    }

    function wait(ms) {
        return new Promise((resolve) => window.setTimeout(resolve, ms));
    }

    async function connect(scooterId, form) {
        const payload = parseScooterPayload(scooterId);
        const normalizedId = payload.scooterId;

        if (!normalizedId) {
            throw new Error('Scan the scooter IoT QR first.');
        }

        if (hasNativeBridge() && activeScooterId === normalizedId) {
            setStatus(form, `Bluetooth selected: ${normalizedId}`, 'connected');
            return normalizedId;
        }

        setStatus(form, 'Connecting Bluetooth...', 'pending');
        activeForm = form || activeForm;

        if (hasNativeBridge()) {
            await connectNative(normalizedId, payload.mac);
        } else {
            await connectWebBluetooth(normalizedId);
        }

        setStatus(form, `Connected: ${normalizedId}`, 'connected');
        return normalizedId;
    }

    async function sendCommand(command, form) {
        if (hasNativeBridge()) {
            return sendNative(command);
        }

        return sendWebBluetooth(command);
    }

    async function scanOrConnect(button) {
        const form = button.closest('form');
        const input = document.getElementById(button.dataset.iotTarget);
        const scannerTarget = input?.id;

        if (!input || !scannerTarget) {
            return;
        }

        activeForm = form;

        if (typeof window.openScanner === 'function') {
            window.openScanner(scannerTarget);
            return;
        }

        await connect(input.value, form);
    }

    async function handleSubmit(event) {
        const form = event.target;
        const command = form.dataset.iotCommand;

        if (!command || pendingSubmit === form) {
            pendingSubmit = null;
            return;
        }

        activeForm = form;

        const input = form.querySelector('[data-iot-device-input]');

        if (!input || !input.value.trim()) {
            return;
        }

        event.preventDefault();

        try {
            const preparedScooterId = syncVehicleNumberFromIotInput(input);
            const scooterId = await connect(input.value, form);
            input.value = scooterId || preparedScooterId;
            syncVehicleNumberFromIotInput(input);
            setStatus(form, command === 'START' ? 'Powering scooter on...' : 'Powering scooter off...', 'pending');
            await sendCommand(command, form);
            setStatus(form, command === 'START' ? 'Scooter powered on' : 'Scooter powered off', 'connected');
            await wait(1200);
            pendingSubmit = form;
            form.requestSubmit();
        } catch (error) {
            setStatus(form, error.message || 'Bluetooth command failed.', 'error');
            window.showAppToast?.(error.message || 'Bluetooth command failed.', 'error');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-iot-scan]').forEach((button) => {
            button.addEventListener('click', () => {
                scanOrConnect(button).catch((error) => {
                    const form = button.closest('form');
                    setStatus(form, error.message || 'Bluetooth connection failed.', 'error');
                    window.showAppToast?.(error.message || 'Bluetooth connection failed.', 'error');
                });
            });
        });

        document.querySelectorAll('form[data-iot-command]').forEach((form) => {
            form.addEventListener('submit', handleSubmit);
        });

        window.addEventListener('scooter:ble-status', (event) => {
            const message = event.detail?.message || 'Bluetooth status updated.';
            const state = /failed|not|unavailable|error|disconnect/i.test(message) ? 'error' : 'pending';
            setGlobalStatus(message, state);
        });

        window.addEventListener('scooter:ble-connected', (event) => {
            const scooterId = event.detail?.scooterId || activeScooterId || '';
            setGlobalStatus(`Bluetooth connected${scooterId ? `: ${scooterId}` : ''}`, 'connected');
        });

        window.addEventListener('scooter:telemetry', (event) => {
            const data = event.detail || {};
            const form = activeForm || document.querySelector('form[data-iot-command]');

            if (!form || data.raw) {
                return;
            }

            const parts = [];

            if (typeof data.active !== 'undefined') {
                parts.push(data.active ? 'active' : 'idle');
            }

            if (typeof data.battery !== 'undefined') {
                parts.push(`${data.battery}%`);
            }

            if (typeof data.km !== 'undefined') {
                parts.push(`${Number(data.km).toFixed(3)} km`);
            }

            if (parts.length > 0) {
                setStatus(form, `Telemetry: ${parts.join(' | ')}`, 'connected');
            }
        });

        window.addEventListener('scooter:qr-scanned', (event) => {
            const input = event.detail?.input;

            if (!input?.matches?.('[data-iot-device-input]')) {
                return;
            }

            const scooterId = syncVehicleNumberFromIotInput(input);

            connect(scooterId, input.closest('form')).catch((error) => {
                setStatus(input.closest('form'), error.message || 'Bluetooth connection failed.', 'error');
            });
        });
    });

    window.ScooterIot = {
        connect,
        sendCommand,
        normalizeScooterId,
        serviceUuid: SERVICE_UUID,
        commandUuid: COMMAND_UUID,
        telemetryUuid: TELEMETRY_UUID,
    };
})();
