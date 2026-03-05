<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\UserResource;

class LoginController extends Controller
{
    public function store(LoginRequest $request): array
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        $token = $user->createToken('main')->plainTextToken;

        return [
            'user' => new UserResource($user),
            'token' => $token
        ];
    }

    public function destroy(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
