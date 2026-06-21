(function () {
    const SERVICE_UUID = '7b6a1000-2f6d-4e7f-9b2e-30a0bbd80101';
    const COMMAND_UUID = '7b6a1001-2f6d-4e7f-9b2e-30a0bbd80101';
    const TELEMETRY_UUID = '7b6a1002-2f6d-4e7f-9b2e-30a0bbd80101';
    const textDecoder = new TextDecoder();
    const MIN_ASSIGN_BATTERY_PERCENT = 10;
    const LIVE_STATS_INTERVAL_MS = 60000;

    let webDevice = null;
    let commandCharacteristic = null;
    let telemetryCharacteristic = null;
    let activeScooterId = '';
    let pendingSubmit = null;
    let activeForm = null;
    let latestTelemetry = null;
    const nearbyScooters = new Map();
    const latestTelemetryByScooter = new Map();
    let liveStatsTimer = null;
    let liveStatsReportTimeout = null;
    let bluetoothEnablePrompted = false;

    function setStatus(form, message, state) {
        const status = form?.querySelector('[data-iot-status]');

        if (!status) {
            return;
        }

        status.textContent = message;
        status.dataset.state = state || 'idle';
    }

    function iotSubmitButtons(form) {
        return Array.from(form?.querySelectorAll('button[type="submit"], input[type="submit"]') || []);
    }

    function setIotSubmitEnabled(form, enabled) {
        if (!form?.matches?.('form[data-iot-command]') || !form.querySelector('[data-iot-device-input]')) {
            return;
        }

        iotSubmitButtons(form).forEach((button) => {
            button.disabled = !enabled;
            button.dataset.iotRequiresConnection = 'true';
        });
    }

    function markFormConnected(form, scooterId) {
        if (!form) {
            return;
        }

        form.dataset.connectedScooterId = scooterId || '';
        setIotSubmitEnabled(form, Boolean(scooterId));
    }

    function markFormDisconnected(form) {
        if (!form) {
            return;
        }

        delete form.dataset.connectedScooterId;
        setIotSubmitEnabled(form, false);
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
        if (!window.ScooterBle || typeof window.ScooterBle[name] !== 'function') {
            throw new Error('Bluetooth control is only available in the installed app.');
        }

        const result = window.ScooterBle[name](JSON.stringify(payload || {}));

        if (result && typeof result.then === 'function') {
            return result;
        }

        return result;
    }

    async function isNativeBluetoothEnabled() {
        if (!hasNativeBridge() || typeof window.ScooterBle.isBluetoothEnabled !== 'function') {
            return true;
        }

        const result = safeJson(await nativeCall('isBluetoothEnabled'));

        return result.available !== false && result.enabled === true;
    }

    async function requestNativeBluetoothEnable() {
        if (!hasNativeBridge() || typeof window.ScooterBle.requestBluetoothEnable !== 'function') {
            return;
        }

        await nativeCall('requestBluetoothEnable');
    }

    function removeBluetoothGate() {
        const gate = document.getElementById('iotBluetoothGate');

        if (!gate) {
            return;
        }

        gate.remove();
        window.dispatchEvent(new Event('scooter:bluetooth-enabled'));
    }

    function showBluetoothGate() {
        if (document.getElementById('iotBluetoothGate')) {
            return;
        }

        const gate = document.createElement('div');
        gate.id = 'iotBluetoothGate';
        gate.style.cssText = 'position:fixed;inset:0;z-index:2147483647;display:flex;align-items:center;justify-content:center;padding:24px;background:rgba(10,16,24,.92);color:#fff;text-align:center;';
        gate.innerHTML = `
            <div style="max-width:360px;width:100%;background:#fff;color:#101828;border-radius:14px;padding:24px;box-shadow:0 24px 60px rgba(0,0,0,.28);">
                <div style="width:48px;height:48px;border-radius:50%;margin:0 auto 16px;background:#e8f1ff;color:#0d6efd;display:flex;align-items:center;justify-content:center;font-size:24px;">
                    <i class="fas fa-bluetooth-b"></i>
                </div>
                <h2 style="font-size:20px;line-height:1.3;margin:0 0 8px;font-weight:700;">Turn on Bluetooth</h2>
                <p style="font-size:14px;line-height:1.5;margin:0 0 18px;color:#667085;">Bluetooth is required before using this app.</p>
                <button type="button" data-bluetooth-enable style="width:100%;border:0;border-radius:10px;background:#0d6efd;color:#fff;padding:12px 14px;font-weight:700;">Turn On Bluetooth</button>
            </div>
        `;

        gate.querySelector('[data-bluetooth-enable]')?.addEventListener('click', () => {
            requestNativeBluetoothEnable().catch(() => {});
        });

        document.body.appendChild(gate);
    }

    async function enforceNativeBluetoothEnabled(promptToEnable = false) {
        if (!hasNativeBridge()) {
            return true;
        }

        const enabled = await isNativeBluetoothEnabled();

        if (enabled) {
            removeBluetoothGate();
            return true;
        }

        showBluetoothGate();

        if (promptToEnable && !bluetoothEnablePrompted) {
            bluetoothEnablePrompted = true;
            await requestNativeBluetoothEnable().catch(() => {});
        }

        return false;
    }

    async function connectNative(scooterId, mac) {
        if (!await enforceNativeBluetoothEnabled(true)) {
            throw new Error('Turn on Bluetooth to continue.');
        }

        const connected = waitForWindowEvent(
            'scooter:ble-connected',
            (event) => (event.detail?.scooterId || scooterId) === scooterId,
            15000
        );
        await nativeCall('connect', { scooterId, mac, serviceUuid: SERVICE_UUID });
        await connected;
        activeScooterId = scooterId;
        return true;
    }

    async function sendNative(command) {
        await nativeCall('sendCommand', { scooterId: activeScooterId, command });
        return true;
    }

    async function connectWebBluetooth(scooterId) {
        throw new Error('Bluetooth control is only available in the installed app.');
    }

    async function sendWebBluetooth(command) {
        throw new Error('Bluetooth control is only available in the installed app.');
    }

    async function disconnectBluetooth() {
        if (hasNativeBridge() && typeof window.ScooterBle.disconnect === 'function') {
            await nativeCall('disconnect');
        }

        if (webDevice?.gatt?.connected) {
            webDevice.gatt.disconnect();
        }

        commandCharacteristic = null;
        telemetryCharacteristic = null;
        activeScooterId = '';
        markFormDisconnected(activeForm);
    }

    async function readNativeTelemetry() {
        if (!hasNativeBridge() || typeof window.ScooterBle.readTelemetry !== 'function') {
            return null;
        }

        const immediateResult = safeJson(await nativeCall('readTelemetry'));
        const immediateTelemetry = immediateResult.telemetry && typeof immediateResult.telemetry === 'object'
            ? immediateResult.telemetry
            : null;

        if (immediateTelemetry) {
            rememberTelemetry(immediateTelemetry);
        }

        await wait(900);

        if (typeof window.ScooterBle.getLastTelemetry !== 'function') {
            return immediateTelemetry;
        }

        const latestTelemetry = safeJson(window.ScooterBle.getLastTelemetry());

        if (latestTelemetry && !latestTelemetry.raw && latestTelemetry.id) {
            rememberTelemetry(latestTelemetry);
            return latestTelemetry;
        }

        return immediateTelemetry;
    }

    async function readWebTelemetry() {
        if (!telemetryCharacteristic) {
            return null;
        }

        const value = await telemetryCharacteristic.readValue();
        const telemetry = safeJson(textDecoder.decode(value));
        rememberTelemetry(telemetry);
        return telemetry;
    }

    async function readFinalTelemetry() {
        if (hasNativeBridge()) {
            return readNativeTelemetry();
        }

        return readWebTelemetry();
    }

    function safeJson(value) {
        try {
            return JSON.parse(value);
        } catch (error) {
            return { raw: value };
        }
    }

    function rememberTelemetry(data) {
        if (data && !data.raw) {
            latestTelemetry = data;
        }

        if (data && !data.raw && data.id) {
            latestTelemetryByScooter.set(data.id, data);
        }
    }

    function telemetryBattery(data) {
        if (!data || data.raw || typeof data.battery === 'undefined') {
            return null;
        }

        const battery = Number(data.battery);

        return Number.isFinite(battery) ? battery : null;
    }

    function telemetryKm(data) {
        if (!data || data.raw || typeof data.km === 'undefined') {
            return null;
        }

        const km = Number(data.km);

        return Number.isFinite(km) ? km : null;
    }

    function formatConnectedMessage(scooterId, telemetry) {
        const parts = [`Bluetooth connected${scooterId ? `: ${scooterId}` : ''}`];
        const battery = telemetryBattery(telemetry);
        const km = telemetryKm(telemetry);

        if (battery !== null) {
            parts.push(`Battery ${battery}%`);
        }

        if (km !== null) {
            parts.push(`${km.toFixed(3)} km`);
        }

        return parts.join(' | ');
    }

    function setBatteryInput(form, battery) {
        setHiddenInput(form, '[data-iot-battery-input]', battery !== null ? String(battery) : '');
    }

    async function refreshTelemetry(form, scooterId) {
        const telemetry = await readFinalTelemetry();
        const remembered = latestTelemetryByScooter.get(scooterId || activeScooterId) || latestTelemetry;
        const selectedTelemetry = telemetry && !telemetry.raw ? telemetry : remembered;

        if (selectedTelemetry) {
            rememberTelemetry(selectedTelemetry);
            captureKmOnForm(form, selectedTelemetry);
        }

        return selectedTelemetry || null;
    }

    async function ensureAssignableBattery(form, scooterId) {
        setStatus(form, 'Checking scooter battery...', 'pending');
        const telemetry = await refreshTelemetry(form, scooterId);
        const battery = telemetryBattery(telemetry);

        setBatteryInput(form, battery);

        if (battery !== null && battery <= MIN_ASSIGN_BATTERY_PERCENT) {
            throw new Error(`Battery ${battery}% is too low. Assign another scooter.`);
        }

        setStatus(form, formatConnectedMessage(scooterId, telemetry), 'connected');
        return telemetry;
    }

    function wait(ms) {
        return new Promise((resolve) => window.setTimeout(resolve, ms));
    }

    function waitForWindowEvent(name, predicate, timeoutMs) {
        return new Promise((resolve, reject) => {
            const timeout = window.setTimeout(() => {
                window.removeEventListener(name, onEvent);
                reject(new Error('Bluetooth connection timed out.'));
            }, timeoutMs);

            function onEvent(event) {
                if (predicate && !predicate(event)) {
                    return;
                }

                window.clearTimeout(timeout);
                window.removeEventListener(name, onEvent);
                resolve(event);
            }

            window.addEventListener(name, onEvent);
        });
    }

    function renderNearbyScooters() {
        const list = document.querySelector('[data-nearby-scooter-list]');
        const empty = document.querySelector('[data-nearby-scooter-empty]');
        const search = document.querySelector('[data-nearby-scooter-search]');
        const term = (search?.value || '').trim().toLowerCase();

        if (!list) {
            return;
        }

        const scooters = Array.from(nearbyScooters.values())
            .filter((scooter) => term === '' || scooter.scooterId.toLowerCase().includes(term))
            .sort((left, right) => left.scooterId.localeCompare(right.scooterId));

        list.replaceChildren(...scooters.map((scooter) => {
            const item = document.createElement('div');
            const name = document.createElement('strong');
            const battery = document.createElement('span');

            item.className = 'nearby-scooter-row';
            name.textContent = scooter.scooterId;
            battery.className = 'nearby-scooter-battery';
            battery.textContent = `${scooter.battery}%`;
            battery.dataset.level = scooter.battery <= 20 ? 'low' : scooter.battery <= 50 ? 'medium' : 'good';

            item.append(name, battery);
            return item;
        }));

        if (empty) {
            empty.hidden = scooters.length > 0;
            if (nearbyScooters.size > 0 && scooters.length === 0) {
                empty.textContent = 'No scooter matches search.';
            } else if (nearbyScooters.size === 0 && !empty.textContent.trim()) {
                empty.textContent = 'Searching for powered scooters...';
            }
        }
    }

    async function startNearbyScan() {
        const empty = document.querySelector('[data-nearby-scooter-empty]');

        if (!document.querySelector('[data-nearby-scooter-list]')) {
            return;
        }

        if (!hasNativeBridge()) {
            if (empty) {
                empty.textContent = 'Nearby battery scan is available in the Android app.';
            }
            return;
        }

        if (typeof window.ScooterBle.startNearbyScan !== 'function') {
            if (empty) {
                empty.textContent = 'Update and reinstall the Android app to enable nearby battery scanning.';
            }
            return;
        }

        if (!await enforceNativeBluetoothEnabled(true)) {
            if (empty) {
                empty.textContent = 'Turn on Bluetooth to scan nearby scooters.';
            }
            return;
        }

        await nativeCall('startNearbyScan');
    }

    async function reportLiveStats() {
        const url = document.querySelector('meta[name="scooter-live-stats-url"]')?.content;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        if (!url || !csrfToken) {
            return;
        }

        const staleBefore = Date.now() - LIVE_STATS_INTERVAL_MS;
        const scooters = Array.from(nearbyScooters.values())
            .filter((scooter) => scooter.seenAt >= staleBefore)
            .map((scooter) => ({
                scooterId: scooter.scooterId,
                battery: scooter.battery,
            }));

        await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ scooters }),
        });
    }

    function scheduleLiveStatsReport(delayMs = 1200) {
        if (liveStatsReportTimeout) {
            window.clearTimeout(liveStatsReportTimeout);
        }

        liveStatsReportTimeout = window.setTimeout(() => {
            liveStatsReportTimeout = null;
            reportLiveStats().catch((error) => {
                console.warn('Live scooter stats could not be reported.', error);
            });
        }, delayMs);
    }

    function startLiveStatsReporting() {
        if (liveStatsTimer || !hasNativeBridge() || typeof window.ScooterBle.startNearbyScan !== 'function') {
            return;
        }

        enforceNativeBluetoothEnabled()
            .then((enabled) => {
                if (!enabled) {
                    return;
                }

                nativeCall('startNearbyScan').catch((error) => {
                    console.warn('Live scooter scan could not start.', error);
                });

                liveStatsTimer = window.setInterval(() => {
                    reportLiveStats().catch((error) => {
                        console.warn('Live scooter stats could not be reported.', error);
                    });
                }, LIVE_STATS_INTERVAL_MS);

                window.setTimeout(() => {
                    reportLiveStats().catch(() => {});
                }, 8000);
            })
            .catch(() => {});
    }

    async function connect(scooterId, form) {
        const payload = parseScooterPayload(scooterId);
        const normalizedId = payload.scooterId;

        if (!normalizedId) {
            throw new Error('Scan the scooter IoT QR first.');
        }

        if (hasNativeBridge() && activeScooterId === normalizedId) {
            const telemetry = latestTelemetryByScooter.get(normalizedId) || latestTelemetry;
            setStatus(form, formatConnectedMessage(normalizedId, telemetry), 'connected');
            markFormConnected(form, normalizedId);
            return normalizedId;
        }

        setStatus(form, 'Connecting Bluetooth...', 'pending');
        activeForm = form || activeForm;

        if (hasNativeBridge()) {
            await connectNative(normalizedId, payload.mac);
        } else {
            await connectWebBluetooth(normalizedId);
        }

        const telemetry = await refreshTelemetry(form, normalizedId);
        setStatus(form, formatConnectedMessage(normalizedId, telemetry), 'connected');
        markFormConnected(form, normalizedId);
        return normalizedId;
    }

    async function sendCommand(command, form) {
        if (hasNativeBridge()) {
            return sendNative(command);
        }

        return sendWebBluetooth(command);
    }

    function setHiddenInput(form, selector, value) {
        const input = form?.querySelector(selector);

        if (input && typeof value !== 'undefined' && value !== null && value !== '') {
            input.value = value;
        }
    }

    function prepareTripTelemetry(form, scooterId) {
        const telemetry = latestTelemetryByScooter.get(scooterId || activeScooterId) || latestTelemetry;

        if (!form || !telemetry) {
            return false;
        }

        if (typeof telemetry.km !== 'undefined') {
            setHiddenInput(form, '[data-iot-distance-input]', Number(telemetry.km).toFixed(3));
            return true;
        }

        return false;
    }

    function captureKmOnForm(form, data) {
        if (!form || !data || typeof data.km === 'undefined') {
            return false;
        }

        setHiddenInput(form, '[data-iot-distance-input]', Number(data.km).toFixed(3));
        return true;
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
            if (command === 'START') {
                await ensureAssignableBattery(form, scooterId);
            }
            setStatus(form, command === 'START' ? 'Powering scooter on...' : 'Powering scooter off...', 'pending');
            if (command === 'START') {
                await sendCommand('RESET_KM', form);
                await wait(250);
            }
            await sendCommand(command, form);
            setStatus(form, command === 'START' ? 'Scooter powered on' : 'Scooter powered off', 'connected');
            if (command === 'STOP') {
                setStatus(form, 'Waiting for final KM...', 'pending');
                await wait(4500);
                const telemetry = await refreshTelemetry(form, scooterId);
                const hasKm = prepareTripTelemetry(form, scooterId);
                const battery = telemetryBattery(telemetry);
                setBatteryInput(form, battery);
                const km = telemetryKm(telemetry);
                setStatus(
                    form,
                    hasKm
                        ? `Final KM captured: ${(km ?? Number(form.querySelector('[data-iot-distance-input]')?.value || 0)).toFixed(3)} km${battery !== null ? ` | Battery ${battery}%` : ''}`
                        : `Final KM not received${battery !== null ? ` | Battery ${battery}%` : ''}.`,
                    hasKm ? 'connected' : 'error'
                );
            } else {
                await wait(1200);
            }
            await disconnectBluetooth();
            pendingSubmit = form;
            form.requestSubmit();
        } catch (error) {
            await disconnectBluetooth().catch(() => {});
            setStatus(form, error.message || 'Bluetooth command failed.', 'error');
            window.showAppToast?.(error.message || 'Bluetooth command failed.', 'error');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        enforceNativeBluetoothEnabled(true).catch(() => {});
        window.setInterval(() => {
            enforceNativeBluetoothEnabled().catch(() => {});
        }, 1500);

        window.addEventListener('scooter:bluetooth-enabled', () => {
            startNearbyScan().catch(() => {});
            startLiveStatsReporting();
        });

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
            setIotSubmitEnabled(form, false);
            form.addEventListener('submit', handleSubmit);

            form.querySelector('[data-iot-device-input]')?.addEventListener('input', () => {
                markFormDisconnected(form);
            });
        });

        window.addEventListener('scooter:ble-status', (event) => {
            const message = event.detail?.message || 'Bluetooth status updated.';
            const state = /failed|not|unavailable|error|disconnect/i.test(message) ? 'error' : 'pending';
            setGlobalStatus(message, state);
        });

        window.addEventListener('scooter:ble-connected', (event) => {
            const scooterId = event.detail?.scooterId || activeScooterId || '';
            const telemetry = latestTelemetryByScooter.get(scooterId) || latestTelemetry;
            setGlobalStatus(formatConnectedMessage(scooterId, telemetry), 'connected');
        });

        window.addEventListener('scooter:telemetry', (event) => {
            const data = event.detail || {};
            const form = activeForm || document.querySelector('form[data-iot-command]');

            if (!form || data.raw) {
                return;
            }

            rememberTelemetry(data);
            captureKmOnForm(form, data);

            const parts = [];

            if (typeof data.active !== 'undefined') {
                parts.push(data.active ? 'active' : 'idle');
            }

            if (typeof data.battery !== 'undefined') {
                parts.push(`${data.battery}%`);
                setBatteryInput(form, Number(data.battery));
            }

            if (typeof data.km !== 'undefined') {
                parts.push(`${Number(data.km).toFixed(3)} km`);
            }

            if (parts.length > 0) {
                setStatus(form, `Telemetry: ${parts.join(' | ')}`, 'connected');
            }
        });

        window.addEventListener('scooter:nearby-telemetry', (event) => {
            const data = event.detail || {};

            if (!data.scooterId || typeof data.battery === 'undefined') {
                return;
            }

            nearbyScooters.set(data.scooterId, {
                scooterId: data.scooterId,
                battery: Number(data.battery),
                rssi: Number(data.rssi),
                mac: data.mac || '',
                seenAt: Date.now(),
            });
            renderNearbyScooters();
            scheduleLiveStatsReport();
        });

        window.addEventListener('scooter:nearby-scan-status', (event) => {
            const empty = document.querySelector('[data-nearby-scooter-empty]');

            if (empty && nearbyScooters.size === 0) {
                empty.textContent = event.detail?.message || 'Searching for powered scooters...';
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
                markFormDisconnected(input.closest('form'));
            });
        });

        startNearbyScan().catch((error) => {
            console.warn('Nearby scooter scan could not start.', error);
        });
        startLiveStatsReporting();

        window.setInterval(() => {
            const staleBefore = Date.now() - 30000;
            let changed = false;

            nearbyScooters.forEach((scooter, scooterId) => {
                if (scooter.seenAt < staleBefore) {
                    nearbyScooters.delete(scooterId);
                    changed = true;
                }
            });

            if (changed) {
                renderNearbyScooters();
            }
        }, 10000);
    });

    window.ScooterIot = {
        connect,
        sendCommand,
        disconnect: disconnectBluetooth,
        normalizeScooterId,
        startNearbyScan,
        renderNearbyScooters,
        serviceUuid: SERVICE_UUID,
        commandUuid: COMMAND_UUID,
        telemetryUuid: TELEMETRY_UUID,
    };
})();
