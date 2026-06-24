@include('theme.partials.head', ['title' => 'On Going Ride'])
<body>
    @include('theme.partials.header', [
        'title' => 'On Going Ride',
        'kicker' => $branch->name,
        'backUrl' => route('index'),
    ])

    <div class="app-shell">
        <div class="page-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="search-panel">
                <div class="search-input-wrap">
                    <i class="fas fa-search search-input-icon"></i>
                    <input type="text" class="form-control" id="ongoingSearch" value="{{ $search }}" placeholder="Search by name, mobile, vehicle, or number">
                </div>
            </div>

            <div class="stack-sm">
                @forelse ($bookings as $booking)
                    @php
                        $numberCompletionEnabled = $branch->vehicle_number_required || $branch->scanner_enabled;
                        $completionBoxEnabled = $numberCompletionEnabled || $branch->iot_enabled;
                        $assignedAt = $booking->rides
                            ->where('status', 'ongoing')
                            ->pluck('start_time')
                            ->filter()
                            ->sort()
                            ->first();
                        $vehicleSummary = $booking->rides
                            ->where('status', 'ongoing')
                            ->groupBy('vehicle_name')
                            ->map(fn($rides, $vehicleName) => '<span class="summary-item" style="font-size: 15px; color:black">' . e($vehicleName) . ' <span class="header-status-pill" style="color:black">' . $rides->count() . '</span></span>')
                            ->values()
                            ->implode('');
                    @endphp
                    <div
                        class="accordion-card soft-accent-card"
                        data-accordion
                        data-booking-id="{{ $booking->id }}"
                        data-search-text="{{ strtolower($booking->name . ' ' . $booking->mobile . ' ' . strip_tags($vehicleSummary)) }}">
                        <button type="button" class="accordion-toggle">
                            <div>
                                <div>{{ $booking->name }}</div>
                                <div class="text-muted small">{{ $booking->mobile }}</div>
                                <div class="booking-meta">
                                    <span>{{ $assignedAt ? $assignedAt->format('d F Y, h:i A') : '-' }}</span>
                                </div>
                            </div>
                            <div class="booking-summary">{!! $vehicleSummary !!}</div>
                        </button>

                        <div class="accordion-body">
                            {{-- @if ($numberCompletionEnabled)
                                <form method="POST" action="{{ route('ride.finish.number', $booking) }}" class="assign-row mb-2">
                                    @csrf
                                    <input type="hidden" name="return_search" value="{{ $search }}">
                                    <input type="hidden" name="return_booking" value="{{ $booking->id }}">
                                    <div class="assign-row-top">
                                        <div class="assign-name">Vehicle ID</div>
                                        <div class="scanner-field">
                                            <input type="text" class="form-control assign-input ride-number-input"
                                                id="complete-number-{{ $booking->id }}" name="ride_number"
                                                placeholder="Enter Id Number" required>
                                            @if ($branch->scanner_enabled)
                                                <button type="button"
                                                    class="btn btn-light-theme scanner-btn scan-trigger"
                                                    data-target-input="complete-number-{{ $booking->id }}"
                                                    aria-label="Scan barcode">
                                                    <i class="fas fa-barcode"></i>
                                                </button>
                                            @endif
                                        </div>
                                        <button class="btn btn-theme" type="submit">Complete</button>
                                    </div>
                                </form>
                            @endif --}}
                            <div class="compact-stack mb-2">
                                @foreach ($booking->rides as $ride)
                                    @php
                                        $expectedEnd = $ride->start_time ? $ride->start_time->copy()->addMinutes($ride->vehicle_time) : null;
                                    @endphp
                                    <form method="POST" action="{{ route('ride.finish.single', $ride) }}" class="assign-row">
                                        @csrf
                                        <input type="hidden" name="return_search" value="{{ $search }}">
                                        <input type="hidden" name="return_booking" value="{{ $booking->id }}">

                                        <div style="display: flex; justify-content:space-between">
                                            <div class="assign-name" style="font-size: 15px; font-weight:700;">{{ $ride->vehicle_name }}</div>
                                            <div class="assign-name" style="font-weight: normal">{{ $ride->ride_number ?: 'No Number' }}</div>
                                        </div>

                                        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                                            <span style="font-weight:600; color:var(--muted); font-size:15px">
                                                Start time:{{ $ride->start_time ? $ride->start_time->format('h:i A') : '-' }}
                                            </span>
                                            @if ($ride->status === 'ongoing')
                                                <span
                                                    class="timer time-ontrack"
                                                    data-start="{{ $ride->start_time?->toIso8601String() }}"
                                                    data-booked-minutes="{{ $ride->vehicle_time }}"
                                                    data-buffer-minutes="{{ $branch->buffer_time }}" style="font-weight:bold">
                                                    00:00:00
                                                </span>
                                            @else
                                                <span class="soft-pill">Finished Ride</span>
                                            @endif
                                        </div>

                                        <div class="action-timer-row">
                                            @if ($ride->status === 'ongoing' && !$completionBoxEnabled)
                                                <button class="btn btn-theme w-100" type="submit">Complete {{ $ride->vehicle_name }}</button>
                                            @endif
                                        </div>
                                    </form>
                                @endforeach
                            </div>

                                     @if ($completionBoxEnabled)
                                <form method="POST" action="{{ route('ride.finish.number', $booking) }}" class="assign-row mb-2" @if ($branch->iot_enabled) data-iot-command="STOP" @endif>
                                    @csrf
                                    <input type="hidden" name="return_search" value="{{ $search }}">
                                    <input type="hidden" name="return_booking" value="{{ $booking->id }}">
                                    @if ($branch->iot_enabled)
                                        <input type="hidden" name="iot_completion_expected" value="1">
                                        <input type="hidden" name="iot_distance_km" value="" data-iot-distance-input>
                                        <input type="hidden" name="iot_battery_percent" value="" data-iot-battery-input>
                                        <input type="hidden" name="actual_scooter_on_seconds" value="" data-iot-actual-seconds-input>
                                    @endif
                                    <div class="assign-row-top">
                                        <div class="assign-name">Vehicle ID</div>
                                        @if ($numberCompletionEnabled)
                                            <div class="scanner-field">
                                                <input type="text" class="form-control assign-input ride-number-input"
                                                    id="complete-number-{{ $booking->id }}" name="ride_number"
                                                    placeholder="Enter Id Number">
                                                @if ($branch->scanner_enabled)
                                                    <button type="button"
                                                        class="btn btn-light-theme scanner-btn scan-trigger"
                                                        data-target-input="complete-number-{{ $booking->id }}"
                                                        aria-label="Scan barcode">
                                                        <i class="fas fa-barcode"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        @endif
                                        @if ($branch->iot_enabled)
                                            <div class="scanner-field">
                                                <input type="text" class="form-control assign-input ride-number-input"
                                                    id="complete-iot-device-{{ $booking->id }}"
                                                    name="iot_device_id"
                                                    placeholder="IoT Device ID"
                                                    data-iot-device-input>
                                                <button type="button"
                                                    class="btn btn-light-theme scanner-btn scan-trigger"
                                                    data-target-input="complete-iot-device-{{ $booking->id }}"
                                                    data-iot-scan
                                                    data-iot-target="complete-iot-device-{{ $booking->id }}"
                                                    aria-label="Scan IoT QR">
                                                    <i class="fas fa-bluetooth-b"></i>
                                                </button>
                                            </div>
                                            <div class="iot-status small mt-1" data-iot-status>Scan IoT QR to connect Bluetooth.</div>
                                        @endif
                                        <button class="btn btn-theme" type="submit">Complete</button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="empty-state">No ongoing ride found.</div>
                @endforelse
            </div>
        </div>
    </div>

    <script>
        const ongoingSearch = document.getElementById('ongoingSearch');

        const accordionCards = Array.from(document.querySelectorAll('[data-accordion]'));

        accordionCards.forEach((card) => {
            card.querySelector('.accordion-toggle').addEventListener('click', () => {
                const willOpen = !card.classList.contains('is-open');

                accordionCards.forEach((accordionCard) => {
                    accordionCard.classList.remove('is-open');
                });

                if (willOpen) {
                    card.classList.add('is-open');
                }
            });
        });

        if (ongoingSearch) {
            ongoingSearch.addEventListener('input', (event) => {
                const term = event.target.value.trim().toLowerCase();
                const url = new URL(window.location.href);

                if (term !== '') {
                    url.searchParams.set('search', term);
                } else {
                    url.searchParams.delete('search');
                }

                history.replaceState({}, '', url);

                document.querySelectorAll('[data-accordion]').forEach((card) => {
                    const text = card.dataset.searchText || '';
                    card.classList.toggle('d-none', term !== '' && !text.includes(term));
                });
            });

            if (ongoingSearch.value.trim() !== '') {
                ongoingSearch.dispatchEvent(new Event('input'));
            }
        }

        const openBookingId = {{ $openBookingId ?: 'null' }};
        if (openBookingId) {
            const card = document.querySelector(`[data-booking-id="${openBookingId}"]`);
            if (card) {
                card.classList.add('is-open');
                card.scrollIntoView({ block: 'start', behavior: 'smooth' });
            }
        }

        function updateTimers() {
            document.querySelectorAll('.timer').forEach((timer) => {
                const start = new Date(timer.dataset.start);
                const diff = Math.max(0, Math.floor((new Date() - start) / 1000));
                const bookedMinutes = Number(timer.dataset.bookedMinutes || 0);
                const bufferMinutes = Number(timer.dataset.bufferMinutes || 0);
                const elapsedMinutes = diff / 60;
                const hours = String(Math.floor(diff / 3600)).padStart(2, '0');
                const minutes = String(Math.floor((diff % 3600) / 60)).padStart(2, '0');
                const seconds = String(diff % 60).padStart(2, '0');

                timer.textContent = `${hours}:${minutes}:${seconds}`;
                timer.classList.remove('time-ontrack', 'time-warning', 'time-overdue');

                if (elapsedMinutes > bookedMinutes + bufferMinutes) {
                    timer.classList.add('time-overdue');
                } else if (elapsedMinutes > bookedMinutes) {
                    timer.classList.add('time-warning');
                } else {
                    timer.classList.add('time-ontrack');
                }
            });
        }

        updateTimers();
        setInterval(updateTimers, 1000);

            const scannerModal = document.createElement('div');
            scannerModal.className = 'scanner-modal';
            scannerModal.id = 'scannerModal';
            scannerModal.setAttribute('aria-hidden', 'true');
            scannerModal.innerHTML = `
                <div class="scanner-panel">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <h2 class="panel-title mb-1">Scan Barcode</h2>
                            <div class="panel-subtitle mb-0">Point the camera at the barcode to fill the vehicle number.</div>
                        </div>
                        <button type="button" class="btn btn-light-theme scanner-btn" id="closeScannerBtn" aria-label="Close scanner">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="scanner-video-wrap">
                        <video id="scannerVideo" class="scanner-video" autoplay muted playsinline></video>
                        <div class="scanner-frame"></div>
                    </div>
                    <div class="stack-sm mt-3">
                        <div class="alert alert-danger d-none mb-0 py-2" id="scannerError"></div>
                        <button type="button" class="btn btn-light-theme w-100" id="cancelScannerBtn">Cancel</button>
                    </div>
                </div>
            `;
            document.body.appendChild(scannerModal);

            const scannerVideo = document.getElementById('scannerVideo');
            const scannerError = document.getElementById('scannerError');
            const closeScannerBtn = document.getElementById('closeScannerBtn');
            const cancelScannerBtn = document.getElementById('cancelScannerBtn');
            let scannerStream = null;
            let scannerTargetInput = null;
            let scannerFrameId = null;
            let barcodeDetector = null;

            function showScannerError(message) {
                scannerError.textContent = message;
                scannerError.classList.remove('d-none');
            }

            function clearScannerError() {
                scannerError.textContent = '';
                scannerError.classList.add('d-none');
            }

            function stopScanner() {
                if (scannerFrameId) {
                    cancelAnimationFrame(scannerFrameId);
                    scannerFrameId = null;
                }

                if (scannerStream) {
                    scannerStream.getTracks().forEach((track) => track.stop());
                    scannerStream = null;
                }

                scannerVideo.srcObject = null;
                scannerModal.classList.remove('is-open');
                scannerModal.setAttribute('aria-hidden', 'true');
                scannerTargetInput = null;
                clearScannerError();
            }

            async function scanFrame() {
                if (!barcodeDetector || !scannerVideo || scannerVideo.readyState < 2) {
                    scannerFrameId = requestAnimationFrame(scanFrame);
                    return;
                }

                try {
                    const barcodes = await barcodeDetector.detect(scannerVideo);

                    if (barcodes.length > 0 && scannerTargetInput) {
                        const rawValue = (barcodes[0].rawValue || '').trim();

                        if (rawValue !== '') {
                            scannerTargetInput.value = rawValue;
                            scannerTargetInput.dispatchEvent(new Event('input', { bubbles: true }));
                            window.dispatchEvent(new CustomEvent('scooter:qr-scanned', {
                                detail: {
                                    value: rawValue,
                                    input: scannerTargetInput
                                }
                            }));
                            stopScanner();
                            return;
                        }
                    }
                } catch (error) {
                    showScannerError('Unable to read barcode from camera.');
                }

                scannerFrameId = requestAnimationFrame(scanFrame);
            }

            window.openScanner = async function openScanner(targetInputId) {
                clearScannerError();

                if (!('BarcodeDetector' in window)) {
                    showScannerError('Barcode scanning is not supported on this device/browser.');
                    scannerModal.classList.add('is-open');
                    scannerModal.setAttribute('aria-hidden', 'false');
                    return;
                }

                scannerTargetInput = document.getElementById(targetInputId);

                if (!scannerTargetInput) {
                    return;
                }

                try {
                    barcodeDetector = new BarcodeDetector({
                        formats: ['code_128', 'code_39', 'ean_13', 'ean_8', 'upc_a', 'upc_e', 'qr_code']
                    });

                    scannerStream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            facingMode: {
                                ideal: 'environment'
                            }
                        },
                        audio: false
                    });

                    scannerVideo.srcObject = scannerStream;
                    await scannerVideo.play();
                    scannerModal.classList.add('is-open');
                    scannerModal.setAttribute('aria-hidden', 'false');
                    scannerFrameId = requestAnimationFrame(scanFrame);
                } catch (error) {
                    showScannerError('Camera access failed. Please allow camera permission and try again.');
                    scannerModal.classList.add('is-open');
                    scannerModal.setAttribute('aria-hidden', 'false');
                }
            }

            document.querySelectorAll('.scan-trigger').forEach((button) => {
                button.addEventListener('click', () => {
                    openScanner(button.dataset.targetInput);
                });
            });

            closeScannerBtn.addEventListener('click', stopScanner);
            cancelScannerBtn.addEventListener('click', stopScanner);
            scannerModal.addEventListener('click', (event) => {
                if (event.target === scannerModal) {
                    stopScanner();
                }
            });
    </script>
</body>
</html>
