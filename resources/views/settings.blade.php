@include('masterlayout.masterlayout', ['title' => 'Setting'])

<!-- ============================================================== -->
<!-- Start form -->
<!-- ============================================================== -->

<div class="content-page">
    <div class="content">
        <!-- Start Content-->
        <div class="container-fluid">

            <div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
                <div class="flex-grow-1">
                    <h4 class="fs-18 fw-semibold m-0">Setting</h4>
                </div>

                <div class="text-end">
                    <ol class="breadcrumb m-0 py-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item active">Setting</li>
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
                            <form class="row g-3" action="{{ route('settingedit', ['setting' => $setting->id]) }} "
                                method="post" enctype="multipart/form-data">
                                @csrf

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Barcode Scanner</label>
                                        <select name="scanner" id="scanner" class="form-select">
                                            <option value="1" {{ $setting->scanner == '1' ? 'selected' : '' }}>On
                                            </option>
                                            <option value="0" {{ $setting->scanner == '0' ? 'selected' : '' }}>Off</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Photo Capture</label>
                                        <select name="photo" id="photo" class="form-select">
                                            <option value="1" {{ $setting->photo == '1' ? 'selected' : '' }}>On</option>
                                            <option value="0" {{ $setting->photo == '0' ? 'selected' : '' }}>Off</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Buffer Time (min)</label>
                                        <input type="number" class="form-control" name="buffer_time" value="{{ $setting->buffer_time }}">
                                    </div>
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


</div>
<script>
    $(document).ready(function() {
        $('#summernote').summernote({
            height: 100
        });
    });
    $(document).ready(function() {
        $('#summernote1').summernote({
            height: 100
        });
    });
    $(document).ready(function() {
        $('#summernote2').summernote({
            height: 100
        });
    });
    $(document).ready(function() {
        $('#summernote3').summernote({
            height: 100
        });
    });
</script>
