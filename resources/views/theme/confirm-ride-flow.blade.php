@include('theme.partials.head', ['title' => 'Confirm Ride'])

<body>
    @include('theme.partials.header', [
        'title' => 'Confirm Ride',
        'kicker' => $branch->name,
        'backUrl' => route('book'),
    ])

    <div class="app-shell">
        <div class="page-body">
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('save-booking', $booking) }}" enctype="multipart/form-data"
                id="confirmForm" class="stack-sm" data-photo-required="{{ $branch->photo_enabled ? '1' : '0' }}">
                @csrf

                {{-- <div class="panel">
                    <div class="detail-row"><span>Name</span><strong>{{ $booking->name }}</strong></div>
                    <div class="detail-row"><span>Mobile</span><strong>{{ $booking->mobile }}</strong></div>
                </div> --}}

                <div class="panel">
                    {{-- <h2 class="panel-title">ID Proof</h2> --}}
                    <div class="stack-sm">
                        <style>
                            .custom-select {
                                text-align: center;
                                /* center text */
                                font-weight: bold;
                                /* bold text */
                                color: black;
                                /* black text */
                            }

                            /* Optional: try styling dropdown options (limited support) */
                            .custom-select option {
                                text-align: center;
                                font-weight: bold;
                                color: black;
                            }
                        </style>
                        @if ($branch->document_select_enabled)
                            <div>
                                <select class="form-select custom-select" id="documentTypeSelect" name="document_type"
                                    required>
                                    <option value="">ID Proof</option>
                                    <option value="Aadhar Card">Aadhar Card</option>
                                    <option value="Driving License">Driving License</option>
                                    <option value="PAN Card">PAN Card</option>
                                    <option value="Passport">Passport</option>
                                    <option value="Voter ID">Voter ID</option>
                                </select>
                            </div>
                        @endif
                        @if ($branch->photo_enabled)
                            <div>
                                <button type="button" class="btn btn-light-theme w-100" id="capturePhotoBtn"
                                    data-requires-id-proof="{{ $branch->document_select_enabled ? '1' : '0' }}"
                                    @if ($branch->document_select_enabled) disabled aria-disabled="true" @endif>Click
                                    Photo</button>
                                <input type="file" class="d-none" id="proofImageInput" name="proof_image"
                                    accept="image/*" capture="environment"
                                    @if ($branch->document_select_enabled) disabled @endif>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="panel">
                    <div class="stack-sm">
                        @forelse ($vehicles as $vehicle)
                            <div class="vehicle-option" data-vehicle-card data-id="{{ $vehicle->id }}"
                                style="display: flex; justify-content:space-between; align-items:center">
                                <button type="button"
                                    class="btn btn-light-theme w-50 vehicle-select-btn d-flex align-items-center justify-content-between"
                                    data-vehicle-toggle>
                                    <span class="fw-semibold">{{ $vehicle->name }}</span>
                                </button>
                                <div class="mt-2 d-none" data-qty-wrap>
                                    <select class="form-select form-select-sm" data-qty-select>
                                        @for ($qty = 1; $qty <= 9; $qty++)
                                            <option value="{{ $qty }}" @selected($qty === 1)>
                                                {{ $qty }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">No vehicle is added for this branch yet.</div>
                        @endforelse
                    </div>
                </div>

                <input type="hidden" name="vehicles_json" id="vehiclesJson">
                <button type="submit" class="btn btn-theme w-100" style="font-size: 1rem">Submit</button>
            </form>
        </div>
    </div>

    <script>
        const selections = {};

        document.querySelectorAll('[data-vehicle-card]').forEach((card) => {
            const toggleButton = card.querySelector('[data-vehicle-toggle]');
            const qtySelect = card.querySelector('[data-qty-select]');
            const qtyWrap = card.querySelector('[data-qty-wrap]');

            toggleButton.addEventListener('click', () => {
                const isSelected = card.classList.toggle('is-selected');
                qtyWrap.classList.toggle('d-none', !isSelected);
                toggleButton.classList.toggle('btn-theme', isSelected);
                toggleButton.classList.toggle('btn-light-theme', !isSelected);

                if (!isSelected) {
                    delete selections[card.dataset.id];
                    return;
                }

                selections[card.dataset.id] = {
                    branch_vehicle_id: card.dataset.id,
                    qty: Number(qtySelect.value)
                };
            });

            qtySelect.addEventListener('change', () => {
                if (!card.classList.contains('is-selected')) {
                    return;
                }

                selections[card.dataset.id] = {
                    branch_vehicle_id: card.dataset.id,
                    qty: Number(qtySelect.value)
                };
            });
        });

        document.getElementById('confirmForm').addEventListener('submit', (event) => {
            const selected = Object.values(selections);

            if (!selected.length) {
                event.preventDefault();
                if (window.showAppToast) {
                    window.showAppToast('Select vehicle first.', 'error');
                }
                return;
            }

            if (event.currentTarget.dataset.photoRequired === '1' && (!proofImageInput || !proofImageInput.files.length)) {
                event.preventDefault();
                if (window.showAppToast) {
                    window.showAppToast('Click photo first.', 'error');
                }
                return;
            }

            document.getElementById('vehiclesJson').value = JSON.stringify(selected);
        });

        const capturePhotoBtn = document.getElementById('capturePhotoBtn');
        const proofImageInput = document.getElementById('proofImageInput');
        const documentTypeSelect = document.getElementById('documentTypeSelect');

        const idProofSelected = () => !documentTypeSelect || documentTypeSelect.value.trim() !== '';
        const syncPhotoButton = () => {
            if (!capturePhotoBtn || !proofImageInput) {
                return;
            }

            const shouldDisablePhoto = capturePhotoBtn.dataset.requiresIdProof === '1' && !idProofSelected();
            capturePhotoBtn.disabled = shouldDisablePhoto;
            capturePhotoBtn.setAttribute('aria-disabled', shouldDisablePhoto ? 'true' : 'false');
            proofImageInput.disabled = shouldDisablePhoto;

            if (shouldDisablePhoto) {
                proofImageInput.value = '';
            }
        };

        if (documentTypeSelect) {
            documentTypeSelect.addEventListener('change', syncPhotoButton);
        }

        if (capturePhotoBtn) {
            capturePhotoBtn.addEventListener('click', (event) => {
                syncPhotoButton();

                if (capturePhotoBtn.disabled || !proofImageInput || proofImageInput.disabled) {
                    event.preventDefault();
                    return;
                }

                proofImageInput.click();
            });
        }

        syncPhotoButton();
    </script>
</body>

</html>
