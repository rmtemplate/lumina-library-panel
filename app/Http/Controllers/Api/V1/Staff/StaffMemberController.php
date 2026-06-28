<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Staff\StaffRegisterMemberRequest;
use App\Services\Api\Staff\Member\StaffMemberService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StaffMemberController extends ApiController
{
    public function __construct(
        private readonly StaffMemberService $service
    ) {
    }

    /**
     * POST /api/v1/staff/members
     *
     * Walk-in member registration. The generated password is returned
     * ONCE in this response — it is never retrievable again afterwards
     * (only its hash is stored), so the staff must communicate it to the
     * member right now.
     */
    public function register(StaffRegisterMemberRequest $request): JsonResponse
    {
        $result = $this->service->register(
            staff: $request->user(),
            payload: $request->validated(),
            photo: $request->file('photo')
        );

        $user = $result['user'];

        return ApiResponse::success(
            data: [
                'user' => [
                    'id' => $user->id,
                    'fullname' => $user->fullname,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'member_code' => $user->member_code,
                    'avatar_url' => $user->avatar_url,
                ],
                'generated_password' => $result['generated_password'],
            ],
            message: 'Member berhasil didaftarkan.',
            code: 'STAFF_MEMBER_REGISTERED'
        );
    }

    /**
     * GET /api/v1/staff/members/search?q=...
     */
    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if ($query === '') {
            throw ValidationException::withMessages(['q' => ['Kata kunci pencarian wajib diisi.']]);
        }

        $members = $this->service->search($request->user(), $query);

        return ApiResponse::success(
            data: $members->map(fn ($member) => [
                'id' => $member->id,
                'fullname' => $member->fullname,
                'email' => $member->email,
                'phone' => $member->phone,
                'member_code' => $member->member_code,
                'avatar_url' => $member->avatar_url,
            ])->values(),
            message: 'Pencarian member berhasil.',
            code: 'STAFF_MEMBER_SEARCH_SUCCESS'
        );
    }

    /**
     * GET /api/v1/staff/members/lookup?code=...
     */
    public function lookup(Request $request): JsonResponse
    {
        $code = trim((string) $request->query('code', ''));

        if ($code === '') {
            throw ValidationException::withMessages(['code' => ['Kode member wajib diisi.']]);
        }

        $member = $this->service->lookup($request->user(), $code);

        if (! $member) {
            return ApiResponse::success(
                data: ['found' => false, 'member' => null],
                message: 'Member tidak ditemukan.',
                code: 'STAFF_MEMBER_NOT_FOUND'
            );
        }

        return ApiResponse::success(
            data: [
                'found' => true,
                'member' => [
                    'id' => $member->id,
                    'fullname' => $member->fullname,
                    'email' => $member->email,
                    'phone' => $member->phone,
                    'member_code' => $member->member_code,
                    'avatar_url' => $member->avatar_url,
                ],
            ],
            message: 'Member ditemukan.',
            code: 'STAFF_MEMBER_FOUND'
        );
    }
}
