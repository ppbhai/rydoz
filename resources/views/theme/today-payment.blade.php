@include('theme.partials.head', ['title' => 'Todays Payment'])
<body>
    @include('theme.partials.header', [
        'title' => 'Todays Payment',
        'kicker' => 'Summary',
        'backUrl' => route('index'),
    ])

    <div class="app-shell">
        <div class="page-body">
            <div class="panel">
                <h2 class="panel-title text-center">Today Payment</h2>
                <div class="table-card mt-3">
                    <table class="table" style="min-width:0;">
                        <thead>
                            <tr>
                                @forelse ($rideCounts as $rideName => $count)
                                    <th class="text-center">{{ $rideName }}</th>
                                @empty
                                    <th class="text-center">Vehicle</th>
                                @endforelse
                                <th class="text-center">Total Ride</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                @forelse ($rideCounts as $rideName => $count)
                                    <td class="text-center fw-semibold">{{ $count }}</td>
                                @empty
                                    <td class="text-center text-muted">0</td>
                                @endforelse
                                <td class="text-center fw-bold">{{ $totalRideCount }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel">
                <div class="table-card">
                    <table class="table" style="min-width:0;">
                        <thead>
                            <tr>
                                <th class="text-center">Total Amount</th>
                                <th class="text-center">Discount Amount</th>
                                <th class="text-center">Total Payment</th>
                                <th class="text-center">Cash</th>
                                <th class="text-center">Online</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center fw-semibold">{{ number_format($totalAmount, 0) }}</td>
                                <td class="text-center fw-semibold">{{ number_format($discountTotal, 0) }}</td>
                                <td class="text-center fw-semibold">{{ number_format($todayTotal, 0) }}</td>
                                <td class="text-center fw-semibold">{{ number_format($cashTotal, 0) }}</td>
                                <td class="text-center fw-semibold">{{ number_format($onlineTotal, 0) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <a href="{{ route('index') }}" class="btn btn-theme w-100">Back</a>
        </div>
    </div>
</body>
</html>
