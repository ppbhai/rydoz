@include('theme.partials.head', ['title' => 'Rydoz Battery'])

<body>
    @include('theme.partials.header', [
        'title' => 'Rydoz Battery',
        'kicker' => 'Nearby devices',
        'backUrl' => route('index'),
    ])

    <div class="app-shell">
        <div class="page-body">
            <div class="panel nearby-scooters-panel">
                <div class="nearby-scooters-heading">
                    <div class="vehicle-tabs" data-nearby-vehicle-tabs>
                        <button type="button" class="vehicle-tab is-active" data-vehicle-tab data-vehicle-name="">All</button>
                        @foreach ($vehicles as $vehicle)
                            <button type="button" class="vehicle-tab" data-vehicle-tab
                                data-vehicle-name="{{ $vehicle->name }}">{{ $vehicle->name }}</button>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-light-theme nearby-scan-retry"
                        data-nearby-scan-retry aria-label="Refresh nearby scooters">
                        <i class="fas fa-rotate" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="scanner-field mb-3">
                    <div class="search-input-wrap flex-grow-1">
                        <i class="fas fa-search search-input-icon"></i>
                        <input type="text" class="form-control" id="nearbyScooterSearch"
                            data-nearby-scooter-search placeholder="Search scooter">
                    </div>
                    <button type="button" class="btn btn-light-theme scanner-btn scan-trigger" data-shared-scan
                        data-target-input="nearbyScooterSearch" aria-label="Scan scooter search">
                        <i class="fas fa-barcode"></i>
                    </button>
                    <button type="button" class="btn btn-light-theme scanner-btn" data-nearby-sort-toggle
                        data-sort-direction="asc" aria-label="Sort by battery">
                        <i class="fas fa-arrow-down-short-wide" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="nearby-scooter-empty" data-nearby-scooter-empty>
                    Searching for powered scooters...
                </div>
                <div class="nearby-scooter-list" data-nearby-scooter-list></div>
            </div>

        </div>
    </div>

    <script>
        window.RydozScooterVehicleMap = @json($scooterVehicleMap);

        document.addEventListener('DOMContentLoaded', () => {
            const retryButton = document.querySelector('[data-nearby-scan-retry]');
            const message = document.querySelector('[data-nearby-scooter-empty]');
            const nearbySearch = document.querySelector('[data-nearby-scooter-search]');
            const sortToggle = document.querySelector('[data-nearby-sort-toggle]');

            function refreshNearbyScooters() {
                if (!message) {
                    return;
                }

                if (!window.ScooterIot || typeof window.ScooterIot.startNearbyScan !== 'function') {
                    message.hidden = false;
                    message.textContent = 'Open this page in the Android app to scan nearby scooters.';
                    return;
                }

                if (!window.ScooterBle || typeof window.ScooterBle.startNearbyScan !== 'function') {
                    message.hidden = false;
                    message.textContent = 'Update and reinstall the Android app to enable battery scanning.';
                    return;
                }

                try {
                    message.hidden = false;
                    message.textContent = 'Refreshing nearby scooters...';
                    window.ScooterIot.startNearbyScan();
                    window.setTimeout(() => {
                        if (message.textContent.trim() === 'Refreshing nearby scooters...') {
                            message.textContent = 'Scanning for powered scooters...';
                        }
                    }, 1200);
                } catch (error) {
                    message.textContent = `Could not start scan: ${error.message || error}`;
                }
            }

            nearbySearch?.addEventListener('input', () => {
                window.ScooterIot?.renderNearbyScooters?.();
            });

            document.querySelectorAll('[data-vehicle-tab]').forEach((tab) => {
                tab.addEventListener('click', () => {
                    document.querySelectorAll('[data-vehicle-tab]').forEach((btn) => btn.classList.remove('is-active'));
                    tab.classList.add('is-active');
                    window.ScooterIot?.renderNearbyScooters?.();
                });
            });

            sortToggle?.addEventListener('click', () => {
                const nextDirection = sortToggle.dataset.sortDirection === 'desc' ? 'asc' : 'desc';
                sortToggle.dataset.sortDirection = nextDirection;
                sortToggle.setAttribute('aria-label', nextDirection === 'desc' ? 'Sorted highest battery first' : 'Sorted lowest battery first');
                const icon = sortToggle.querySelector('i');
                icon?.classList.toggle('fa-arrow-down-short-wide', nextDirection === 'asc');
                icon?.classList.toggle('fa-arrow-up-wide-short', nextDirection === 'desc');
                window.ScooterIot?.renderNearbyScooters?.();
            });

            retryButton?.addEventListener('click', refreshNearbyScooters);
            window.setTimeout(refreshNearbyScooters, 500);
        });
    </script>
</body>

</html>
