<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        $user = auth()->user()->load('profileType');

        return view('user.profile.edit', compact('user'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'fullname'        => ['required', 'string', 'min:3', 'max:200'],
            'phone'           => ['nullable', 'string', 'min:8', 'max:20'],
            'institution_name' => ['nullable', 'string', 'max:200'],
            'major_name'      => ['nullable', 'string', 'max:200'],
            'current_level'   => ['nullable', 'string', 'max:100'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password'        => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $data = [
            'fullname'        => $validated['fullname'],
            'phone'           => $validated['phone'] ?? null,
            'institution_name' => $validated['institution_name'] ?? null,
            'major_name'      => $validated['major_name'] ?? null,
            'current_level'   => $validated['current_level'] ?? null,
            'updated_by'      => $user->id,
        ];

        if ($request->filled('password')) {
            $data['password_hash'] = Hash::make($validated['password']);
        }

        $user->update($data);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }
}
