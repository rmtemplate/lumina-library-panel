<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Auth\ApiLoginRequest;
use App\Http\Requests\Api\Auth\ApiRegisterRequest;
use App\Services\Api\Auth\ApiAuthenticationService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthenticationController extends ApiController
{
    public function __construct(
        private readonly ApiAuthenticationService $authService
    ) {
    }

    public function login(ApiLoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return $this->success(
            data: $result,
            message: 'Login berhasil.',
            code: 'AUTH_LOGIN_SUCCESS'
        );
    }

    public function register(ApiRegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->created(
            data: $result,
            message: 'Registrasi berhasil. Silakan cek email untuk verifikasi akun.',
            code: 'AUTH_REGISTER_SUCCESS'
        );
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['roles', 'branch', 'profileType']);

        return $this->success(
            data: [
                'user' => $user,
                'roles' => $user->roles->pluck('code')->values(),
            ],
            message: 'Data user berhasil diambil.',
            code: 'AUTH_ME_SUCCESS'
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->success(
            message: 'Logout berhasil.',
            code: 'AUTH_LOGOUT_SUCCESS'
        );
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $this->authService->logoutAll($request->user());

        return $this->success(
            message: 'Semua session berhasil logout.',
            code: 'AUTH_LOGOUT_ALL_SUCCESS'
        );
    }
}