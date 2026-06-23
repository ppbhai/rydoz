@include('theme.partials.head', ['title' => 'Dashboard'])

<body>
    @include('theme.partials.header', [
        'title' => 'Dashboard',
        'kicker' => $branch->name,
        'leftUrl' => route('scooter.batteries'),
        'leftIcon' => 'fas fa-battery-three-quarters',
        'leftLabel' => 'Scooter batteries',
        'rightUrl' => route('userlogout'),
        'rightIcon' => 'fas fa-right-from-bracket',
        'rightLabel' => 'Logout',
    ])

    <div class="app-shell">
        <div class="page-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="metric-grid">
                <a href="{{ route('book') }}" class="metric-card"
                    style="min-height:48px; padding:12px 16px; align-items:center; justify-content:center; background:var(--brand); color:#fff; border-radius:999px;">
                    <div class="metric-value" style="color:#fff; font-size:1.5rem;">Book Ride</div>
                </a>
                <a href="{{ route('ongoing') }}" class="metric-card"
                    style="min-height:48px; padding:12px 16px; align-items:center; justify-content:center; background:var(--brand); color:#fff; border-radius:999px;">
                    <div class="metric-value" style="color:#fff; font-size:1.5rem;">On Going Ride</div>
                </a>
                <a href="{{ route('payments.collect') }}" class="metric-card"
                    style="min-height:48px; padding:12px 16px; align-items:center; justify-content:center; background:var(--brand); color:#fff; border-radius:999px;">
                    <div class="metric-value" style="color:#fff; font-size:1.5rem;">Collect Payment</div>
                </a>
                <a href="{{ route('payments.today') }}" class="metric-card"
                    style="min-height:40px; padding:10px 16px; align-items:center; justify-content:center; background:var(--brand); color:#fff; border-radius:999px;">
                    <div class="metric-value" style="color:#fff; font-size:1.2rem;">Todays Payment</div>
                </a>
                <a href="{{ route('bookings.completed') }}" class="metric-card"
                    style="min-height:40px; padding:10px 16px; align-items:center; justify-content:center; background:var(--brand); color:#fff; border-radius:999px;">
                    <div class="metric-value" style="color:#fff; font-size:1.2rem;">Completed Rides</div>
                </a>
                <a href="{{ route('scooter.usage') }}" class="metric-card"
                    style="min-height:40px; padding:10px 16px; align-items:center; justify-content:center; background:var(--brand); color:#fff; border-radius:999px;">
                    <div class="metric-value" style="color:#fff; font-size:1.2rem;">Scooter Usage</div>
                </a>
                @if ($branch->free_trial_enabled)
                    <a href="{{ route('free-trial') }}" class="metric-card"
                        style="min-height:40px; padding:10px 16px; align-items:center; justify-content:center; background:var(--brand); color:#fff; border-radius:999px;">
                        <div class="metric-value" style="color:#fff; font-size:1.2rem;">Free Trial</div>
                    </a>
                @endif
            </div>

            <div class="panel">
                <h2 class="panel-title" style="text-align: center">Requested Ride</h2>

                <div class="search-input-wrap mb-3">
                    <i class="fas fa-search search-input-icon"></i>
                    <input type="text" class="form-control" id="pendingAssignmentSearch" value="{{ $search }}"
                        placeholder="Search by customer, mobile, or vehicle">
                </div>

                <div class="stack-sm">
                    @forelse ($pendingAssignments as $booking)
                        @php
                            $vehicleSummary = $booking->rides
                                ->groupBy('vehicle_name')
                                ->map(function ($rides, $vehicleName) {
                                    return '<span class="summary-item" style="font-size: 15px; color:black">' .
                                        e($vehicleName) .
                                        ' <span class="header-status-pill" style="color:black">' .
                                        $rides->where('status', 'pending')->count() .
                                        '</span></span>';
                                })
                                ->values()
                                ->implode('');
                        @endphp

                        <div class="accordion-card soft-accent-card" data-accordion
                            data-booking-id="{{ $booking->id }}">
                            <button type="button" class="accordion-toggle"
                                data-search-text="{{ strtolower($booking->name . ' ' . $booking->mobile . ' ' . strip_tags($vehicleSummary)) }}">
                                <div>
                                    <div>{{ $booking->name }}</div>
                                    <div class="text-muted small">{{ $booking->mobile }}</div>
                                    <div class="booking-meta">
                                        <span>{{ $booking->created_at ? $booking->created_at->format('d F Y, h:i A') : '-' }}</span>
                                    </div>
                                </div>
                                <div class="booking-summary">
                                    {!! $vehicleSummary !!}
                                </div>
                            </button>

                            <div class="accordion-body">
                                <div class="compact-stack">
                                    @foreach ($booking->rides as $ride)
                                        <form method="POST" action="{{ route('ride.assign.single', $ride) }}"
                                            class="assign-row" data-iot-command="START">
                                            @csrf
                                            <input type="hidden" name="return_search" value="{{ $search }}">
                                            <input type="hidden" name="return_booking" value="{{ $booking->id }}">
                                            <input type="hidden" name="iot_battery_percent" value="" data-iot-battery-input>
                                            <div class="assign-row-top">
                                                <div class="assign-name">{{ $ride->vehicle_name }}</div>
                                                <div style="display: flex;">
                                                    @if ($branch->vehicle_number_required || $branch->scanner_enabled)
                                                        <input type="text"
                                                            class="form-control assign-input ride-number-input"
                                                            id="ride-number-{{ $ride->id }}" name="ride_number"
                                                            value="{{ $ride->ride_number }}"
                                                            @disabled($ride->status !== 'pending')
                                                            placeholder="Enter Id Number"
                                                            data-ride-number-input>
                                                    @endif
                                                    @if ($branch->scanner_enabled && $ride->status === 'pending')
                                                        <button type="button"
                                                            class="btn btn-light-theme scanner-btn scan-trigger"
                                                            data-target-input="ride-number-{{ $ride->id }}"
                                                            aria-label="Scan barcode">
                                                            <i class="fas fa-barcode"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                            @if ($ride->status === 'pending')
                                                <div class="assign-times">
                                                    <span><strong class="live-current-time"></strong></span>
                                                    <span><strong class="live-end-time"
                                                            data-duration-minutes="{{ $ride->vehicle_time }}"></strong></span>
                                                </div>
                                                <div class="scanner-field mt-2">
                                                    <input type="text"
                                                        class="form-control assign-input ride-number-input"
                                                        id="iot-device-{{ $ride->id }}"
                                                        value="{{ $ride->ride_number }}"
                                                        name="iot_device_id"
                                                        placeholder="IoT Device ID"
                                                        data-ride-number-target="ride-number-{{ $ride->id }}"
                                                        data-iot-device-input>
                                                    <button type="button"
                                                        class="btn btn-light-theme scanner-btn scan-trigger"
                                                        data-target-input="iot-device-{{ $ride->id }}"
                                                        data-iot-scan
                                                        data-iot-target="iot-device-{{ $ride->id }}"
                                                        aria-label="Scan IoT QR">
                                                        <i class="fas fa-bluetooth-b"></i>
                                                    </button>
                                                </div>
                                                <div class="iot-status small mt-1" data-iot-status>Scan IoT QR to connect Bluetooth.</div>
                                                <button type="submit" class="btn btn-theme w-100 mt-2">Assign
                                                    {{ $ride->vehicle_name }}</button>
                                            @else
                                                <div class="mt-2">
                                                    <span class="soft-pill">Assigned</span>
                                                </div>
                                            @endif
                                        </form>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state"></div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <script>
        const pendingAssignmentSearch = document.getElementById('pendingAssignmentSearch');
        const shouldRedirectBackToHome = @json((bool) session('booking_saved'));

        if (shouldRedirectBackToHome) {
            history.pushState({
                bookingSaved: true
            }, '', window.location.href);
            window.addEventListener('popstate', () => {
                window.location.replace(@json(route('index')));
            }, {
                once: true
            });
        }

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

        if (pendingAssignmentSearch) {
            pendingAssignmentSearch.addEventListener('input', (event) => {
                const term = event.target.value.trim().toLowerCase();
                const url = new URL(window.location.href);

                if (term !== '') {
                    url.searchParams.set('search', term);
                } else {
                    url.searchParams.delete('search');
                }

                history.replaceState({}, '', url);

                document.querySelectorAll('[data-accordion]').forEach((card) => {
                    const text = card.querySelector('.accordion-toggle').dataset.searchText || '';
                    card.classList.toggle('d-none', term !== '' && !text.includes(term));
                });
            });

            if (pendingAssignmentSearch.value.trim() !== '') {
                pendingAssignmentSearch.dispatchEvent(new Event('input'));
            }
        }

        const openBookingId = {{ $openBookingId ?: 'null' }};
        if (openBookingId) {
            const card = document.querySelector(`[data-booking-id="${openBookingId}"]`);
            if (card) {
                card.classList.add('is-open');
                card.scrollIntoView({
                    block: 'start',
                    behavior: 'smooth'
                });
            }
        }

        function updatePendingAssignmentTimes() {
            const now = new Date();

            document.querySelectorAll('.live-current-time').forEach((node) => {
                node.textContent = now.toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
            });

            document.querySelectorAll('.live-end-time').forEach((node) => {
                const durationMinutes = Number(node.dataset.durationMinutes || 0);
                const end = new Date(now.getTime() + (durationMinutes * 60000));
                node.textContent = end.toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
            });
        }

        updatePendingAssignmentTimes();
        setInterval(updatePendingAssignmentTimes, 1000);

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
