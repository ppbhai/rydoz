@include('masterlayout.masterlayout', ['title' => 'Edit User'])

<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="py-3 d-flex align-items-center justify-content-between">
                <h4 class="fs-18 fw-semibold m-0">Edit User</h4>
            </div>

            <div class="card">
                <div class="card-body">
                    <form class="row g-3" action="{{ route('useredit', $user) }}" method="post">
                        @csrf
                        <div class="col-md-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" value="{{ $user->name }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone_no" maxlength="10" value="{{ $user->phone_no }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="{{ $user->email }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Branch</label>
                            <select class="form-select" name="branch_id" required>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected($user->branch_id == $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">New Password</label>
                            <input type="text" class="form-control" name="password" placeholder="Leave blank to keep old">
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary" type="submit">Update User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
