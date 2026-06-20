@include('masterlayout.masterlayout', ['title' => 'Dashboard'])

<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="py-3 d-flex align-items-center justify-content-between">
                <h4 class="fs-18 fw-semibold m-0">Dashboard</h4>
            </div>

            <div class="row g-3">
                <div class="col-md-6 col-xl-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Branches</h6>
                            <h3 class="mb-0">{{ number_format($totalBranches) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Branch Users</h6>
                            <h3 class="mb-0">{{ number_format($totalCustomers) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Active Bookings</h6>
                            <h3 class="mb-0">{{ number_format($taskPending) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Completed Bookings</h6>
                            <h3 class="mb-0">{{ number_format($totalDeals) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-6">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Collected Revenue</h6>
                            <h3 class="mb-0">Rs {{ number_format($totalRevenue, 2) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-6">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Vehicle Capacity</h6>
                            <h3 class="mb-0">{{ number_format($totalVehicles) }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3" data-live-dashboard>
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h5 class="card-title mb-0">Live Dashboard</h5>
                        <div class="text-muted small" data-live-reported-at>
                            {{ $liveDashboardStats['reported_at'] ? 'Last update: ' . $liveDashboardStats['reported_at'] : 'Waiting for live app update' }}
                        </div>
                    </div>
                    <div style="min-width: 220px;">
                        <select class="form-select" id="liveBranchSelect">
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected($selectedBranchId === $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6 col-xl">
                            <div class="border rounded p-3 h-100">
                                <h6 class="text-muted mb-2">Total Scooter</h6>
                                <h3 class="mb-0" data-live-stat="total_scooters">{{ number_format($liveDashboardStats['total_scooters']) }}</h3>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl">
                            <div class="border rounded p-3 h-100">
                                <h6 class="text-muted mb-2">Ongoing Ride</h6>
                                <h3 class="mb-0" data-live-stat="ongoing_rides">{{ number_format($liveDashboardStats['ongoing_rides']) }}</h3>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl">
                            <div class="border rounded p-3 h-100">
                                <h6 class="text-muted mb-2">Available Scooter</h6>
                                <h3 class="mb-0" data-live-stat="available_scooters">{{ number_format($liveDashboardStats['available_scooters']) }}</h3>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl">
                            <div class="border rounded p-3 h-100">
                                <h6 class="text-muted mb-2">Online Scooter</h6>
                                <h3 class="mb-0" data-live-stat="online_scooters">{{ number_format($liveDashboardStats['online_scooters']) }}</h3>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl">
                            <div class="border rounded p-3 h-100">
                                <h6 class="text-muted mb-2">Low Battery Scooter</h6>
                                <h3 class="mb-0" data-live-stat="low_battery_scooters">{{ number_format($liveDashboardStats['low_battery_scooters']) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const branchSelect = document.getElementById('liveBranchSelect');
        const reportedAt = document.querySelector('[data-live-reported-at]');
        const statsUrl = @json(route('dashboard.live-stats'));

        function setStat(key, value) {
            const target = document.querySelector(`[data-live-stat="${key}"]`);

            if (target) {
                target.textContent = new Intl.NumberFormat().format(Number(value || 0));
            }
        }

        async function refreshLiveDashboard() {
            if (!branchSelect) {
                return;
            }

            const url = new URL(statsUrl, window.location.origin);
            url.searchParams.set('branch_id', branchSelect.value);

            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json'
                }
            });
            const payload = await response.json();

            if (!payload.status || !payload.stats) {
                return;
            }

            Object.entries(payload.stats).forEach(([key, value]) => setStat(key, value));

            if (reportedAt) {
                reportedAt.textContent = payload.stats.reported_at
                    ? `Last update: ${payload.stats.reported_at}`
                    : 'Waiting for live app update';
            }
        }

        branchSelect?.addEventListener('change', refreshLiveDashboard);
        window.setInterval(refreshLiveDashboard, 60000);
    });
</script>
