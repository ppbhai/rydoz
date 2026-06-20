@include('masterlayout.masterlayout', ['title' => 'SEO'])

<!-- ============================================================== -->
<!-- Start form -->
<!-- ============================================================== -->

<div class="content-page">
    <div class="content">
        <!-- Start Content-->
        <div class="container-fluid">

            <div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
                <div class="flex-grow-1">
                    <h4 class="fs-18 fw-semibold m-0">SEO</h4>
                </div>

                <div class="text-end">
                    <ol class="breadcrumb m-0 py-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item active">SEO</li>
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
            <div class="row">
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-body">
                            <form class="row g-3" action="{{ route('seoedit', ['seo' => $seo->id]) }}" method="post">
                                @csrf
                                <div class="col-md-3">
                                    <label for="title" class="form-label">Meta Title</label>
                                    <textarea class="form-control" name="title">{{ $seo->title }}</textarea>
                                </div>
                                <div class="col-md-3">
                                    <label for="des" class="form-label">Meta Description</label>
                                    <textarea class="form-control" name="des">{{ $seo->des }}</textarea>
                                </div>
                                <div class="col-md-3">
                                    <label for="keywords" class="form-label">Meta Keywords</label>
                                    <textarea class="form-control" name="keywords">{{ $seo->keywords }}</textarea>
                                </div>
                                <div class="col-md-3">
                                    <label for="canonical" class="form-label">Canonical URL</label>
                                    <textarea class="form-control" name="canonical">{{ $seo->canonical }}</textarea>
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
