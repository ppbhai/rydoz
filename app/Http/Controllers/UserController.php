<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function usershow()
    {
        $users = User::with('branchRelation')->orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();

        return view('user', compact('users', 'branches'));
    }

    public function usersave(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        User::create([
            'name' => $validated['name'],
            'phone_no' => $validated['phone_no'],
            'email' => $validated['email'],
            'branch_id' => $validated['branch_id'],
            'branch' => Branch::find($validated['branch_id'])?->name,
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('usershow')->with('success', 'User added successfully.');
    }

    public function userupdate(User $user)
    {
        $branches = Branch::orderBy('name')->get();

        return view('userupdate', compact('user', 'branches'));
    }

    public function useredit(Request $request, User $user): RedirectResponse
    {
        $validated = $this->validated($request, $user, false);

        $user->name = $validated['name'];
        $user->phone_no = $validated['phone_no'];
        $user->email = $validated['email'];
        $user->branch_id = $validated['branch_id'];
        $user->branch = Branch::find($validated['branch_id'])?->name;

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('usershow')->with('success', 'User updated successfully.');
    }

    public function userdelete(User $user): RedirectResponse
    {
        $user->delete();

        return redirect()->route('usershow')->with('success', 'User deleted successfully.');
    }

    protected function validated(Request $request, ?User $user = null, bool $passwordRequired = true): array
    {
        $passwordRules = $passwordRequired ? ['required', 'string', 'min:4'] : ['nullable', 'string', 'min:4'];

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone_no' => ['required', 'digits:10'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'branch_id' => ['required', 'exists:branches,id'],
            'password' => $passwordRules,
        ]);
    }
}
