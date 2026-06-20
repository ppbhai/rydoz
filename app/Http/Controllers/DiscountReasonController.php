<?php

namespace App\Http\Controllers;

use App\Models\DiscountReason;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DiscountReasonController extends Controller
{
    public function index()
    {
        $reasons = DiscountReason::orderByDesc('is_active')->orderBy('reason')->get();

        return view('discount-reason', compact('reasons'));
    }

    public function store(Request $request): RedirectResponse
    {
        DiscountReason::create($this->validated($request));

        return redirect()->route('discount-reasons.index')->with('success', 'Discount reason added successfully.');
    }

    public function edit(DiscountReason $discountReason)
    {
        return view('discount-reason-update', compact('discountReason'));
    }

    public function update(Request $request, DiscountReason $discountReason): RedirectResponse
    {
        $discountReason->update($this->validated($request));

        return redirect()->route('discount-reasons.index')->with('success', 'Discount reason updated successfully.');
    }

    public function destroy(DiscountReason $discountReason): RedirectResponse
    {
        $discountReason->delete();

        return redirect()->route('discount-reasons.index')->with('success', 'Discount reason deleted successfully.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]) + [
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
