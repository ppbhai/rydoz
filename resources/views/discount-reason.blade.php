@include('masterlayout.masterlayout', ['title' => 'Discount Reasons'])

<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="py-3 d-flex justify-content-between align-items-center">
                <h4 class="fs-18 fw-semibold m-0">Discount Reasons</h4>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card mb-3">
                <div class="card-body">
                    <form class="row g-3" action="{{ route('discount-reasons.store') }}" method="post">
                        @csrf
                        <div class="col-md-8">
                            <label class="form-label">Reason</label>
                            <input type="text" class="form-control" name="reason" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="is_active">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-primary w-100" type="submit">Add Reason</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Reason List</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="datatable" class="table table-bordered dt-responsive nowrap">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($reasons as $index => $reason)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $reason->reason }}</td>
                                        <td>{{ $reason->is_active ? 'Active' : 'Inactive' }}</td>
                                        <td>
                                            <a href="{{ route('discount-reasons.edit', $reason) }}" class="btn btn-sm btn-primary">Edit</a>
                                            <a href="{{ route('discount-reasons.destroy', $reason) }}" class="btn btn-sm btn-danger">Delete</a>
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
