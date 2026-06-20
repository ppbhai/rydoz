@include('theme.partials.head', ['title' => 'Book Ride'])

<body>
    @include('theme.partials.header', [
        'title' => 'Book Ride',
        'kicker' => 'Customer',
        'backUrl' => route('index'),
    ])

    <div class="app-shell">
        <div class="page-body">
            <div id="message"></div>

            <div class="panel">
                <div class="stack-sm">
                    <div style="display: flex; align-items:end; justify-content:space-between">
                        <div>
                            <input type="tel" class="form-control" id="mobile" maxlength="10"
                                placeholder="Mobile No." pattern="[0-9]*" inputmode="numeric" style="width: 150px">
                        </div>
                        <button type="button" class="btn btn-theme d-none" id="findBtn" style="width: 140px; height:45px">Find</button>
                    </div>
                    <div style="text-align: center">
                        <div class="d-none" style="text-align: left" id="nameWrap">
                            <input type="text" class="form-control" id="name" placeholder="Enter customer name">
                        </div>
                        <button type="button" class="btn btn-theme d-none" id="sendOtpBtn"
                            style="margin-top: 10px">Send OTP</button>
                    </div>
                    <div style="text-align: center">
                        <div class="d-none" id="otpWrap" style="text-align: left">
                            <input type="tel" class="form-control" id="otp" maxlength="5"
                                placeholder="Enter OTP">
                        </div>
                        <button type="button" class="btn btn-theme d-none" id="verifyOtpBtn"  style="margin-top: 10px">Verify OTP</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let bookingId = null;

        function flash(message, type = 'success') {
            if (window.showAppToast) {
                window.showAppToast(message, type === 'success' ? 'success' : 'error');
            }
        }

        function toggleFind() {
            const mobile = document.getElementById('mobile').value.replace(/\D/g, '').slice(0, 10);
            document.getElementById('mobile').value = mobile;
            document.getElementById('findBtn').classList.toggle('d-none', mobile.length !== 10);
        }

        async function findCustomer() {
            const mobile = document.getElementById('mobile').value.trim();
            const response = await fetch(`{{ route('booking-customer') }}?mobile=${mobile}`);
            const data = await response.json();

            document.getElementById('nameWrap').classList.remove('d-none');
            document.getElementById('sendOtpBtn').classList.remove('d-none');

            if (data.name) {
                document.getElementById('name').value = data.name;
            }
        }

        document.getElementById('mobile').addEventListener('input', toggleFind);
        document.getElementById('findBtn').addEventListener('click', findCustomer);

        document.getElementById('sendOtpBtn').addEventListener('click', async () => {
            const sendOtpBtn = document.getElementById('sendOtpBtn');
            const mobile = document.getElementById('mobile').value.trim();
            const name = document.getElementById('name').value.trim();

            if (mobile.length !== 10 || !name) {
                flash('Enter valid mobile number and name.', 'danger');
                return;
            }

            sendOtpBtn.disabled = true;
            sendOtpBtn.textContent = 'Sending...';

            try {
                const response = await fetch(`{{ route('send-otp') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        mobile,
                        name
                    })
                });

                const data = await response.json();

                if (!response.ok || !data.status) {
                    sendOtpBtn.disabled = false;
                    sendOtpBtn.textContent = 'Send OTP';
                    flash(data.message || 'Unable to send OTP.', 'danger');
                    return;
                }

                bookingId = data.booking_id;
                document.getElementById('mobile').readOnly = true;
                document.getElementById('name').readOnly = true;
                document.getElementById('findBtn').disabled = true;
                document.getElementById('otpWrap').classList.remove('d-none');
                document.getElementById('verifyOtpBtn').classList.remove('d-none');
                sendOtpBtn.textContent = 'OTP Sent';
                flash(data.message || 'OTP sent successfully.');
            } catch (error) {
                sendOtpBtn.disabled = false;
                sendOtpBtn.textContent = 'Send OTP';
                flash('Unable to send OTP. Please try again.', 'danger');
            }
        });

        document.getElementById('verifyOtpBtn').addEventListener('click', async () => {
            const response = await fetch(`{{ route('verify-otp') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    booking_id: bookingId,
                    mobile: document.getElementById('mobile').value.trim(),
                    otp: document.getElementById('otp').value.trim()
                })
            });

            const data = await response.json();

            if (!response.ok || !data.status) {
                flash(data.message || 'OTP verification failed.', 'danger');
                return;
            }

            window.location.href = data.redirect_url;
        });
    </script>
</body>

</html>
