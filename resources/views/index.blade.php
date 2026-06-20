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
        </div>
    </div>
</div>
