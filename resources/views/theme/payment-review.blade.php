@include('theme.partials.head', ['title' => 'Collect Payment'])

<body>
    @include('theme.partials.header', [
        'title' => 'Collect Payment',
        'kicker' => 'Payments',
        'backUrl' => route('index'),
    ])

    <div class="app-shell">
        <div class="page-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="search-panel">
                <div class="search-input-wrap">
                    <i class="fas fa-search search-input-icon"></i>
                    <input type="text" class="form-control" id="paymentSearch" value="{{ $search }}"
                        placeholder="Search by customer, mobile, vehicle, or number">
                </div>
            </div>

            <div class="stack-sm" style="border-radius:10px">
                @forelse ($payments as $booking)
                    @php
                        $finishedRides = $booking->rides->where('status', 'finished')->values();
                        $subtotal = (float) $finishedRides->sum('charge');
                        $vehicleSummary = $booking->rides
                            ->groupBy('vehicle_name')
                            ->map(
                                fn(
                                    $rides,
                                    $vehicleName,
                                ) => '<span class="summary-item" style="font-size: 15px; color:black">' .
                                    e($vehicleName) .
                                    ' <span class="header-status-pill" style="color:black">' .
                                    $rides->count() .
                                    '</span></span>',
                            )
                            ->values()
                            ->implode('');
                    @endphp
                    <a href="{{ route('payments.show', $booking) }}" class="accordion-card soft-accent-card"
                        style="display:block;" data-payment-card
                        data-search-text="{{ strtolower($booking->name . ' ' . $booking->mobile . ' ' . strip_tags($vehicleSummary) . ' ' . $booking->rides->pluck('ride_number')->filter()->implode(' ')) }}">
                        <div class="accordion-toggle">
                            <div>
                                <div>{{ $booking->name }}@if ($booking->document_type)
                                        ({{ $booking->document_type }})
                                    @endif
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
                    </a>
                    @empty
                        <div class="empty-state">No booking is waiting for payment collection.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <script>
            const paymentSearch = document.getElementById('paymentSearch');
            const shouldRedirectBackToHome = @json((bool) session('payment_completed'));

            if (shouldRedirectBackToHome) {
                history.pushState({ paymentCompleted: true }, '', window.location.href);
                window.addEventListener('popstate', () => {
                    window.location.replace(@json(route('index')));
                }, { once: true });
            }

            if (paymentSearch) {
                paymentSearch.addEventListener('input', (event) => {
                    const term = event.target.value.trim().toLowerCase();
                    const url = new URL(window.location.href);

                    if (term !== '') {
                        url.searchParams.set('search', term);
                    } else {
                        url.searchParams.delete('search');
                    }

                    history.replaceState({}, '', url);

                    document.querySelectorAll('[data-payment-card]').forEach((card) => {
                        const text = card.dataset.searchText || '';
                        card.classList.toggle('d-none', term !== '' && !text.includes(term));
                    });
                });

                if (paymentSearch.value.trim() !== '') {
                    paymentSearch.dispatchEvent(new Event('input'));
                }
            }
        </script>
    </body>

    </html>
