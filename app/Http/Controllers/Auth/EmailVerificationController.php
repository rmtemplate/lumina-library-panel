<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Api\Auth\ApiAuthenticationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function __construct(
        private readonly ApiAuthenticationService $authService
    ) {
    }

    public function verify(Request $request): View
    {
        $result = $this->authService->verifyEmailByLink(
            email: (string) $request->query('email'),
            token: (string) $request->query('token')
        );

        return view('emails.auth.email-verified', [
            'status' => $result['verified'] ? 'success' : 'failed',
            'title' => $result['title'],
            'message' => $result['message'],
        ]);
    }
}