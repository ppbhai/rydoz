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
                            data-search-text="{{ strtolower($scooter->ride_number) }}">
                            <strong>{{ $scooter->ride_number }}</strong>
                            <span class="nearby-scooter-battery" data-level="good">
                                {{ $scooter->assign_count }} assign
                            </span>
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

            assignedSearch?.addEventListener('input', (event) => {
                const term = event.target.value.trim().toLowerCase();

                document.querySelectorAll('[data-assigned-scooter-row]').forEach((row) => {
                    row.classList.toggle('d-none', term !== '' && !(row.dataset.searchText || '').includes(term));
                });
            });
        });
    </script>
</body>

</html>
