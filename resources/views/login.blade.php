<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8" />
    <title>Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="" />
    <meta name="author" content="Zoyothemes" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ URL::asset('assets/images/favicon.ico') }}">

    <!-- App css -->
    <link href="{{ URL::asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" id="app-style" />

    <!-- Icons -->
    <link href="{{ URL::asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />

    <script src="{{ URL::asset('assets/js/head.js') }}"></script>


</head>

<body>
    <div class="account-page">
        <div class="container-fluid p-0">
            <div class="row align-items-center g-0 px-3 py-3 vh-100">

                <div class="col-xl-5">
                    <div class="row">
                        <div class="col-md-8 mx-auto">
                            <div class="card">
                                <div class="card-body">
                                    <div class="mb-0 p-0 p-lg-3">
                                        <div class="mb-0 border-0 p-md-4 p-lg-0">
                                            <div class="mb-4 p-0 text-lg-start text-center">
                                                <div class="auth-brand">
                                                    <a href="index.html" class="logo logo-light">
                                                        <span class="logo-lg">
                                                            <img src="assets/images/logo-light-3.png" alt=""
                                                                height="24">
                                                        </span>
                                                    </a>
                                                    {{-- <a href="index.html" class="logo logo-dark">
                                                        <span class="logo-lg">
                                                            <img src="assets/images/logo-dark-3.png" alt=""
                                                                height="24">
                                                        </span>
                                                    </a> --}}
                                                </div>
                                            </div>

                                            <div class="auth-title-section mb-4 text-lg-start text-center">
                                                <h3 class="text-dark fw-semibold mb-3">Welcome back! Please Sign in to
                                                    continue.</h3>
                                                {{-- <p class="text-muted fs-14 mb-0">Sign up today to unlock exclusive
                                                    content,
                                                    enjoy special offers, and be the first to hear about exciting
                                                    updates
                                                    and announcements.</p> --}}
                                            </div>
                                            <div class="pt-0">
                                                <form action="loginprocess" class="my-4" method="post">
                                                    @csrf
                                                    <div class="form-group mb-3">
                                                        <label for="emailaddress" class="form-label">Email
                                                            address</label>
                                                        <input class="form-control" type="email" id="emailaddress"
                                                            required="" placeholder="Enter your email"
                                                            name="email">
                                                    </div>

                                                    <div class="form-group mb-3">
                                                        <label for="password" class="form-label">Password</label>
                                                        <input class="form-control" type="password" required=""
                                                            id="password" placeholder="Enter your password"
                                                            name="password">
                                                    </div>

                                                    <div class="form-group d-flex mb-3">
                                                        <div class="col-sm-6">

                                                        </div>
                                                        {{-- <div class="col-sm-6 text-end">
                                                            <a class='text-muted fs-14'
                                                                href='adminforgetpwd'>Forgot
                                                                password?</a>
                                                        </div> --}}
                                                    </div>

                                                    @if (Session('success'))
                                                        <div class="alert alert-success alert-dismissible fade show"
                                                            role="alert">
                                                            {{ Session('success') }}
                                                        </div>
                                                    @endif

                                                    @if (Session('error'))
                                                        <div class="alert alert-danger alert-dismissible fade show"
                                                            role="alert">
                                                            {{ Session('error') }}
                                                        </div>
                                                    @endif
                                                    <div class="form-group mb-0 row">
                                                        <div class="col-12">
                                                            <div class="d-grid">
                                                                <button class="btn btn-primary fw-semibold"
                                                                    type="submit">
                                                                    Log In </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </form>


                                                {{-- <div class="text-center text-muted">
                                                    <p class="mb-0">Don't have an account ?<a
                                                            class='text-primary ms-2 fw-medium'
                                                            href='auth-register.html'>Sing up</a></p>
                                                </div> --}}

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="col-xl-7 d-none d-xl-inline-block">
                    <div class="account-page-bg rounded-4">
                        <div class="auth-user-review text-center">
                            <div id="carouselExampleFade" class="carousel slide carousel-fade" data-bs-ride="carousel">
                                <div class="carousel-inner">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

</body>

</html>
