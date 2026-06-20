@include('masterlayout.masterlayout', ['title' => 'Discounted Bookings'])

<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="py-3 d-flex justify-content-between align-items-center">
                <h4 class="fs-18 fw-semibold m-0">Discounted Bookings</h4>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('booking.discounted') }}" class="row g-3 align-items-end">
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
                            <a href="{{ route('booking.discounted.export', request()->query()) }}" class="btn btn-success w-100">Export</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Discounted Booking List</h5>
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
                                    <th>Vehicles</th>
                                    <th>Actual Time</th>
                                    <th>Total</th>
                                    <th>Discount</th>
                                    <th>Discount Reason</th>
                                    <th>Final</th>
                                    <th>Payment</th>
                                    <th>Paid At</th>
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
                                        <td>
                                            @foreach ($booking->rides as $ride)
                                                <div>{{ $ride->vehicle_name }} x 1</div>
                                            @endforeach
                                        </td>
                                        <td>
                                            @foreach ($booking->rides as $ride)
                                                <div>{{ $ride->actual_minutes ? $ride->actual_minutes . ' min' : '-' }}</div>
                                            @endforeach
                                        </td>
                                        <td>{{ number_format($booking->total_amount, 0) }}</td>
                                        <td>{{ number_format($booking->discount_amount, 0) }}</td>
                                        <td>
                                            @php
                                                $discountReasons = $booking->rides
                                                    ->filter(fn ($ride) => (float) $ride->discount_amount > 0 && filled($ride->discount_reason))
                                                    ->pluck('discount_reason')
                                                    ->unique()
                                                    ->values()
                                                    ->implode(', ');
                                            @endphp
                                            {{ $discountReasons ?: ($booking->discount_reason ?: '-') }}
                                        </td>
                                        <td>{{ number_format($booking->final_amount, 0) }}</td>
                                        <td>{{ $booking->payment_method ?: '-' }}</td>
                                        <td>{{ $booking->paid_at?->format('d M Y h:i A') }}</td>
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
