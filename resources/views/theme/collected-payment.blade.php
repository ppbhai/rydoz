@include('theme.partials.head', ['title' => 'Collect Payment'])
<body>
    @include('theme.partials.header', [
        'title' => 'Collect Payment',
        'kicker' => 'Reports',
        'backUrl' => route('index'),
        'rightUrl' => route('payments.today'),
        'rightIcon' => 'fas fa-calendar-day',
        'rightLabel' => 'Today',
    ])

    <div class="app-shell">
        <div class="page-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="metric-grid">
                <div class="metric-card">
                    <span class="metric-icon"><i class="fas fa-wallet"></i></span>
                    <div>
                        <div class="metric-label">All Collected</div>
                        <div class="metric-value">Rs {{ number_format($totalCollected, 0) }}</div>
                    </div>
                </div>
                <div class="metric-card">
                    <span class="metric-icon"><i class="fas fa-money-bill-wave"></i></span>
                    <div>
                        <div class="metric-label">Cash</div>
                        <div class="metric-value">Rs {{ number_format($cashCollected, 0) }}</div>
                    </div>
                </div>
                <div class="metric-card">
                    <span class="metric-icon"><i class="fas fa-mobile-screen-button"></i></span>
                    <div>
                        <div class="metric-label">Online</div>
                        <div class="metric-value">Rs {{ number_format($onlineCollected, 0) }}</div>
                    </div>
                </div>
            </div>

            <form method="GET" action="{{ route('payments.collected') }}" class="search-panel">
                <div class="stack-sm">
                    <input
                        type="text"
                        class="form-control"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Search by customer, mobile, payment method, ride, or ride number">
                    <div class="d-grid gap-2" style="grid-template-columns: repeat(2, minmax(0, 1fr)); display:grid;">
                        <button class="btn btn-theme" type="submit">Search</button>
                        <a href="{{ route('payments.collected') }}" class="btn btn-light-theme">Reset</a>
                    </div>
                </div>
            </form>

            @forelse ($payments as $payment)
                <div class="panel">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <h2 class="panel-title mb-1">{{ $payment->name }}</h2>
                            <div class="panel-subtitle mb-0">{{ $payment->mobile }}</div>
                        </div>
                        <span class="soft-pill">Rs {{ number_format($payment->final_amount, 0) }}</span>
                    </div>

                    <div class="section-row mb-3">
                        <span class="soft-pill">{{ ucfirst($payment->payment_method) }}</span>
                        <span class="soft-pill">{{ $payment->paid_at ? $payment->paid_at->format('d F Y, h:i A') : '-' }}</span>
                    </div>

                    <div class="info-grid mb-3">
                        <div class="info-item">
                            <span class="info-label">Booking Date</span>
                            <span class="info-value">{{ $payment->created_at ? $payment->created_at->format('d F Y, h:i A') : '-' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Document Type</span>
                            <span class="info-value">{{ $payment->document_type ?: 'No ID proof' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Discount Reason</span>
                            <span class="info-value">{{ $payment->discount_reason ?: 'No discount reason' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Summary</span>
                            <span class="info-value">Total Rs {{ number_format($payment->total_amount, 0) }}, Discount Rs {{ number_format($payment->discount_amount, 0) }}</span>
                        </div>
                    </div>

                    <div class="table-card">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Ride</th>
                                    <th>Number</th>
                                    <th>Qty</th>
                                    <th>Requested</th>
                                    <th>Actual</th>
                                    <th>Start</th>
                                    <th>End</th>
                                    <th>Charge</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $rideIndexByVehicle = []; @endphp
                                @foreach ($payment->rides as $ride)
                                    @php
                                        $rideIndexByVehicle[$ride->vehicle_name] = ($rideIndexByVehicle[$ride->vehicle_name] ?? 0) + 1;
                                        $unitNumber = $rideIndexByVehicle[$ride->vehicle_name];
                                        $actualMinutes = (int) ($ride->actual_minutes ?? 0);
                                        $timeClass = 'time-ontrack';

                                        if ($actualMinutes > ($ride->requested_minutes + $bufferTime)) {
                                            $timeClass = 'time-overdue';
                                        } elseif ($actualMinutes > $ride->requested_minutes) {
                                            $timeClass = 'time-warning';
                                        }
                                    @endphp
                                    <tr>
                                        <td>{{ $ride->vehicle_name }} <span class="text-muted small">#{{ $unitNumber }}</span></td>
                                        <td>{{ $ride->ride_number ?: '-' }}</td>
                                        <td>{{ $ride->qty }}</td>
                                        <td><span class="time-indicator {{ $timeClass }}">{{ $ride->requested_minutes }} min</span></td>
                                        <td><span class="time-indicator {{ $timeClass }}">{{ $ride->actual_minutes ?: 0 }} min</span></td>
                                        <td>{{ $ride->start_time ? $ride->start_time->format('d F Y, h:i A') : '-' }}</td>
                                        <td>
                                            @if ($ride->end_time)
                                                <span class="time-indicator {{ $timeClass }}">{{ $ride->end_time->format('d F Y, h:i A') }}</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>Rs {{ number_format($ride->charge, 0) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="panel">
                    <div class="empty-state">No Collect Payments found for this search.</div>
                </div>
            @endforelse
        </div>
    </div>
</body>
</html>
