@include('theme.partials.head', ['title' => 'Login'])

<body>
    <div class="auth-wrap">
        <div class="auth-card">
            <div class="text-center mb-4">
                <img src="{{ URL::asset('assets/images/rydoz.png') }}" class="auth-logo" alt="RYDOZ">
            </div>

            <form action="{{ route('userloginprocess') }}" method="post" class="stack-sm">
                @csrf
                <div>
                    <label class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" placeholder="Enter email" name="email">
                    </div>
                </div>

                <div>
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" id="password" class="form-control" placeholder="Enter password"
                            name="password">
                    </div>
                </div>

                @if (session('error'))
                    <div class="alert alert-danger text-center mb-0">
                        {{ session('error') }}
                    </div>
                @endif

                <button class="btn btn-theme w-100">Login</button>
            </form>
        </div>
    </div>
</body>

</html>
