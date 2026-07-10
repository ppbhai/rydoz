@include('theme.partials.head', ['title' => 'Scooter Usage'])

<body>
    @include('theme.partials.header', [
        'title' => 'Scooter Usage',
        'kicker' => 'Last 24 hours',
        'backUrl' => route('index'),
    ])

    <div class="app-shell">
        <div class="page-body">
            <div class="panel nearby-scooters-panel">
                <div class="nearby-scooters-heading">
                    <h2 class="panel-title mb-0">Assigned Scooters - 24 Hours</h2>
                </div>

                <select class="form-select mb-3" data-assigned-vehicle-filter>
                    <option value="">All vehicles</option>
                    @foreach ($vehicles as $vehicle)
                        <option value="{{ $vehicle->id }}">{{ $vehicle->name }}</option>
                    @endforeach
                </select>

                <div class="scanner-field mb-3">
                    <div class="search-input-wrap flex-grow-1">
                        <i class="fas fa-search search-input-icon"></i>
                        <input type="text" class="form-control" id="assignedScooterSearch"
                            data-assigned-scooter-search placeholder="Search assigned scooter">
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
                            No scooter assigned in last 24 hours.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const assignedSearch = document.querySelector('[data-assigned-scooter-search]');
            const vehicleFilter = document.querySelector('[data-assigned-vehicle-filter]');

            function filterAssignedScooters() {
                const term = (assignedSearch?.value || '').trim().toLowerCase();
                const vehicleId = vehicleFilter?.value || '';

                document.querySelectorAll('[data-assigned-scooter-row]').forEach((row) => {
                    const matchesSearch = term === '' || (row.dataset.searchText || '').includes(term);
                    const matchesVehicle = vehicleId === '' || row.dataset.vehicleId === vehicleId;

                    row.classList.toggle('d-none', !matchesSearch || !matchesVehicle);
                });
            }

            assignedSearch?.addEventListener('input', filterAssignedScooters);
            vehicleFilter?.addEventListener('change', filterAssignedScooters);
        });
    </script>
</body>

</html>
