@include('masterlayout.masterlayout', ['title' => 'Edit Blacklist Reason'])

<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="py-3 d-flex justify-content-between align-items-center">
                <h4 class="fs-18 fw-semibold m-0">Edit Blacklist Reason</h4>
            </div>

            <div class="card">
                <div class="card-body">
                    <form class="row g-3" action="{{ route('blacklist-reasons.update', $blacklistReason) }}" method="post">
                        @csrf
                        <div class="col-md-8">
                            <label class="form-label">Reason</label>
                            <input type="text" class="form-control" name="reason" value="{{ $blacklistReason->reason }}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="is_active">
                                <option value="1" @selected($blacklistReason->is_active)>Active</option>
                                <option value="0" @selected(!$blacklistReason->is_active)>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-primary w-100" type="submit">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
