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
                            <form class="row g-3" action="{{ route('testimonialsave') }}" method="post" enctype="multipart/form-data">
                                @csrf
                                <div class="col-md-3">
                                    <label for="name" class="form-label">Name</label>
                                    <input type="text" class="form-control" name="name" id="name"
                                        placeholder="Enter Name" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="review" class="form-label">Review</label>
                                    <input type="text" class="form-control" name="review" id="review"
                                        placeholder="Enter Review" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="rating" class="form-label">Rating</label>
                                    <input type="text" class="form-control" name="rating" id="rating"
                                        placeholder="Enter Rating" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="position" class="form-label">Position</label>
                                    <input type="text" class="form-control" name="position" id="position"
                                        placeholder="Enter Position" required>
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
    <div class="row">
        <div class="col-12">
            <div class="card">

                <div class="card-header">
                    <h5 class="card-title mb-0">Testimonials</h5>
                </div><!-- end card header -->

                <div class="card-body">
                    <table id="datatable" class="table table-bordered dt-responsive table-responsive nowrap">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Position</th>
                                <th>Rating</th>
                                <th>Review</th>

                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $co = 1; ?>
                            @foreach ($testimonial as $testimonial)
                                <tr>
                                    <td>{{ $co }}</td>
                                    <td>{{ $testimonial->name }}</td>
                                    <td>{{ $testimonial->position }}</td>
                                    <td>{{ $testimonial->rating }}</td>
                                    <td>{{ $testimonial->review }}</td>




                                    <td>
                                        {{-- <button>
                                            <a class="edit" href="{{ route('testimonialupdate', $testimonial->id) }}">
                                                <i class="fa fa-pen"></i>
                                            </a></button>
                                        <button class="btn btn-danger btn-xs"
                                            onclick="mydelete({{ $testimonial->id }})"><i class="fa fa-trash"
                                                style="margin: 3px;"></i></button>

                                        <script>
                                            function mydelete(id) {
                                                var ids = id;

                                                swal({
                                                    title: "Are you want to delete this Item?",
                                                    icon: "warning",
                                                    buttons: true,
                                                    dangerMode: true,
                                                }).then((willDelete) => {
                                                    if (willDelete) {
                                                        var urldelete = '{{ route('testimonialdelete', ':testimonial') }}';
                                                        urldelete = urldelete.replace(':testimonial', ids);
                                                        window.location.href = urldelete;
                                                    }
                                                });
                                            }
                                        </script> --}}

                                        <a href="{{ route('testimonialupdate', ['testimonial' => $testimonial->id]) }}"
                                            aria-label="anchor" class="btn btn-icon btn-sm bg-primary-subtle me-1"
                                            data-bs-toggle="tooltip" data-bs-original-title="Edit">
                                            <i class="mdi mdi-pencil-outline fs-14 text-primary"></i>
                                        </a>
                                        <a aria-label="anchor" class="btn btn-icon btn-sm bg-danger-subtle"
                                            data-bs-toggle="modal"
                                            data-bs-target="#delete-modal-{{ $testimonial->id }}">
                                            <i class="mdi mdi-delete fs-14 text-danger"></i>
                                        </a>

                                        <!-- Delete Confirmation Modal -->
                                        <div class="modal fade" id="delete-modal-{{ $testimonial->id }}" tabindex="-1"
                                            aria-labelledby="standard-modalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h1 class="modal-title fs-5" id="standard-modalLabel">Confirm
                                                            Delete?</h1>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                            aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <h5>Are you sure you want to delete this testimonial?</h5>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light"
                                                            data-bs-dismiss="modal">Close</button>

                                                        <!-- Delete Form -->
                                                        <form
                                                            action="{{ route('testimonialdelete', ['testimonial' => $testimonial->id]) }}"
                                                            method="get" style="display:inline;">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger">Yes,
                                                                Delete</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php $co++; ?>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>


</div>
