@include('theme.partials.head', ['title' => 'Vehicle Usage'])

<body>
    @include('theme.partials.header', [
        'title' => 'Vehicle Usage',
        'kicker' => 'Today',
        'backUrl' => route('index'),
    ])

    <div class="app-shell">
        <div class="page-body">
            <div class="panel nearby-scooters-panel">
                <div class="nearby-scooters-heading">
                    <div class="vehicle-tabs" data-assigned-vehicle-tabs>
                        <button type="button" class="vehicle-tab is-active" data-vehicle-tab data-vehicle-id="">All</button>
                        @foreach ($vehicles as $vehicle)
                            <button type="button" class="vehicle-tab" data-vehicle-tab
                                data-vehicle-id="{{ $vehicle->id }}">{{ $vehicle->name }}</button>
                        @endforeach
                    </div>
                </div>

                <div class="scanner-field mb-3">
                    <div class="search-input-wrap flex-grow-1">
                        <i class="fas fa-search search-input-icon"></i>
                        <input type="text" class="form-control" id="assignedScooterSearch"
                            data-assigned-scooter-search placeholder="Search Vehicle">
                    </div>
                    <button type="button" class="btn btn-light-theme scanner-btn scan-trigger" data-shared-scan
                        data-target-input="assignedScooterSearch" aria-label="Scan scooter search">
                        <i class="fas fa-barcode"></i>
                    </button>
                </div>

                <div class="nearby-scooter-list" data-assigned-scooter-list>
                    @forelse ($assignedScooters as $scooter)
                        <div class="nearby-scooter-row" data-assigned-scooter-row
                            data-vehicle-id="{{ $scooter->branch_vehicle_id }}"
                            data-assign-count="{{ $scooter->assign_count }}"
                            data-ongoing="{{ $scooter->usage_status === 'ongoing' ? '1' : '0' }}"
                            data-search-text="{{ strtolower($scooter->ride_number . ' ' . $scooter->usage_status) }}">
                            <strong>{{ $scooter->ride_number }}</strong>
                            <div class="d-flex align-items-center gap-5">
                                <span class="nearby-scooter-battery" data-level="good">
                                    {{ $scooter->assign_count }}
                                </span>
                                <span class="nearby-scooter-battery"
                                    data-level="{{ $scooter->usage_status === 'ongoing' ? 'medium' : 'good' }}">
                                    {{ ucfirst($scooter->usage_status) }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="nearby-scooter-empty">
                            No scooter assigned today.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const assignedSearch = document.querySelector('[data-assigned-scooter-search]');

            function filterAssignedScooters() {
                const term = (assignedSearch?.value || '').trim().toLowerCase();
                const activeTab = document.querySelector('[data-vehicle-tab].is-active');
                const vehicleId = activeTab?.dataset.vehicleId || '';

                document.querySelectorAll('[data-assigned-scooter-row]').forEach((row) => {
                    const matchesSearch = term === '' || (row.dataset.searchText || '').includes(term);
                    const matchesVehicle = vehicleId === '' || row.dataset.vehicleId === vehicleId;

                    row.classList.toggle('d-none', !matchesSearch || !matchesVehicle);
                });
            }

            assignedSearch?.addEventListener('input', filterAssignedScooters);

            document.querySelectorAll('[data-vehicle-tab]').forEach((tab) => {
                tab.addEventListener('click', () => {
                    document.querySelectorAll('[data-vehicle-tab]').forEach((btn) => btn.classList.remove('is-active'));
                    tab.classList.add('is-active');
                    filterAssignedScooters();
                });
            });
        });
    </script>
</body>

</html>
