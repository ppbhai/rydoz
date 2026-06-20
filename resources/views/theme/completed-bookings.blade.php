@include('theme.partials.head', ['title' => 'Completed Booking'])

<body>
    @include('theme.partials.header', [
        'title' => 'Completed Booking',
        'kicker' => 'Completed',
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
                    <input type="text" class="form-control" id="completedBookingSearch" value="{{ $search }}" placeholder="Search by customer, mobile, vehicle, or number">
                </div>
            </div>

            <div class="stack-sm">
                @forelse ($bookings as $booking)
                    @php
                        $completedRides = $booking->rides->where('status', 'completed')->values();
                        $vehicleSummary = $completedRides
                            ->groupBy('vehicle_name')
                            ->map(
                                fn($rides, $vehicleName) => '<span class="summary-item" style="font-size: 15px; color:black">' .
                                    e($vehicleName) .
                                    ' <span class="header-status-pill" style="color:black">' . $rides->count() . '</span></span>',
                            )
                            ->values()
                            ->implode('');
                        $bookingAmount = (float) $completedRides->sum(fn ($ride) => $ride->final_charge ?: $ride->charge);
                        $rideNumbers = $completedRides->pluck('ride_number')->filter()->implode(' ');
                    @endphp

                    <div class="accordion-card soft-accent-card" data-completed-card data-search-text="{{ strtolower($booking->id . ' ' . $booking->name . ' ' . $booking->mobile . ' ' . strip_tags($vehicleSummary) . ' ' . $rideNumbers) }}">
                        <div class="accordion-toggle">
                            <div>
                                <div>{{ $booking->name }}</div>
                                <div class="text-muted small">{{ $booking->mobile }}</div>
                                <div class="booking-meta">
                                    {{-- <span>Booking #{{ $booking->id }}</span> --}}
                                    <span>{{ $booking->paid_at ? $booking->paid_at->format('d F Y, h:i A') : '-' }}</span>
                                </div>
                            </div>
                            <div class="booking-summary">
                                {!! $vehicleSummary !!}
                                <p style="font-weight: bold; font-size:15px; color:#FE5100;">
                                    {{ number_format($bookingAmount, 0) }}/-</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">No completed booking found for this branch.</div>
                @endforelse
            </div>
        </div>
    </div>

    <script>
        const completedBookingSearch = document.getElementById('completedBookingSearch');

        if (completedBookingSearch) {
            completedBookingSearch.addEventListener('input', (event) => {
                const term = event.target.value.trim().toLowerCase();
                const url = new URL(window.location.href);

                if (term !== '') {
                    url.searchParams.set('search', term);
                } else {
                    url.searchParams.delete('search');
                }

                history.replaceState({}, '', url);

                document.querySelectorAll('[data-completed-card]').forEach((card) => {
                    const text = card.dataset.searchText || '';
                    card.classList.toggle('d-none', term !== '' && !text.includes(term));
                });
            });

            if (completedBookingSearch.value.trim() !== '') {
                completedBookingSearch.dispatchEvent(new Event('input'));
            }
        }
    </script>
</body>

</html>
