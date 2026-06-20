@include('masterlayout.masterlayout', ['title' => 'Completed Bookings'])

<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="py-3 d-flex justify-content-between align-items-center">
                <h4 class="fs-18 fw-semibold m-0">Completed Bookings</h4>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('booking.list') }}" class="row g-3 align-items-end">
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
                        <div class="col-md-3">
                            <label class="form-label">To Date</label>
                            <input type="date" name="to_date" class="form-control form-control-lg" value="{{ request('to_date') }}" max="{{ today()->toDateString() }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Go</button>
                        </div>
                        <div class="col-md-1">
                            <a href="{{ route('booking.list.export', request()->query()) }}" class="btn btn-success w-100">Export</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Completed Booking List</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="datatable" class="table table-bordered dt-responsive nowrap">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Booking ID</th>
                                    <th>Branch</th>
                                    <th>Name</th>
                                    <th>Mobile</th>
                                    <th>Vehicle</th>
                                    <th>Vehicle ID</th>
                                    <th>Actual Time</th>
                                    <th>Total KM</th>
                                    <th>Avg Speed</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Discount</th>
                                    <th>Final</th>
                                    <th>Payment</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $rowNumber = 1; @endphp
                                @foreach ($bookings as $booking)
                                    @foreach ($booking->rides as $ride)
                                        @php
                                            $rideFinalAmount = (float) ($ride->final_charge ?: $ride->charge);
                                        @endphp
                                        <tr>
                                            <td>{{ $rowNumber++ }}</td>
                                            <td>{{ $booking->id }}</td>
                                            <td>{{ $booking->branchRelation?->name ?: $booking->branch_name ?: $booking->branch ?: '-' }}</td>
                                            <td>{{ $booking->name }}</td>
                                            <td>{{ $booking->mobile }}</td>
                                            <td>{{ $ride->vehicle_name ?: '-' }}</td>
                                            <td>{{ $ride->ride_number ?: '-' }}</td>
                                            <td>{{ $ride->actual_minutes ? $ride->actual_minutes . ' min' : '-' }}</td>
                                            <td>{{ $ride->trip_distance_km !== null ? number_format((float) $ride->trip_distance_km, 3) . ' km' : '-' }}</td>
                                            <td>{{ $ride->average_speed_kph !== null ? number_format((float) $ride->average_speed_kph, 2) . ' km/h' : '-' }}</td>
                                            <td>{{ ucwords(str_replace('_', ' ', $ride->status ?: $booking->status)) }}</td>
                                            <td>{{ number_format($ride->charge, 0) }}</td>
                                            <td>{{ number_format($ride->discount_amount, 0) }}</td>
                                            <td>{{ number_format($rideFinalAmount, 0) }}</td>
                                            <td>{{ $booking->payment_method ?: '-' }}</td>
                                            <td>{{ $booking->created_at?->format('d M Y h:i A') }}</td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
