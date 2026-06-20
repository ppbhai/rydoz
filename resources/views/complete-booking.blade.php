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
                                    <th>No.</th>
                                    <th>Branch</th>
                                    <th>Booking ID</th>
                                    <th>Date</th>
                                    <th>Name</th>
                                    <th>Mobile No.</th>
                                    <th>Vehicle</th>
                                    <th>Vehicle ID</th>
                                    <th>Assign Ride Time</th>
                                    <th>Actual Time</th>
                                    <th>Battery % Assign</th>
                                    <th>Battery % Complete</th>
                                    <th>Used Battery</th>
                                    <th>Total KM</th>
                                    <th>Average Speed</th>
                                    <th>Total Amount</th>
                                    <th>Discount</th>
                                    <th>Final Payment</th>
                                    <th>Payment Method</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $rowNumber = 1; @endphp
                                @foreach ($bookings as $booking)
                                    @foreach ($booking->rides as $ride)
                                        @php
                                            $rideFinalAmount = (float) ($ride->final_charge ?: $ride->charge);
                                            $usedBattery = $ride->assign_battery_percent !== null && $ride->complete_battery_percent !== null
                                                ? max(0, (int) $ride->assign_battery_percent - (int) $ride->complete_battery_percent)
                                                : null;
                                        @endphp
                                        <tr>
                                            <td>{{ $rowNumber++ }}</td>
                                            <td>{{ $booking->branchRelation?->name ?: $booking->branch_name ?: $booking->branch ?: '-' }}</td>
                                            <td>{{ $booking->id }}</td>
                                            <td>{{ ($booking->paid_at ?: $booking->created_at)?->format('d M Y h:i A') }}</td>
                                            <td>{{ $booking->name }}</td>
                                            <td>{{ $booking->mobile }}</td>
                                            <td>{{ $ride->vehicle_name ?: '-' }}</td>
                                            <td>{{ $ride->ride_number ?: '-' }}</td>
                                            <td>{{ $ride->start_time ? $ride->start_time->format('d M Y h:i A') : '-' }}</td>
                                            <td>{{ $ride->actual_minutes ? $ride->actual_minutes . ' min' : '-' }}</td>
                                            <td>{{ $ride->assign_battery_percent !== null ? $ride->assign_battery_percent . '%' : '-' }}</td>
                                            <td>{{ $ride->complete_battery_percent !== null ? $ride->complete_battery_percent . '%' : '-' }}</td>
                                            <td>{{ $usedBattery !== null ? $usedBattery . '%' : '-' }}</td>
                                            <td>{{ $ride->trip_distance_km !== null ? number_format((float) $ride->trip_distance_km, 3) . ' km' : '-' }}</td>
                                            <td>{{ $ride->average_speed_kph !== null ? number_format((float) $ride->average_speed_kph, 2) . ' km/h' : '-' }}</td>
                                            <td>{{ number_format($ride->charge, 0) }}</td>
                                            <td>{{ number_format($ride->discount_amount, 0) }}</td>
                                            <td>{{ number_format($rideFinalAmount, 0) }}</td>
                                            <td>{{ $booking->payment_method ?: '-' }}</td>
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
