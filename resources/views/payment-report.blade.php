@include('masterlayout.masterlayout', ['title' => 'Payment Report'])

<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="py-3 d-flex justify-content-between align-items-center">
                <h4 class="fs-18 fw-semibold m-0">Payment Report</h4>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('payment.report') }}" class="row g-3 align-items-end">
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
                            <button type="submit" class="btn btn-primary w-100">Load Report</button>
                        </div>
                        <div class="col-md-1">
                            <a href="{{ route('payment.report.export', request()->query()) }}" class="btn btn-success w-100">Export</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mb-3 overflow-auto">
                <div class="row g-3 flex-nowrap" style="min-width: max-content;">
                    <div class="col-auto" style="min-width: 220px;">
                        <div class="card"><div class="card-body"><h6 class="text-muted mb-2">Total Ride</h6><h4 class="mb-0">{{ number_format($totalRideCount) }}</h4></div></div>
                    </div>
                    @forelse ($vehicleBookingCounts as $vehicleName => $vehicleCount)
                        <div class="col-auto" style="min-width: 220px;">
                            <div class="card"><div class="card-body"><h6 class="text-muted mb-2">{{ $vehicleName }}</h6><h4 class="mb-0">{{ number_format($vehicleCount) }}</h4></div></div>
                        </div>
                    @empty
                        <div class="col-auto" style="min-width: 320px;">
                            <div class="card"><div class="card-body"><h6 class="text-muted mb-2">Vehicle Booking Number</h6><h4 class="mb-0">-</h4></div></div>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <div class="card"><div class="card-body"><h6 class="text-muted mb-2">Total Booking</h6><h4 class="mb-0">{{ number_format($totalBookingCount) }}</h4></div></div>
                </div>
                <div class="col-md-3">
                    <div class="card"><div class="card-body"><h6 class="text-muted mb-2">Pending Booking</h6><h4 class="mb-0">{{ number_format($pendingBookingCount) }}</h4></div></div>
                </div>
                <div class="col-md-3">
                    <div class="card"><div class="card-body"><h6 class="text-muted mb-2">Completed Booking</h6><h4 class="mb-0">{{ number_format($completedBookingCount) }}</h4></div></div>
                </div>
                <div class="col-md-3">
                    <div class="card"><div class="card-body"><h6 class="text-muted mb-2">Discounted Booking</h6><h4 class="mb-0">{{ number_format($discountedBookingCount) }}</h4></div></div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <div class="card"><div class="card-body"><h6 class="text-muted mb-2">Total Payment</h6><h4 class="mb-0">Rs {{ number_format($totalAmount, 0) }}</h4></div></div>
                </div>
                <div class="col-md-3">
                    <div class="card"><div class="card-body"><h6 class="text-muted mb-2">Cash Payment</h6><h4 class="mb-0">Rs {{ number_format($cashAmount, 0) }}</h4></div></div>
                </div>
                <div class="col-md-3">
                    <div class="card"><div class="card-body"><h6 class="text-muted mb-2">Online Payment</h6><h4 class="mb-0">Rs {{ number_format($onlineAmount, 0) }}</h4></div></div>
                </div>
                <div class="col-md-3">
                    <div class="card"><div class="card-body"><h6 class="text-muted mb-2">Discount Amount</h6><h4 class="mb-0">Rs {{ number_format($discountAmount, 0) }}</h4></div></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Payment Details</h5>
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
                                    <th>Total</th>
                                    <th>Discount</th>
                                    <th>Final</th>
                                    <th>Method</th>
                                    <th>Reason</th>
                                    <th>Paid At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $rowNumber = 1; @endphp
                                @foreach ($payments as $payment)
                                    @foreach ($payment->rides as $ride)
                                        @php
                                            $rideFinalAmount = (float) ($ride->final_charge ?: $ride->charge);
                                        @endphp
                                        <tr>
                                            <td>{{ $rowNumber++ }}</td>
                                            <td>{{ $payment->id }}</td>
                                            <td>{{ $payment->branchRelation?->name ?: $payment->branch_name ?: $payment->branch ?: '-' }}</td>
                                            <td>{{ $payment->name }}</td>
                                            <td>{{ $payment->mobile }}</td>
                                            <td>{{ $ride->vehicle_name ?: '-' }}</td>
                                            <td>{{ $ride->ride_number ?: '-' }}</td>
                                            <td>{{ number_format($ride->charge, 0) }}</td>
                                            <td>{{ number_format($ride->discount_amount, 0) }}</td>
                                            <td>{{ number_format($rideFinalAmount, 0) }}</td>
                                            <td>{{ ucfirst($payment->payment_method ?: '-') }}</td>
                                            <td>{{ $ride->discount_reason ?: ($payment->discount_reason ?: '-') }}</td>
                                            <td>{{ $payment->paid_at?->format('d M Y h:i A') }}</td>
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
