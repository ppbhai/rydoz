<?php

namespace App\Http\Controllers;

use App\Models\BlacklistReason;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BlacklistReasonController extends Controller
{
    public function index()
    {
        $reasons = BlacklistReason::orderByDesc('is_active')->orderBy('reason')->get();

        return view('blacklist-reason', compact('reasons'));
    }

    public function store(Request $request): RedirectResponse
    {
        BlacklistReason::create($this->validated($request));

        return redirect()->route('blacklist-reasons.index')->with('success', 'Blacklist reason added successfully.');
    }

    public function edit(BlacklistReason $blacklistReason)
    {
        return view('blacklist-reason-update', compact('blacklistReason'));
    }

    public function update(Request $request, BlacklistReason $blacklistReason): RedirectResponse
    {
        $blacklistReason->update($this->validated($request));

        return redirect()->route('blacklist-reasons.index')->with('success', 'Blacklist reason updated successfully.');
    }

    public function destroy(BlacklistReason $blacklistReason): RedirectResponse
    {
        $blacklistReason->delete();

        return redirect()->route('blacklist-reasons.index')->with('success', 'Blacklist reason deleted successfully.');
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
