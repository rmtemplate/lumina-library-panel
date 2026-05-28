<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminLoginService
{
    public function login(string $email, string $password, bool $remember = false): User
    {
        $user = User::with(['roles', 'branch'])->where('email', $email)->first();

        if (! $user || ! Hash::check($password, (string) $user->password_hash)) {
            throw ValidationException::withMessages(['email' => 'Email atau password tidak sesuai.']);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages(['email' => 'Akun ini sedang tidak aktif.']);
        }

        if (! $user->is_verified) {
            throw ValidationException::withMessages(['email' => 'Akun ini belum diverifikasi.']);
        }

        if (! $user->canAccessAdminPanel()) {
            throw ValidationException::withMessages(['email' => 'Hanya Super Admin dan Admin Branch yang dapat mengakses dashboard admin.']);
        }

        if ($user->isBranchAdmin() && ! $user->getBranchScopeId()) {
            throw ValidationException::withMessages(['email' => 'Akun admin branch belum terhubung ke branch.']);
        }

        Auth::guard('web')->login($user, $remember);
        $user->forceFill(['last_login_at' => now()])->save();

        return $user;
    }
}
