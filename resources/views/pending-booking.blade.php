@include('masterlayout.masterlayout', ['title' => 'Active Bookings'])

<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="py-3 d-flex justify-content-between align-items-center">
                <h4 class="fs-18 fw-semibold m-0">Active Bookings</h4>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('booking.pending') }}" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Branch</label>
                            <select name="branch_id" class="form-select">
                                <option value="all">All branches</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected(request('branch_id') == $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">From Date</label>
                            <input type="date" name="from_date" class="form-control form-control-lg" value="{{ request('from_date') }}" max="{{ today()->toDateString() }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">To Date</label>
                            <input type="date" name="to_date" class="form-control form-control-lg" value="{{ request('to_date') }}" max="{{ today()->toDateString() }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Go</button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('booking.pending.export', request()->query()) }}" class="btn btn-success w-100">Export</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Pending Booking List</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="datatable" class="table table-bordered dt-responsive nowrap">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Branch</th>
                                    <th>Name</th>
                                    <th>Mobile</th>
                                    <th>Sent OTP</th>
                                    <th>Status</th>
                                    <th>Vehicles</th>
                                    <th>ID Proof</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($bookings as $index => $booking)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $booking->branchRelation?->name ?: $booking->branch_name ?: $booking->branch ?: '-' }}</td>
                                        <td>{{ $booking->name }}</td>
                                        <td>{{ $booking->mobile }}</td>
                                        <td>{{ $booking->otp ?: '-' }}</td>
                                        <td>{{ ucwords(str_replace('_', ' ', $booking->status)) }}</td>
                                        <td>
                                            @foreach ($booking->rides->groupBy('vehicle_name') as $vehicleName => $rides)
                                                <div>{{ $vehicleName }} x {{ $rides->count() }}</div>
                                            @endforeach
                                        </td>
                                        <td>{{ $booking->document_type ?: 'Not selected' }}</td>
                                        <td>{{ $booking->created_at?->format('d M Y h:i A') }}</td>
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
