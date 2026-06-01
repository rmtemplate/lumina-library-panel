<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Account\UpdateAvatarRequest;
use App\Http\Requests\Api\Account\UpdatePasswordRequest;
use App\Http\Requests\Api\Account\UpdateProfileRequest;
use App\Http\Resources\Api\Account\AccountProfileResource;
use App\Services\Api\Account\AccountService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends ApiController
{
    public function __construct(
        private readonly AccountService $accountService
    ) {
    }

    /**
     * GET /api/v1/account/profile
     *
     * Ambil data profil user yang sedang login.
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $this->accountService->getProfile($request->user());

        return ApiResponse::success(
            data: new AccountProfileResource($user),
            message: 'Profil berhasil diambil.',
            code: 'ACCOUNT_PROFILE_SUCCESS'
        );
    }

    /**
     * PUT /api/v1/account/profile
     *
     * Update field profil yang diizinkan.
     * Email, role, branch tidak bisa diubah dari endpoint ini.
     * staff_position hanya bisa diubah oleh user dengan role staff.
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->accountService->updateProfile(
            user: $request->user(),
            payload: $request->validated()
        );

        return ApiResponse::success(
            data: new AccountProfileResource($user),
            message: 'Profil berhasil diperbarui.',
            code: 'ACCOUNT_PROFILE_UPDATED'
        );
    }

    /**
     * POST /api/v1/account/avatar
     *
     * Upload atau ganti avatar profil.
     * Avatar lama akan dihapus jika local.
     */
    public function updateAvatar(UpdateAvatarRequest $request): JsonResponse
    {
        $user = $this->accountService->updateAvatar(
            user: $request->user(),
            file: $request->file('avatar')
        );

        return ApiResponse::success(
            data: new AccountProfileResource($user),
            message: 'Foto profil berhasil diperbarui.',
            code: 'ACCOUNT_AVATAR_UPDATED'
        );
    }

    /**
     * DELETE /api/v1/account/avatar
     *
     * Hapus avatar profil.
     * File fisik akan dihapus jika bukan external.
     */
    public function deleteAvatar(Request $request): JsonResponse
    {
        $user = $this->accountService->deleteAvatar($request->user());

        return ApiResponse::success(
            data: new AccountProfileResource($user),
            message: 'Foto profil berhasil dihapus.',
            code: 'ACCOUNT_AVATAR_DELETED'
        );
    }

    /**
     * PUT /api/v1/account/password
     *
     * Update password user.
     * current_password diverifikasi terhadap password_hash.
     * Session tidak di-logout otomatis.
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $this->accountService->updatePassword(
            user: $request->user(),
            payload: $request->validated()
        );

        return ApiResponse::success(
            message: 'Password berhasil diperbarui.',
            code: 'ACCOUNT_PASSWORD_UPDATED'
        );
    }
}
