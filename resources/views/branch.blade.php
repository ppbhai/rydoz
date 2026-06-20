@include('masterlayout.masterlayout', ['title' => 'Branches'])

<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="py-3 d-flex justify-content-between align-items-center">
                <h4 class="fs-18 fw-semibold m-0">Branches</h4>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card mb-3">
                <div class="card-body">
                    <form class="row g-3" action="{{ route('branchsave') }}" method="post">
                        @csrf
                        <div class="col-md-3">
                            <label class="form-label">Branch Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Buffer Time (min)</label>
                            <input type="number" class="form-control" name="buffer_time" min="0" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Document Photo (Camera)</label>
                            <select class="form-select" name="photo_enabled">
                                <option value="0">Off</option>
                                <option value="1">On</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Vehicle Id Scanner</label>
                            <select class="form-select" name="scanner_enabled">
                                <option value="0">Off</option>
                                <option value="1">On</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Vehicle Id Number</label>
                            <select class="form-select" name="vehicle_number_required">
                                <option value="0">Off</option>
                                <option value="1">On</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Document Select</label>
                            <select class="form-select" name="document_select_enabled">
                                <option value="0">Off</option>
                                <option value="1">On</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary" type="submit">Add Branch</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Branch List</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="datatable" class="table table-bordered dt-responsive nowrap">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Buffer</th>
                                    <th>Photo</th>
                                    <th>Scanner</th>
                                    <th>Vehicle No.</th>
                                    <th>Document Select</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($branches as $index => $branch)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $branch->name }}</td>
                                        <td>{{ $branch->buffer_time }} min</td>
                                        <td>{{ $branch->photo_enabled ? 'On' : 'Off' }}</td>
                                        <td>{{ $branch->scanner_enabled ? 'On' : 'Off' }}</td>
                                        <td>{{ $branch->vehicle_number_required ? 'On' : 'Off' }}</td>
                                        <td>{{ $branch->document_select_enabled ? 'On' : 'Off' }}</td>
                                        <td>
                                            <a href="{{ route('branchupdate', $branch) }}" class="btn btn-sm btn-primary">Edit</a>
                                            <form action="{{ route('branchdelete.bookings', $branch) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete only booking data for this branch? Users, branch, and vehicle data will stay.');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-warning">Delete Data</button>
                                            </form>
                                            <a href="{{ route('branchdelete', $branch) }}" class="btn btn-sm btn-danger">Delete</a>
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
