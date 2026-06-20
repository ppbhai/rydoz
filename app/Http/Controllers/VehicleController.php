<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchVehicle;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class VehicleController extends Controller
{
    public function vehicleshow()
    {
        $vehicles = BranchVehicle::with('branch')->orderByDesc('id')->get();
        $branches = Branch::orderBy('name')->get();

        return view('vehicle', compact('vehicles', 'branches'));
    }

    public function vehiclesave(Request $request): RedirectResponse
    {
        BranchVehicle::create($this->validated($request));

        return redirect()->route('vehicleshow')->with('success', 'Branch vehicle added successfully.');
    }

    public function vehicleupdate(BranchVehicle $vehicle)
    {
        $branches = Branch::orderBy('name')->get();

        return view('vehicleupdate', compact('vehicle', 'branches'));
    }

    public function vehicleedit(Request $request, BranchVehicle $vehicle): RedirectResponse
    {
        $vehicle->update($this->validated($request));

        return redirect()->route('vehicleshow')->with('success', 'Branch vehicle updated successfully.');
    }

    public function vehicledelete(BranchVehicle $vehicle): RedirectResponse
    {
        $vehicle->delete();

        return redirect()->route('vehicleshow')->with('success', 'Branch vehicle deleted successfully.');
    }

    protected function validated(Request $request): array
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'time' => ['required', 'integer', 'min:1'],
        ]);

        return $validated;
    }
}
