<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8" />
    <title>{{ $title ?? 'Default Title' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="A fully featured admin theme which can be used to build CRM, CMS, etc." />
    <meta name="author" content="Zoyothemes" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <!-- App css -->
    <link href="{{ URL::asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" id="app-style" />

    <!-- Icons -->
    <link href="{{ URL::asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />

    <script src="{{ URL::asset('assets/js/head.js') }}"></script>

    <link href="{{ URL::asset('assets/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet"
        type="text/css" />
    <link href="{{ URL::asset('assets/libs/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css') }}"
        rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('assets/libs/datatables.net-keytable-bs5/css/keyTable.bootstrap5.min.css') }}"
        rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('assets/libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css') }}"
        rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('assets/libs/datatables.net-select-bs5/css/select.bootstrap5.min.css') }}"
        rel="stylesheet" type="text/css" />

    <link href="{{ URL::asset('assets/libs/quill/quill.core.js" rel="stylesheet" type="text/css') }}" />
    <link href="{{ URL::asset('assets/libs/quill/quill.snow.css" rel="stylesheet" type="text/css') }}" />
    <link href="{{ URL::asset('assets/libs/quill/quill.bubble.css" rel="stylesheet" type="text/css') }}" />
    <link rel="stylesheet" href="{{ URL::asset('assets/summernote/summernote-bs4.min.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>

    <style>
        .app-toast-stack {
            position: fixed;
            top: 18px;
            right: 18px;
            z-index: 2000;
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: min(92vw, 360px);
            pointer-events: none;
        }

        .app-toast {
            padding: 12px 14px;
            border-radius: 12px;
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: #fff;
            color: #1f2933;
            font-size: 0.9rem;
            font-weight: 600;
            line-height: 1.4;
            transform: translateY(-6px);
            opacity: 0;
            transition: opacity 0.25s ease, transform 0.25s ease;
            pointer-events: auto;
        }

        .app-toast.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .app-toast.is-success {
            background: #e8f7ee;
            color: #155d34;
        }

        .app-toast.is-error {
            background: #feeaeb;
            color: #8a1c24;
        }
    </style>


</head>
@php
    $sessiondata = Session::get('admindata');
@endphp

<body data-menu-color="light" data-sidebar="default">
    <div id="app-layout">

        <!-- Topbar Start -->
        <div class="topbar-custom">
            <div class="container-fluid">
                <div class="d-flex justify-content-between">
                    <ul class="list-unstyled topnav-menu mb-0 d-flex align-items-center">
                        <li>
                            <button class="button-toggle-menu nav-link">
                                <i data-feather="menu" class="noti-icon"></i>
                            </button>
                        </li>
                        <li class="d-none d-lg-block">
                            <h5 class="mb-0">Hello, {{ $sessiondata->unm }}</h5>
                        </li>
                    </ul>

                    <ul class="list-unstyled topnav-menu mb-0 d-flex align-items-center">
                        <!-- Button Trigger Customizer Offcanvas -->
                        <li class="d-none d-sm-flex">
                            <button type="button" class="btn nav-link" data-toggle="fullscreen">
                                <i data-feather="maximize" class="align-middle fullscreen noti-icon"></i>
                            </button>
                        </li>

                        <!-- Light/Dark Mode Button Themes -->
                        <li class="d-none d-sm-flex">
                            <button type="button" class="btn nav-link" id="light-dark-mode">
                                <i data-feather="moon" class="align-middle dark-mode"></i>
                                <i data-feather="sun" class="align-middle light-mode"></i>
                            </button>
                        </li>

                        <!-- User Dropdown -->
                        <li class="dropdown notification-list topbar-dropdown">
                            <a class="nav-link dropdown-toggle nav-user me-0" data-bs-toggle="dropdown" href="#"
                                role="button" aria-haspopup="false" aria-expanded="false">
                                <img src="{{ $sessiondata->image ? asset('storage/' . $sessiondata->image) : asset('assets/images/favicon.ico') }}" alt="user-image"
                                    class="rounded-circle" />
                                <span class="pro-user-name ms-1">{{ $sessiondata->unm }} <i
                                        class="mdi mdi-chevron-down"></i></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end profile-dropdown">
                                <!-- item-->
                                <div class="dropdown-header noti-title">
                                    <h6 class="text-overflow m-0">Welcome !</h6>
                                </div>

                                <!-- item-->
                                <a href="{{ route('adminprofileupdate', ['admin' => $sessiondata->id]) }}"
                                    class="dropdown-item notify-item">
                                    <i class="mdi mdi-account-circle-outline fs-16 align-middle"></i>
                                    <span>Edit Profile</span>
                                </a>

                                <a href="{{ route('adminchagepwd', ['admin' => $sessiondata->id]) }}"
                                    class="dropdown-item notify-item">
                                    <i class="mdi mdi-key fs-16 align-middle"></i>
                                    <span>Chnage Password</span>
                                </a>


                                <div class="dropdown-divider"></div>

                                <!-- item-->
                                <a href="{{ route('logout') }}" class="dropdown-item notify-item">
                                    <i class="mdi mdi-location-exit fs-16 align-middle"></i>
                                    <span>Logout</span>
                                </a>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <!-- end Topbar -->

        <!-- Left Sidebar Start -->
        <div class="app-sidebar-menu">
            <div class="h-100" data-simplebar>

                <!--- Sidemenu -->
                <div id="sidebar-menu">

                    <div class="logo-box">
                        <a href="{{ route('dashboard') }}" class="logo logo-light">
                            <span class="logo-sm">
                                <img src="{{ URL::asset('assets/images/rydoz.png') }}" alt=""
                                    height="25">
                            </span>
                            <span class="logo-lg">
                                <img src="{{ URL::asset('assets/images/rydoz.png') }}" alt=""
                                    height="26">
                            </span>
                        </a>
                        <a href="{{ route('dashboard') }}" class="logo logo-dark">
                            <span class="logo-sm">
                                <img src="{{ URL::asset('assets/images/rydoz.png') }}" alt=""
                                    height="25">
                            </span>
                            <span class="logo-lg">
                                <img src="{{ URL::asset('assets/images/rydoz.png') }}" alt="" height="50">
                            </span>
                        </a>
                    </div>

                    <ul id="side-menu">


                        {{-- Dashboard --}}
                        <li>
                            <a href="{{ route('dashboard') }}">
                                <i data-feather="home"></i>
                                <span> Dashboard </span>
                            </a>
                        </li>
                        {{-- Dashboard --}}

                        {{-- Branch --}}
                        <li>
                            <a href="{{ route('branchshow') }}">
                                <i data-feather="map-pin"></i>
                                <span> Branches </span>
                            </a>
                        </li>

                        {{-- User --}}
                        <li>
                            <a href="{{ route('usershow') }}">
                                <i data-feather="user"></i>
                                <span> Branch Users </span>
                            </a>
                        </li>
                        {{-- User --}}

                        {{-- vehicle --}}
                        <li>
                            <a href="{{ route('vehicleshow') }}">
                               <i data-feather="truck"></i>
                                <span> Branch Vehicles </span>
                            </a>
                        </li>
                        {{-- vehicle --}}

                        <li>
                            <a href="{{ route('discount-reasons.index') }}">
                               <i data-feather="tag"></i>
                                <span> Discount Reasons </span>
                            </a>
                        </li>

                        {{-- Booking --}}
                        <li>
                            <a href="#bookingMenu" data-bs-toggle="collapse" aria-expanded="false">
                                <i data-feather="truck"></i>
                                <span>Bookings</span>
                                <span class="menu-arrow"></span>
                            </a>

                            <div class="collapse {{ request()->routeIs('booking.*') ? 'show' : '' }}" id="bookingMenu">
                                <ul class="nav-second-level">
                                    <li>
                                        <a href="{{ route('booking.list') }}">
                                            Completed Bookings List
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('booking.pending') }}">
                                            Pending Bookings List
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('booking.discounted') }}">
                                            Discounted Bookings List
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        {{-- Booking --}}

                        {{-- Payment --}}
                        <li>
                            <a href="#paymentMenu" data-bs-toggle="collapse" aria-expanded="false">
                                <i data-feather="credit-card"></i>
                                <span>Payment & Ride</span>
                                <span class="menu-arrow"></span>
                            </a>

                            <div class="collapse {{ request()->routeIs('payment.*') ? 'show' : '' }}" id="paymentMenu">
                                <ul class="nav-second-level">
                                    <li>
                                        <a href="{{ route('payment.report') }}">
                                            Payment Report
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        {{-- Payment --}}

                    </ul>

                </div>
                <!-- End Sidebar -->

                <div class="clearfix"></div>

            </div>
        </div>
        <!-- Left Sidebar End -->
    </div>


    <!-- Footer Start -->
    <footer class="footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col fs-13 text-muted text-center">
                    &copy;
                    <script>
                        document.write(new Date().getFullYear())
                    </script> - Developed by <a href="https://jgitsolution.com/"
                        class="text-reset fw-semibold">JG IT Solution</a>
                </div>
            </div>
        </div>
    </footer>
    <!-- end Footer -->

    <div id="loader">
        <div class="spinner-border text-primary m-2" style="width: 4rem; height: 4rem;" role="status">
            <span class="visually-hidden">Loading...</span>

            <div class="spinner-grow text-primary m-2" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>

    <style>
        /* Fullscreen loader overlay */
        #loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #ffffff;
            /* background color */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }


        /* Hide loader smoothly */
        #loader.fade-out {
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.4s ease, visibility 0.4s ease;
        }
    </style>


    <script>
        window.addEventListener("load", function() {
            const loader = document.getElementById("loader");
            loader.classList.add("fade-out");
        });
    </script>




    <!-- Vendor -->
    <script src="{{ URL::asset('assets/libs/jquery/jquery.min.js') }}"></script>
    <script src="{{ URL::asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ URL::asset('assets/libs/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ URL::asset('assets/libs/node-waves/waves.min.js') }}"></script>
    <script src="{{ URL::asset('assets/libs/waypoints/lib/jquery.waypoints.min.js') }}"></script>
    <script src="{{ URL::asset('assets/libs/jquery.counterup/jquery.counterup.min.js') }}"></script>
    <script src="{{ URL::asset('assets/libs/feather-icons/feather.min.js') }}"></script>

    <!-- Apexcharts JS -->
    <script src="{{ URL::asset('assets/libs/apexcharts/apexcharts.min.js') }}"></script>

    <!-- Widgets Init Js -->
    <script src="{{ URL::asset('assets/js/pages/crm-dashboard.init.js') }}"></script>


    <!-- Datatables js -->
    <script src="{{ URL::asset('assets/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>

    <!-- dataTables.bootstrap5 -->
    <script src="{{ URL::asset('assets/libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ URL::asset('assets/libs/datatables.net-buttons/js/dataTables.buttons.min.js') }}"></script>

    <!-- buttons.colVis -->
    <script src="{{ URL::asset('assets/libs/datatables.net-buttons/js/buttons.colVis.min.js') }}"></script>
    <script src="{{ URL::asset('assets/libs/datatables.net-buttons/js/buttons.flash.min.js') }}"></script>
    <script src="{{ URL::asset('assets/libs/datatables.net-buttons/js/buttons.html5.min.js') }}"></script>
    <script src="{{ URL::asset('assets/libs/datatables.net-buttons/js/buttons.print.min.js') }}"></script>

    <!-- buttons.bootstrap5 -->
    <script src="{{ URL::asset('assets/libs/datatables.net-buttons-bs5/js/buttons.bootstrap5.min.js') }}"></script>

    <!-- dataTables.keyTable -->
    <script src="{{ URL::asset('assets/libs/datatables.net-keytable/js/dataTables.keyTable.min.js') }}"></script>
    <script src="{{ URL::asset('assets/libs/datatables.net-keytable-bs5/js/keyTable.bootstrap5.min.js') }}"></script>

    <!-- dataTable.responsive -->
    <script src="{{ URL::asset('assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ URL::asset('assets/libs/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js') }}"></script>

    <!-- dataTables.select -->
    <script src="{{ URL::asset('assets/libs/datatables.net-select/js/dataTables.select.min.js') }}"></script>
    <script src="{{ URL::asset('assets/libs/datatables.net-select-bs5/js/select.bootstrap5.min.js') }}"></script>

    <!-- Datatable Demo App Js -->
    <script src="{{ URL::asset('assets/js/pages/datatable.init.js') }}"></script>

    <script src="{{ URL::asset('assets/libs/quill/quill.core.js') }}"></script>
    <script src="{{ URL::asset('assets/libs/quill/quill.min.js') }}"></script>

    <!-- Quill Demo Js -->
    <script src="{{ URL::asset('assets/js/pages/quilljs.init.js') }}"></script>

    <!-- Summernote -->
    <script src="{{ URL::asset('assets/summernote/summernote-bs4.min.js') }}"></script>
    <!-- App js-->
    <script src="{{ URL::asset('assets/js/app.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const alerts = Array.from(document.querySelectorAll('.alert'))
                .filter((alert) => !alert.classList.contains('d-none') && !alert.id);

            if (!alerts.length) {
                return;
            }

            const stack = document.createElement('div');
            stack.className = 'app-toast-stack';
            document.body.appendChild(stack);

            alerts.forEach((alert, index) => {
                const text = (alert.textContent || '').trim();

                if (text === '') {
                    alert.remove();
                    return;
                }

                const toast = document.createElement('div');
                toast.className = 'app-toast';
                toast.classList.add(alert.classList.contains('alert-success') ? 'is-success' : 'is-error');
                toast.textContent = text;
                stack.appendChild(toast);
                alert.remove();

                requestAnimationFrame(() => {
                    setTimeout(() => {
                        toast.classList.add('is-visible');
                    }, index * 80);
                });

                setTimeout(() => {
                    toast.classList.remove('is-visible');
                    setTimeout(() => toast.remove(), 250);
                }, 3000 + (index * 80));
            });
        });
    </script>

</body>

</html>
