@include('theme.partials.head', ['title' => 'Payment Details'])

<body>
    @include('theme.partials.header', [
        'title' => 'Payment Details',
        'kicker' => 'Payments',
        'backUrl' => route('payments.collect'),
    ])

    <div class="app-shell">
        <div class="page-body">
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @php
                $vehicleSummary = $booking->rides
                    ->groupBy('vehicle_name')
                    ->map(
                        fn($rides, $vehicleName) => '<span class="summary-item" style="font-size: 15px; color:black">' .
                            e($vehicleName) .
                            ' <span class="header-status-pill" style="color:black">' .
                            $rides->count() .
                            '</span></span>',
                    )
                    ->values()
                    ->implode('');
            @endphp

            <div class="accordion-card soft-accent-card mb-3">
                <div class="accordion-toggle">
                    <div>
                        <div
                            style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                            <span>
                                {{ $booking->name }}@if ($booking->document_type)
                                    ({{ $booking->document_type }})
                                @endif
                            </span>
                        </div>
                        <div class="text-muted small">{{ $booking->mobile }}</div>
                        <div class="booking-meta">
                            <span>{{ $booking->updated_at ? $booking->updated_at->format('d F Y, h:i A') : '-' }}</span>
                        </div>
                    </div>
                    <div class="booking-summary">
                        {!! $vehicleSummary !!}
                        <p style="font-weight: bold; font-size:15px; color:#FE5100;">
                            {{ number_format($subtotal, 0) }}/-</p>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('payments.store', $booking) }}" class="stack-sm" id="paymentForm">
                @csrf

                @foreach ($payableRides as $ride)
                    <div class="summary-card ride-payment-card" data-ride-id="{{ $ride->id }}"
                        data-ride-charge="{{ (float) $ride->charge }}">
                        <div
                            style="display:flex; justify-content:space-between; gap:12px; align-items:center; flex-wrap:wrap;">
                            <div class="assign-name">{{ $ride->vehicle_name }}</div>
                            <div class="assign-name" style="font-weight: normal">
                                {{ $ride->ride_number ?: 'No Number' }}</div>
                        </div>
                        <div class="assign-times" style="display:flex; flex-direction:column; gap:6px;">
                            <span style="font-weight: normal">Start time:
                                <strong style="font-weight: normal">{{ $ride->start_time ? $ride->start_time->format('h:i A') : '-' }}</strong></span>
                            <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                                <span style="font-weight: normal">End time:
                                    <strong style="font-weight: normal">{{ $ride->end_time ? $ride->end_time->format('h:i A') : '-' }}</strong></span>
                                <span><strong
                                        style="color:black; font-weight:bold">{{ number_format((float) $ride->charge, 0) }}/-</strong></span>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="panel">
                    <div class="stack-sm">
                        <div style="display:flex; gap:8px;">
                            <div style="flex:1;">
                                <select class="form-select" name="discount_ride_id" id="discountRideSelect">
                                    <option value="">Dis. Ride</option>
                                    @foreach ($payableRides as $ride)
                                        <option value="{{ $ride->id }}" data-charge="{{ (float) $ride->charge }}"
                                            @selected(old('discount_ride_id') == $ride->id)>
                                            {{ $ride->vehicle_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div style="flex:1;">
                                <select class="form-select" name="discount_reason_id" id="discountReasonSelect">
                                    <option value="">Dis. Reason</option>
                                    @foreach ($discountReasons as $reason)
                                        <option value="{{ $reason->id }}" @selected(old('discount_reason_id') == $reason->id)>{{ $reason->reason }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <input type="number" class="form-control" name="discount_amount" id="discountAmountInput"
                                value="{{ old('discount_amount') }}" placeholder="Discount Amount" min="0"
                                step="0.01" disabled>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="mini-stat">
                        <div class="detail-row">
                            <strong style="font-size: 20px;">Total Payment</strong>
                            <strong><span id="finalTotalText" style="font-size: 20px; color:#FE5100">{{ number_format($subtotal, 0) }}/-</span></strong>
                        </div>
                    </div>
                </div>

                <div style="display:flex; justify-content:center; gap:5px;">
                    <button type="submit" class="btn btn-theme w-100" name="payment_method"
                        value="cash">Cash</button>
                    <button type="submit" class="btn btn-theme w-100" name="payment_method"
                        value="online">Online</button>
                </div>

                <a href="{{ route('payments.collect') }}" class="btn btn-theme w-100">Back</a>
            </form>
        </div>
    </div>

    <script>
        const discountRideSelect = document.getElementById('discountRideSelect');
        const discountReasonSelect = document.getElementById('discountReasonSelect');
        const discountAmountInput = document.getElementById('discountAmountInput');
        const discountAmountHelp = document.getElementById('discountAmountHelp');
        const finalTotalText = document.getElementById('finalTotalText');
        const paymentForm = document.getElementById('paymentForm');
        const paymentButtons = paymentForm ? Array.from(paymentForm.querySelectorAll('button[name="payment_method"]')) : [];
        const subtotal = Number({{ json_encode((float) $subtotal) }});
        let explicitPaymentSubmit = false;

        function updateDiscountInputState() {
            const hasRide = (discountRideSelect?.value || '').trim() !== '';
            const hasReason = (discountReasonSelect?.value || '').trim() !== '';
            const selectedOption = discountRideSelect?.selectedOptions?.[0];
            const selectedCharge = Number(selectedOption?.dataset?.charge || 0);
            const allowedMax = selectedCharge > 0 ? Math.min(selectedCharge, subtotal) : subtotal;
            const canEditDiscount = hasRide && hasReason;

            if (discountAmountInput) {
                discountAmountInput.disabled = !canEditDiscount;
                discountAmountInput.max = String(Math.round(allowedMax));

                if (canEditDiscount) {
                    discountAmountInput.placeholder = `Discount Amount (max ${Math.round(allowedMax)})`;
                } else {
                    discountAmountInput.placeholder = 'Discount Amount';
                    discountAmountInput.value = '';
                }
            }

            if (discountAmountHelp) {
                discountAmountHelp.textContent = canEditDiscount
                    ? `Discount amount cannot be greater than ${Math.round(allowedMax)}.`
                    : 'Select discount ride and reason to enter amount.';
            }
        }

        function updatePaymentTotals() {
            updateDiscountInputState();

            const selectedOption = discountRideSelect?.selectedOptions?.[0];
            const selectedCharge = Number(selectedOption?.dataset?.charge || 0);
            const rawDiscount = (discountAmountInput?.value || '').trim();
            let discount = Number(rawDiscount || 0);
            const hasRide = (discountRideSelect?.value || '').trim() !== '';
            const hasReason = (discountReasonSelect?.value || '').trim() !== '';
            const maxAllowedDiscount = selectedCharge > 0 ? Math.min(selectedCharge, subtotal) : subtotal;

            if (!hasRide || !hasReason || discountAmountInput?.disabled) {
                discount = 0;
            }

            if (discount < 0) {
                discount = 0;
            }

            if (discount > maxAllowedDiscount) {
                discount = maxAllowedDiscount;
                if (discountAmountInput) {
                    discountAmountInput.value = Math.round(maxAllowedDiscount).toString();
                }
            }

            if (discountAmountInput) {
                discountAmountInput.setCustomValidity(
                    discount > maxAllowedDiscount ? 'Discount amount cannot be greater than the total payment.' : '',
                );
            }

            if (finalTotalText) {
                finalTotalText.textContent = Math.round(Math.max(subtotal - discount, 0)).toString();
            }
        }

        discountRideSelect?.addEventListener('change', updatePaymentTotals);
        discountReasonSelect?.addEventListener('change', updatePaymentTotals);
        discountAmountInput?.addEventListener('input', updatePaymentTotals);

        paymentButtons.forEach((button) => {
            button.addEventListener('click', () => {
                explicitPaymentSubmit = true;
                setTimeout(() => {
                    explicitPaymentSubmit = false;
                }, 0);
            });
        });

        paymentForm?.addEventListener('submit', (event) => {
            if (!explicitPaymentSubmit) {
                event.preventDefault();
            }
        });

        paymentForm?.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && !event.target.closest('button[name="payment_method"]')) {
                event.preventDefault();
            }
        });

        updatePaymentTotals();
    </script>
</body>

</html>
