@include('masterlayout.masterlayout', ['title' => 'Edit Branch'])

<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="py-3 d-flex justify-content-between align-items-center">
                <h4 class="fs-18 fw-semibold m-0">Edit Branch</h4>
            </div>

            <div class="card">
                <div class="card-body">
                    <form class="row g-3" action="{{ route('branchedit', $branch) }}" method="post">
                        @csrf
                        <div class="col-md-3">
                            <label class="form-label">Branch Name</label>
                            <input type="text" class="form-control" name="name" value="{{ $branch->name }}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Buffer Time (min)</label>
                            <input type="number" class="form-control" name="buffer_time" min="0" value="{{ $branch->buffer_time }}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Document Photo (Camera)</label>
                            <select class="form-select" name="photo_enabled">
                                <option value="0" @selected(!$branch->photo_enabled)>Off</option>
                                <option value="1" @selected($branch->photo_enabled)>On</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Vehicle Id Scanner</label>
                            <select class="form-select" name="scanner_enabled">
                                <option value="0" @selected(!$branch->scanner_enabled)>Off</option>
                                <option value="1" @selected($branch->scanner_enabled)>On</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Vehicle Id Number</label>
                            <select class="form-select" name="vehicle_number_required">
                                <option value="0" @selected(!$branch->vehicle_number_required)>Off</option>
                                <option value="1" @selected($branch->vehicle_number_required)>On</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Document Select</label>
                            <select class="form-select" name="document_select_enabled">
                                <option value="0" @selected(!$branch->document_select_enabled)>Off</option>
                                <option value="1" @selected($branch->document_select_enabled)>On</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Free Trial</label>
                            <select class="form-select" name="free_trial_enabled">
                                <option value="0" @selected(!$branch->free_trial_enabled)>Off</option>
                                <option value="1" @selected($branch->free_trial_enabled)>On</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary" type="submit">Update Branch</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
