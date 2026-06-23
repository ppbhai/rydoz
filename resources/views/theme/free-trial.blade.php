@include('theme.partials.head', ['title' => 'Free Trial'])

<body>
    @include('theme.partials.header', [
        'title' => 'Free Trial',
        'kicker' => '1 min or 100 meter',
        'backUrl' => route('index'),
    ])

    <div class="app-shell">
        <div class="page-body">
            <div class="panel">
                <h2 class="panel-title">Free Trial</h2>

                <div class="scanner-field mb-3">
                    <input type="text" class="form-control assign-input ride-number-input"
                        id="free-trial-iot-device"
                        placeholder="Scan IoT QR"
                        data-free-trial-device>
                    <button type="button"
                        class="btn btn-light-theme scanner-btn scan-trigger"
                        data-shared-scan
                        data-target-input="free-trial-iot-device"
                        aria-label="Scan IoT QR">
                        <i class="fas fa-bluetooth-b"></i>
                    </button>
                </div>

                <div class="iot-status small mb-3" data-free-trial-status>
                    Scan IoT QR to connect Bluetooth.
                </div>

                <div class="free-trial-actions">
                    <button type="button" class="btn btn-theme w-100" data-free-trial-assign hidden>
                        Assign Free Trial
                    </button>
                    <button type="button" class="btn btn-danger w-100" data-free-trial-complete hidden>
                        Complete Free Trial
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .free-trial-actions {
            display: grid;
            gap: 10px;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const input = document.querySelector('[data-free-trial-device]');
            const status = document.querySelector('[data-free-trial-status]');
            const assignButton = document.querySelector('[data-free-trial-assign]');
            const completeButton = document.querySelector('[data-free-trial-complete]');

            let scooterId = '';
            let latestTelemetry = null;
            let trialActive = false;
            let completing = false;

            function setStatus(message, state = 'pending') {
                if (!status) {
                    return;
                }

                status.textContent = message;
                status.dataset.state = state;
            }

            function normalizeScooterId(value) {
                if (window.ScooterIot?.normalizeScooterId) {
                    return window.ScooterIot.normalizeScooterId(value);
                }

                return String(value || '').trim();
            }

            function scooterIsOn() {
                return latestTelemetry?.active === true || latestTelemetry?.scooterOutputOn === true || latestTelemetry?.freeTrialActive === true;
            }

            function renderButtons() {
                const connected = scooterId !== '';

                if (assignButton) {
                    assignButton.hidden = !connected || trialActive || scooterIsOn();
                }

                if (completeButton) {
                    completeButton.hidden = !connected || (!trialActive && !scooterIsOn());
                }
            }

            function clearSelectedScooter(message = 'Free trial assigned. Scan next scooter QR.') {
                scooterId = '';
                latestTelemetry = null;
                trialActive = false;

                if (input) {
                    input.value = '';
                }

                setStatus(message, 'connected');
                renderButtons();
            }

            async function connectScooter() {
                const rawId = input?.value || '';
                const normalizedId = normalizeScooterId(rawId);

                if (!normalizedId) {
                    setStatus('Scan IoT QR first.', 'error');
                    return '';
                }

                if (!window.ScooterIot?.connect) {
                    setStatus('Open this page in the Android app to connect Bluetooth.', 'error');
                    return '';
                }

                setStatus('Connecting Bluetooth...', 'pending');
                scooterId = await window.ScooterIot.connect(normalizedId);

                if (input) {
                    input.value = scooterId;
                }

                setStatus(`Bluetooth connected: ${scooterId}`, 'connected');
                renderButtons();

                return scooterId;
            }

            async function assignTrial() {
                try {
                    assignButton.disabled = true;
                    const connectedScooterId = scooterId || await connectScooter();

                    if (!connectedScooterId) {
                        return;
                    }

                    trialActive = true;
                    setStatus('Starting free trial...', 'pending');
                    renderButtons();

                    await window.ScooterIot.sendCommand('START_TRIAL');
                    await window.ScooterIot.disconnect?.().catch(() => {});
                    clearSelectedScooter('Free trial assigned. Scan next scooter QR.');
                } catch (error) {
                    trialActive = false;
                    setStatus(error.message || 'Unable to start free trial.', 'error');
                    window.showAppToast?.(error.message || 'Unable to start free trial.', 'error');
                    renderButtons();
                } finally {
                    assignButton.disabled = false;
                }
            }

            async function completeTrial(reason = 'Free trial completed.') {
                if (completing || !scooterId || !window.ScooterIot?.sendCommand) {
                    return;
                }

                completing = true;
                assignButton.disabled = true;
                completeButton.disabled = true;
                setStatus('Stopping scooter power...', 'pending');

                try {
                    await window.ScooterIot.sendCommand('STOP');
                    latestTelemetry = {
                        ...(latestTelemetry || {}),
                        active: false,
                        scooterOutputOn: false,
                        freeTrialActive: false,
                        freeTrialStopReason: 'manual',
                    };
                    trialActive = false;
                    setStatus(reason, 'connected');
                } catch (error) {
                    setStatus(error.message || 'Unable to stop scooter.', 'error');
                    window.showAppToast?.(error.message || 'Unable to stop scooter.', 'error');
                } finally {
                    completing = false;
                    assignButton.disabled = false;
                    completeButton.disabled = false;
                    renderButtons();
                }
            }

            function checkAutoComplete() {
                if (!trialActive || completing) {
                    renderButtons();
                    return;
                }

                if (latestTelemetry?.freeTrialActive === false && latestTelemetry?.freeTrialStopReason) {
                    trialActive = false;
                    setStatus(`Free trial stopped by ESP: ${latestTelemetry.freeTrialStopReason}.`, 'connected');
                    renderButtons();
                }
            }

            function resetForInputChange() {
                scooterId = '';
                latestTelemetry = null;
                trialActive = false;
                setStatus('Scan IoT QR to connect Bluetooth.');
                renderButtons();
            }

            input?.addEventListener('input', resetForInputChange);
            input?.addEventListener('change', resetForInputChange);

            assignButton?.addEventListener('click', assignTrial);
            completeButton?.addEventListener('click', () => completeTrial('Free trial completed manually.'));

            window.addEventListener('scooter:qr-scanned', (event) => {
                if (event.detail?.input !== input) {
                    return;
                }

                connectScooter().catch((error) => {
                    setStatus(error.message || 'Bluetooth connection failed.', 'error');
                    window.showAppToast?.(error.message || 'Bluetooth connection failed.', 'error');
                });
            });

            window.addEventListener('scooter:ble-connected', (event) => {
                const connectedId = event.detail?.scooterId || scooterId;

                if (connectedId) {
                    scooterId = connectedId;
                }

                renderButtons();
            });

            window.addEventListener('scooter:telemetry', (event) => {
                const data = event.detail || {};

                if (data.raw) {
                    return;
                }

                if (!scooterId && !input?.value?.trim()) {
                    return;
                }

                latestTelemetry = data;
                if (data.id) {
                    scooterId = data.id;
                    if (input) {
                        input.value = data.id;
                    }
                }

                if (data.freeTrialActive === true) {
                    trialActive = true;
                } else if (data.freeTrialActive === false) {
                    trialActive = false;
                }

                if (scooterId && !trialActive) {
                    if (data.freeTrialActive === false && data.freeTrialStopReason && data.freeTrialStopReason !== 'none') {
                        setStatus(`Free trial stopped by ESP: ${data.freeTrialStopReason}.`, 'connected');
                        renderButtons();
                        return;
                    }

                    setStatus(
                        scooterIsOn() ? 'Scooter is ON. Complete button is ready.' : 'Scooter is OFF. Assign free trial when ready.',
                        'connected'
                    );
                }

                renderButtons();
            });

            window.setInterval(checkAutoComplete, 1000);
            renderButtons();
        });
    </script>
</body>

</html>
