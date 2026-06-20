@include('masterlayout.masterlayout', ['title' => 'Edit Branch Vehicle'])

<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="py-3 d-flex justify-content-between align-items-center">
                <h4 class="fs-18 fw-semibold m-0">Edit Branch Vehicle</h4>
            </div>

            <div class="card">
                <div class="card-body">
                    <form class="row g-3" action="{{ route('vehicleedit', $vehicle) }}" method="post">
                        @csrf
                        <div class="col-md-3">
                            <label class="form-label">Branch</label>
                            <select class="form-select" name="branch_id" required>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected($vehicle->branch_id == $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Vehicle Name</label>
                            <input type="text" class="form-control" name="name" value="{{ $vehicle->name }}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Price</label>
                            <input type="number" class="form-control" step="0.01" name="price" min="0" value="{{ $vehicle->price }}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Time (min)</label>
                            <input type="number" class="form-control" name="time" min="1" value="{{ $vehicle->time }}" required>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary" type="submit">Update Vehicle</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
