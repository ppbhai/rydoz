@include('theme.partials.head', ['title' => 'Payment Method'])
<body>
    @include('theme.partials.header', [
        'title' => 'Payment Method',
        'kicker' => 'Payment',
        'backUrl' => route('payments.collect', $booking),
    ])

    <div class="app-shell">
        <div class="page-body">
            <div class="panel">
                <h2 class="panel-title">Booking Summary</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Customer</span>
                        <span class="info-value">{{ $booking->name }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Mobile</span>
                        <span class="info-value">{{ $booking->mobile }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Total</span>
                        <span class="info-value">Rs {{ number_format($subtotal, 0) }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Final Total</span>
                        <span class="info-value">Rs {{ number_format($finalTotal, 0) }}</span>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('payments.store', $booking) }}" class="panel stack-sm">
                @csrf
                <div>
                    <label class="form-label">Select Payment Method</label>
                    <select class="form-select" name="payment_method" required>
                        <option value="">Choose payment method</option>
                        <option value="cash">Cash</option>
                        <option value="online">Online</option>
                    </select>
                </div>
                <button class="btn btn-theme w-100" type="submit">Submit Payment</button>
            </form>
        </div>
    </div>
</body>
</html>
