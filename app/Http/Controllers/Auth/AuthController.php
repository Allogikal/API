<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function logIn(LoginRequest $request):JsonResponse {
        if (!Auth::attempt($request->all())) {
            return response()->json([
                "error" => [
                    "code" => 401,
                    "message" => "Неудачная авторизация!"
                ]
            ], 401, [ "Content-type" => "application/json" ]);
        }
        $user = Auth::user();
        $token = $user->createToken('user_token')->plainTextToken;

        return response()->json([
            "data" => [
                "user_token" => $token
            ]
        ], 200, [ "Content-type" => "application/json" ]);
    }

    public function logOut():JsonResponse {
        auth()->user()->tokens()->delete();

        return response()->json([
            "data" => [
                "message" => "Успешный выход!"
            ]
        ], 200, [ "Content-type" => "application/json" ]);
    }

    public function unauthorization():JsonResponse {
        return response()->json([
            'error' => [
                'code' => 403,
                'message' => 'Вы не авторизованы!'
            ]
        ], 403, [ "Content-type" => "application/json" ]);
    }
}
