<?php

namespace App\Services\Api\Auth;

use App\Mail\VerifyEmailMail;
use App\Models\EmailVerificationToken;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use App\Models\UserSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApiAuthenticationService
{
    private const TOKEN_EXPIRES_DAYS = 30;
    private const EMAIL_VERIFICATION_EXPIRES_MINUTES = 60;

    public function register(array $payload): array
    {
        return DB::transaction(function () use ($payload) {
            if ($this->findUserByEmail((string) $payload['email'])) {
                throw ValidationException::withMessages([
                    'email' => ['Email sudah terdaftar.'],
                ]);
            }

            $memberRole = Role::query()
                ->where('code', Role::MEMBER)
                ->where('is_active', true)
                ->first();

            if (! $memberRole) {
                throw ValidationException::withMessages([
                    'role' => ['Role member belum tersedia. Jalankan seeder role terlebih dahulu.'],
                ]);
            }

            $user = User::query()->create([
                'id' => (string) Str::uuid(),
                'branch_id' => $payload['branch_id'] ?? null,
                'fullname' => $payload['fullname'],
                'email' => $payload['email'],
                'phone' => $payload['phone'] ?? null,
                'password_hash' => Hash::make($payload['password']),
                'member_code' => $this->generateMemberCode(),
                'profile_type_id' => $payload['profile_type_id'],
                'institution_name' => $payload['institution_name'] ?? null,
                'major_name' => $payload['major_name'] ?? null,
                'current_level' => $payload['current_level'] ?? null,
                'is_verified' => false,
                'is_active' => true,
                'created_by' => null,
                'updated_by' => null,
            ]);

            UserRole::query()->create([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'role_id' => $memberRole->id,
                'assigned_at' => now(),
                'assigned_by' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $plainToken = $this->createEmailVerificationToken($user);

            $verificationUrl = route('email.verify', [
                'email' => $user->email,
                'token' => $plainToken,
            ]);

            Mail::to($user->email)->send(
                new VerifyEmailMail(
                    user: $user,
                    verificationUrl: $verificationUrl
                )
            );

            return [
                'requires_email_verification' => true,
                'verification_sent' => true,
                'verification_expires_in_minutes' => self::EMAIL_VERIFICATION_EXPIRES_MINUTES,
                'user' => [
                    'id' => $user->id,
                    'fullname' => $user->fullname,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'member_code' => $user->member_code,
                    'is_verified' => false,
                ],
            ];
        });
    }

    public function verifyEmailByLink(string $email, string $token): array
    {
        $email = trim($email);
        $token = trim($token);

        if (blank($email) || blank($token)) {
            return [
                'verified' => false,
                'title' => 'Verifikasi Gagal',
                'message' => 'Link verifikasi tidak valid.',
            ];
        }

        return DB::transaction(function () use ($email, $token) {
            $user = User::query()
                ->where('email', $email)
                ->first();

            if (! $user) {
                return [
                    'verified' => false,
                    'title' => 'Verifikasi Gagal',
                    'message' => 'Email tidak ditemukan.',
                ];
            }

            /*
            * Ambil beberapa token terakhir milik user.
            * Karena token_hash memakai Hash::make(), token tidak bisa dicari langsung via WHERE.
            * Jadi kita ambil kandidat token lalu cocokkan satu per satu dengan Hash::check().
            */
            $verificationTokens = EmailVerificationToken::query()
                ->where('user_id', $user->id)
                ->where('email', $user->email)
                ->latest()
                ->limit(10)
                ->get();

            $matchedToken = $verificationTokens->first(function (EmailVerificationToken $verificationToken) use ($token) {
                return Hash::check($token, $verificationToken->token_hash);
            });

            if (! $matchedToken) {
                return [
                    'verified' => false,
                    'title' => 'Link Tidak Valid',
                    'message' => 'Link verifikasi tidak valid. Pastikan Anda membuka link terbaru dari email Lumina Library.',
                ];
            }

            /*
            * Kalau token cocok dan user sudah verified, berarti user klik link yang benar lagi.
            * Ini boleh dianggap success, tapi tetap token wajib cocok.
            */
            if ($user->is_verified) {
                return [
                    'verified' => true,
                    'title' => 'Email Sudah Terverifikasi',
                    'message' => 'Akun Anda sudah aktif. Silakan kembali ke aplikasi dan login.',
                ];
            }

            if ($matchedToken->is_used) {
                return [
                    'verified' => false,
                    'title' => 'Link Sudah Digunakan',
                    'message' => 'Link verifikasi ini sudah pernah digunakan. Silakan login atau minta kirim ulang email verifikasi.',
                ];
            }

            if ($matchedToken->expires_at && $matchedToken->expires_at->isPast()) {
                return [
                    'verified' => false,
                    'title' => 'Link Kedaluwarsa',
                    'message' => 'Link verifikasi sudah kedaluwarsa. Silakan minta kirim ulang email verifikasi.',
                ];
            }

            $matchedToken->forceFill([
                'is_used' => true,
                'verified_at' => now(),
                'updated_at' => now(),
            ])->save();

            $user->forceFill([
                'is_verified' => true,
                'updated_at' => now(),
            ])->save();

            /*
            * Optional: matikan token verifikasi lain yang masih aktif untuk user ini.
            */
            EmailVerificationToken::query()
                ->where('user_id', $user->id)
                ->where('id', '!=', $matchedToken->id)
                ->where('is_used', false)
                ->update([
                    'is_used' => true,
                    'updated_at' => now(),
                ]);

            return [
                'verified' => true,
                'title' => 'Email Berhasil Diverifikasi',
                'message' => 'Akun Lumina Library Anda sudah aktif. Silakan kembali ke aplikasi dan login.',
            ];
        });
    }

    public function login(array $payload): array
    {
        $user = $this->findUserByEmail((string) $payload['email']);

        $this->validateCredentials($user, (string) $payload['password']);

        /** @var User $user */
        $user->loadMissing(['roles', 'branch', 'profileType']);

        $roleCodes = $user->roles
            ->pluck('code')
            ->filter()
            ->values();

        $this->ensureUserCanLoginToMobileApi($roleCodes);

        $deviceName = $this->resolveDeviceName($payload);
        $abilities = $this->resolveTokenAbilities($roleCodes);
        $expiresAt = now()->addDays(self::TOKEN_EXPIRES_DAYS);

        $user->tokens()
            ->where('name', $deviceName)
            ->delete();

        UserSession::query()
            ->where('user_id', $user->id)
            ->where('device_info', $this->resolveDeviceInfo($payload))
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'last_activity_at' => now(),
            ]);

        $accessToken = $user->createToken($deviceName, $abilities, $expiresAt);
        $plainTextToken = $accessToken->plainTextToken;
        $personalAccessTokenId = $accessToken->accessToken->id;

        $userSession = $this->storeUserSession(
            user: $user,
            personalAccessTokenId: $personalAccessTokenId,
            plainTextToken: $plainTextToken,
            payload: $payload,
            expiresAt: $expiresAt
        );

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        return [
            'token' => $plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toISOString(),
            'abilities' => $abilities,
            'session' => [
                'id' => $userSession->id,
                'device_info' => $userSession->device_info,
                'ip_address' => $userSession->ip_address,
                'expires_at' => $userSession->expires_at?->toISOString(),
                'last_activity_at' => $userSession->last_activity_at?->toISOString(),
            ],
            'user' => $this->transformUser($user, $roleCodes->all()),
        ];
    }

    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token) {
            UserSession::query()
                ->where('user_id', $user->id)
                ->where('personal_access_token_id', $token->id)
                ->update([
                    'is_active' => false,
                    'last_activity_at' => now(),
                ]);

            $token->delete();
        }
    }

    public function logoutAll(User $user): void
    {
        UserSession::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'last_activity_at' => now(),
            ]);

        $user->tokens()->delete();
    }

    private function createEmailVerificationToken(User $user): string
    {
        EmailVerificationToken::query()
            ->where('user_id', $user->id)
            ->where('is_used', false)
            ->update([
                'is_used' => true,
            ]);

        $plainToken = Str::random(64);

        EmailVerificationToken::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'email' => $user->email,
            'token_hash' => Hash::make($plainToken),
            'expires_at' => now()->addMinutes(self::EMAIL_VERIFICATION_EXPIRES_MINUTES),
            'verified_at' => null,
            'is_used' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $plainToken;
    }

    private function generateMemberCode(): string
    {
        do {
            $code = 'MBR-' . now()->format('ymd') . '-' . strtoupper(Str::random(6));
        } while (User::query()->where('member_code', $code)->exists());

        return $code;
    }

    private function storeUserSession(
        User $user,
        int $personalAccessTokenId,
        string $plainTextToken,
        array $payload,
        $expiresAt
    ): UserSession {
        $tokenSecret = Str::contains($plainTextToken, '|')
            ? Str::after($plainTextToken, '|')
            : $plainTextToken;

        return UserSession::query()->create([
            'user_id' => $user->id,
            'personal_access_token_id' => $personalAccessTokenId,
            'token_hash' => hash('sha256', $tokenSecret),
            'device_info' => $this->resolveDeviceInfo($payload),
            'ip_address' => request()->ip(),
            'expires_at' => $expiresAt,
            'last_activity_at' => now(),
            'is_active' => true,
            'created_at' => now(),
        ]);
    }

    private function findUserByEmail(string $email): ?User
    {
        return User::query()
            ->with(['roles', 'branch', 'profileType'])
            ->where('email', $email)
            ->first();
    }

    private function validateCredentials(?User $user, string $password): void
    {
        if (! $user || ! Hash::check($password, (string) $user->password_hash)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password tidak sesuai.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Akun kamu sedang tidak aktif.'],
            ]);
        }

        if (! $user->is_verified) {
            throw ValidationException::withMessages([
                'email' => ['Akun kamu belum diverifikasi. Silakan cek email untuk verifikasi akun.'],
            ]);
        }
    }

    private function ensureUserCanLoginToMobileApi($roleCodes): void
    {
        $hasAdminRole = $roleCodes->contains(Role::SUPER_ADMIN)
            || $roleCodes->contains(Role::ADMIN_BRANCH);

        if ($hasAdminRole) {
            throw ValidationException::withMessages([
                'email' => ['Akun admin tidak dapat login melalui API mobile.'],
            ]);
        }

        $hasMobileRole = $roleCodes->contains(Role::STAFF)
            || $roleCodes->contains(Role::MEMBER);

        if (! $hasMobileRole) {
            throw ValidationException::withMessages([
                'email' => ['Akun ini tidak memiliki akses aplikasi mobile.'],
            ]);
        }
    }

    private function resolveTokenAbilities($roleCodes): array
    {
        $abilities = [];

        if ($roleCodes->contains(Role::STAFF)) {
            $abilities[] = 'staff';
            $abilities[] = 'borrowings:scan';
            $abilities[] = 'borrowings:approve';
            $abilities[] = 'borrowings:return';
        }

        if ($roleCodes->contains(Role::MEMBER)) {
            $abilities[] = 'member';
            $abilities[] = 'books:read';
            $abilities[] = 'borrowings:create';
            $abilities[] = 'borrowings:read-own';
            $abilities[] = 'recommendations:read';
        }

        return array_values(array_unique($abilities));
    }

    private function resolveDeviceName(array $payload): string
    {
        $deviceName = trim((string) ($payload['device_name'] ?? ''));

        if ($deviceName === '') {
            return 'mobile-api';
        }

        return mb_substr($deviceName, 0, 100);
    }

    private function resolveDeviceInfo(array $payload): string
    {
        $deviceInfo = trim((string) ($payload['device_info'] ?? ''));

        if ($deviceInfo !== '') {
            return mb_substr($deviceInfo, 0, 500);
        }

        return mb_substr(
            (string) request()->userAgent() ?: $this->resolveDeviceName($payload),
            0,
            500
        );
    }

    private function transformUser(User $user, array $roleCodes): array
    {
        return [
            'id' => $user->id,
            'fullname' => $user->fullname,
            'email' => $user->email,
            'phone' => $user->phone,
            'member_code' => $user->member_code,
            'avatar_url' => $this->resolveFileUrl(
                path: $user->avatar_path ?? $user->avatar_url ?? null,
                isExternal: (bool) ($user->avatar_is_external ?? false)
            ),
            'branch' => $user->branch ? [
                'id' => $user->branch->id,
                'code' => $user->branch->code,
                'name' => $user->branch->name,
            ] : null,
            'profile_type' => $user->profileType ? [
                'id' => $user->profileType->id,
                'code' => $user->profileType->code ?? null,
                'name' => $user->profileType->name,
                'slug' => $user->profileType->slug ?? null,
            ] : null,
            'roles' => $roleCodes,
            'is_staff' => in_array(Role::STAFF, $roleCodes, true),
            'is_member' => in_array(Role::MEMBER, $roleCodes, true),
            'last_login_at' => $user->last_login_at?->toISOString(),
        ];
    }

    private function resolveFileUrl(?string $path, bool $isExternal = false): ?string
    {
        if (! $path) {
            return null;
        }

        if ($isExternal || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::url($path);
    }
}