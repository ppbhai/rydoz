@include('masterlayout.masterlayout', ['title' => 'Branch Vehicles'])

<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="py-3 d-flex justify-content-between align-items-center">
                <h4 class="fs-18 fw-semibold m-0">Branch Vehicles</h4>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card mb-3">
                <div class="card-body">
                    <form class="row g-3" action="{{ route('vehiclesave') }}" method="post">
                        @csrf
                        <div class="col-md-3">
                            <label class="form-label">Branch</label>
                            <select class="form-select" name="branch_id" required>
                                <option value="">Select branch</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Vehicle Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Price</label>
                            <input type="number" class="form-control" step="0.01" name="price" min="0" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Time (min)</label>
                            <input type="number" class="form-control" name="time" min="1" required>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary" type="submit">Add Vehicle</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Branch Vehicle List</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="datatable" class="table table-bordered dt-responsive nowrap">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Branch</th>
                                    <th>Vehicle</th>
                                    <th>Price</th>
                                    <th>Time</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($vehicles as $index => $vehicle)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $vehicle->branch?->name }}</td>
                                        <td>{{ $vehicle->name }}</td>
                                        <td>{{ $vehicle->price }}</td>
                                        <td>{{ $vehicle->time }} min</td>
                                        <td>
                                            <a href="{{ route('vehicleupdate', $vehicle) }}" class="btn btn-sm btn-primary">Edit</a>
                                            <a href="{{ route('vehicledelete', $vehicle) }}" class="btn btn-sm btn-danger">Delete</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
