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
                            <form class="row g-3" action="{{ route('blogedit', ['blog' => $blog->id]) }}"
                                method="post" enctype="multipart/form-data">
                                @csrf
                                <div class="col-md-3">
                                    <label for="example-select" class="form-label">Blog Category</label>
                                    <select class="form-select" name="blogcategory" id="blogcategory" required>
                                        <option value="">Select Category</option>
                                        @foreach ($blogcategory as $blogcategory)
                                            <option value="{{ $blogcategory->id }}"
                                                {{ $blog->blogcatid == $blogcategory->id ? 'selected' : '' }}>
                                                {{ $blogcategory->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="title" class="form-label">Title</label>
                                    <input type="text" class="form-control" name="title" id="title"
                                        placeholder="Enter Title" value="{{ $blog->title }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="blogimg" class="form-label">Blog Image</label>
                                    <input type="file" class="form-control" name="blogimg" id="blogimg">
                                </div>
                                <div class="col-md-3">
                                    <label for="alt" class="form-label">Alt</label>
                                    <input type="text" class="form-control" name="alt" id="alt"
                                        placeholder="Enter Alt" value="{{ $blog->alt }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="slug" class="form-label">Slug</label>
                                    <input type="text" class="form-control" name="slug" id="slug"
                                        placeholder="Enter Slug" value="{{ $blog->slug }}" required>
                                </div>
                                <div class="col-md-12">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="summernote" name="description">{{ $blog->description }}</textarea>
                                </div>
                                <div class="col-md-3">
                                    <label for="metatitle" class="form-label">Meta Title</label>
                                    <textarea class="form-control" name="metatitle">{{ $blog->metatitle }}</textarea>
                                </div>
                                <div class="col-md-3">
                                    <label for="metadescription" class="form-label">Meta Description</label>
                                    <textarea class="form-control" name="metadescription">{{ $blog->metades }}</textarea>
                                </div>
                                <div class="col-md-3">
                                    <label for="metakeywords" class="form-label">Meta Keywords</label>
                                    <textarea class="form-control" name="metakeywords">{{ $blog->metakeyword }}</textarea>
                                </div>
                                <div class="col-md-3">
                                    <label for="canonical" class="form-label">canonical URL</label>
                                    <textarea class="form-control" name="canonical">{{ $blog->canonical }}</textarea>
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
<script>
    $(document).ready(function() {
        $('#summernote').summernote({
            height: 100
        });
    });
</script>
