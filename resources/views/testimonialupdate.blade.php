@include('masterlayout.masterlayout', ['title' => 'Testimonials'])

<!-- ============================================================== -->
<!-- Start form -->
<!-- ============================================================== -->

<div class="content-page">
    <div class="content">
        <!-- Start Content-->
        <div class="container-fluid">

            <div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
                <div class="flex-grow-1">
                    <h4 class="fs-18 fw-semibold m-0">Testimonials</h4>
                </div>

                <div class="text-end">
                    <ol class="breadcrumb m-0 py-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item active">Testimonials</li>
                    </ol>
                </div>
            </div>

            @if (Session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ Session('success') }}
                </div>
            @endif

            @if (Session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ Session('error') }}
                </div>
            @endif
            <!-- Form Validation -->
            <div class="row">
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-body">
                            <form class="row g-3"
                                action="{{ route('testimonialedit', ['testimonial' => $testimonial->id]) }}"
                                method="post" enctype="multipart/form-data">
                                @csrf
                                <div class="col-md-3">
                                    <label for="name" class="form-label">Name</label>
                                    <input type="text" class="form-control" name="name" id="name"
                                        placeholder="Enter Name" value="{{ $testimonial->name }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="review" class="form-label">Review</label>
                                    <input type="text" class="form-control" name="review" id="review"
                                        placeholder="Enter Review" value="{{ $testimonial->review }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="rating" class="form-label">Rating</label>
                                    <input type="text" class="form-control" name="rating" id="rating"
                                        placeholder="Enter Rating" value="{{ $testimonial->rating }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="position" class="form-label">Position</label>
                                    <input type="text" class="form-control" name="position" id="position"
                                        placeholder="Enter Position" value="{{ $testimonial->position }}" required>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-primary" type="submit">Submit</button>
                                </div>
                            </form>
                        </div> <!-- end card-body -->
                    </div> <!-- end card-->
                </div> <!-- end col -->
            </div>
        </div> <!-- container-fluid -->


        <!-- ============================================================== -->
        <!-- End form -->
        <!-- ============================================================== -->

    </div> <!-- content -->
    <!-- Datatables  -->


</div>
