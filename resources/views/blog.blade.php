@include('masterlayout.masterlayout', ['title' => 'Blogs'])

<!-- ============================================================== -->
<!-- Start form -->
<!-- ============================================================== -->

<div class="content-page">
    <div class="content">
        <!-- Start Content-->
        <div class="container-fluid">

            <div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
                <div class="flex-grow-1">
                    <h4 class="fs-18 fw-semibold m-0">Blogs</h4>
                </div>

                <div class="text-end">
                    <ol class="breadcrumb m-0 py-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item active">Blogs</li>
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
                            <form class="row g-3" action="{{ route('blogsave') }}" method="post"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="col-md-3">
                                    <label for="example-select" class="form-label">Blog Category</label>
                                    <select class="form-select" name="blogcategory" id="blogcategory" required>
                                        <option value="">Select Category</option>
                                        @foreach ($blogcategory as $blogcategory)
                                            <option value="{{ $blogcategory->id }}">{{ $blogcategory->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="title" class="form-label">Title</label>
                                    <input type="text" class="form-control" name="title" id="title"
                                        placeholder="Enter Title" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="blogimg" class="form-label">Blog Image</label>
                                    <input type="file" class="form-control" name="blogimg" id="blogimg"required>
                                </div>
                                <div class="col-md-3">
                                    <label for="alt" class="form-label">Alt</label>
                                    <input type="text" class="form-control" name="alt" id="alt"
                                        placeholder="Enter Alt" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="slug" class="form-label">Slug</label>
                                    <input type="text" class="form-control" name="slug" id="slug"
                                        placeholder="Enter Slug" required>
                                </div>
                                <div class="col-md-12">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="summernote" name="description"></textarea>
                                </div>
                                <div class="col-md-3">
                                    <label for="metatitle" class="form-label">Meta Title</label>
                                    <textarea class="form-control" name="metatitle"></textarea>
                                </div>

                                <div class="col-md-3">
                                    <label for="metadescription" class="form-label">Meta Description</label>
                                    <textarea class="form-control" name="metadescription"></textarea>
                                </div>
                                <div class="col-md-3">
                                    <label for="metakeywords" class="form-label">Meta Keywords</label>
                                    <textarea class="form-control" name="metakeywords"></textarea>
                                </div>
                                <div class="col-md-3">
                                    <label for="cannonical" class="form-label">Cannonical URL</label>
                                    <textarea class="form-control" name="cannonical"></textarea>
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
                    <h5 class="card-title mb-0">Blogs</h5>
                </div><!-- end card header -->

                <div class="card-body">
                    <table id="datatable" class="table table-bordered dt-responsive table-responsive nowrap">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Title</th>
                                <th>Image</th>


                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $co = 1; ?>
                            @foreach ($blog as $blog)
                                <tr>
                                    <td>{{ $co }}</td>
                                    <td>{{ $blog->title }}</td>
                                    <td><img src="{{ URL::asset('/storage/' . $blog->image) }}" alt=""
                                            style="height:40px"></td>
                                    <td>


                                        <a href="{{ route('blogupdate', ['blog' => $blog->id]) }}" aria-label="anchor"
                                            class="btn btn-icon btn-sm bg-primary-subtle me-1"
                                            data-bs-toggle="tooltip" data-bs-original-title="Edit">
                                            <i class="mdi mdi-pencil-outline fs-14 text-primary"></i>
                                        </a>
                                        <a aria-label="anchor" class="btn btn-icon btn-sm bg-danger-subtle"
                                            data-bs-toggle="modal"
                                            data-bs-target="#delete-modal-{{ $blog->id }}">
                                            <i class="mdi mdi-delete fs-14 text-danger"></i>
                                        </a>

                                        <!-- Delete Confirmation Modal -->
                                        <div class="modal fade" id="delete-modal-{{ $blog->id }}" tabindex="-1"
                                            aria-labelledby="standard-modalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h1 class="modal-title fs-5" id="standard-modalLabel">Confirm
                                                            Delete?</h1>
                                                        <button type="button" class="btn-close"
                                                            data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <h5>Are you sure you want to delete this blog?</h5>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light"
                                                            data-bs-dismiss="modal">Close</button>

                                                        <!-- Delete Form -->
                                                        <form
                                                            action="{{ route('blogdelete', ['blog' => $blog->id]) }}"
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
<script>
    $(document).ready(function() {
        $('#summernote').summernote({
            height: 100
        });
    });
</script>
