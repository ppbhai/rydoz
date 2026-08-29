@include('masterlayout.masterlayout', ['title' => 'Free Trials'])

<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="py-3 d-flex justify-content-between align-items-center">
                <h4 class="fs-18 fw-semibold m-0">Free Trials</h4>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('free-trials.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Branch</label>
                            <select name="branch_id" id="freeTrialBranchSelect" class="form-select">
                                <option value="all" @selected(!request()->filled('branch_id') || request('branch_id') === 'all')>All branches</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected(request('branch_id') == $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Vehicle</label>
                            <select name="vehicle_name" id="freeTrialVehicleSelect" class="form-select">
                                <option value="all">All vehicles</option>
                                @foreach ($allVehicleNames as $vehicleName)
                                    <option value="{{ $vehicleName }}" @selected(request('vehicle_name') === $vehicleName)>{{ $vehicleName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">From Date</label>
                            <input type="date" name="from_date" class="form-control form-control-lg" value="{{ request('from_date') }}" max="{{ today()->toDateString() }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">To Date</label>
                            <input type="date" name="to_date" class="form-control form-control-lg" value="{{ request('to_date') }}" max="{{ today()->toDateString() }}">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100">Go</button>
                        </div>
                        <div class="col-md-1">
                            <a href="{{ route('free-trials.export', request()->query()) }}" class="btn btn-success w-100">Export</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Free Trial List</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="datatable" class="table table-bordered dt-responsive nowrap">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Branch</th>
                                    <th>Vehicle</th>
                                    <th>Vehicle ID</th>
                                    <th>Assigned At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $rowNumber = 1; @endphp
                                @forelse ($freeTrials as $freeTrial)
                                    <tr>
                                        <td>{{ $rowNumber++ }}</td>
                                        <td>{{ $freeTrial->branch?->name ?: '-' }}</td>
                                        <td>{{ $freeTrial->vehicle_name ?: '-' }}</td>
                                        <td>{{ $freeTrial->scooter_id ?: '-' }}</td>
                                        <td>{{ $freeTrial->assigned_at?->format('d M Y h:i A') ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">No free trial found for the selected filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const vehiclesByBranch = @json($vehicleNamesByBranch);
        const allVehicleNames = @json($allVehicleNames);
        const branchSelect = document.getElementById('freeTrialBranchSelect');
        const vehicleSelect = document.getElementById('freeTrialVehicleSelect');
        const selectedVehicleName = @json(request('vehicle_name'));

        function renderVehicleOptions(branchId, selected) {
            if (!vehicleSelect) {
                return;
            }

            const names = branchId && branchId !== 'all'
                ? (vehiclesByBranch[branchId] || [])
                : allVehicleNames;

            vehicleSelect.innerHTML = '';

            const allOption = document.createElement('option');
            allOption.value = 'all';
            allOption.textContent = 'All vehicles';
            vehicleSelect.appendChild(allOption);

            names.forEach((name) => {
                const option = document.createElement('option');
                option.value = name;
                option.textContent = name;
                vehicleSelect.appendChild(option);
            });

            vehicleSelect.value = names.includes(selected) ? selected : 'all';
        }

        branchSelect?.addEventListener('change', () => {
            renderVehicleOptions(branchSelect.value, null);
        });

        renderVehicleOptions(branchSelect?.value, selectedVehicleName);
    });
</script>
