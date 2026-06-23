<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingRide;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function branchshow()
    {
        $branches = Branch::orderBy('name')->get();

        return view('branch', compact('branches'));
    }

    public function branchsave(Request $request): RedirectResponse
    {
        $validated = $this->validateBranch($request);

        Branch::create($validated);

        return redirect()->route('branchshow')->with('success', 'Branch added successfully.');
    }

    public function branchupdate(Branch $branch)
    {
        return view('branchupdate', compact('branch'));
    }

    public function branchedit(Request $request, Branch $branch): RedirectResponse
    {
        $branch->update($this->validateBranch($request, $branch));

        return redirect()->route('branchshow')->with('success', 'Branch updated successfully.');
    }

    public function branchdelete(Branch $branch): RedirectResponse
    {
        $branch->delete();

        return redirect()->route('branchshow')->with('success', 'Branch deleted successfully.');
    }

    public function deleteBranchBookings(Branch $branch): RedirectResponse
    {
        $bookingIds = Booking::query()
            ->where('branch_id', $branch->id)
            ->pluck('id');

        $bookingCount = $bookingIds->count();

        DB::transaction(function () use ($bookingIds, $branch) {
            if ($bookingIds->isNotEmpty()) {
                BookingRide::query()
                    ->whereIn('booking_id', $bookingIds)
                    ->delete();
            }

            Booking::query()
                ->where('branch_id', $branch->id)
                ->delete();
        });

        return redirect()->route('branchshow')
            ->with('success', $bookingCount . ' booking data deleted for ' . $branch->name . '.');
    }

    protected function validateBranch(Request $request, ?Branch $branch = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('branches', 'name')->ignore($branch?->id)],
            'buffer_time' => ['required', 'integer', 'min:0'],
            'photo_enabled' => ['nullable', 'boolean'],
            'scanner_enabled' => ['nullable', 'boolean'],
            'vehicle_number_required' => ['nullable', 'boolean'],
            'document_select_enabled' => ['nullable', 'boolean'],
            'free_trial_enabled' => ['nullable', 'boolean'],
        ]) + [
            'photo_enabled' => $request->boolean('photo_enabled'),
            'scanner_enabled' => $request->boolean('scanner_enabled'),
            'vehicle_number_required' => $request->boolean('vehicle_number_required'),
            'document_select_enabled' => $request->boolean('document_select_enabled'),
            'free_trial_enabled' => $request->boolean('free_trial_enabled'),
        ];
    }
}
