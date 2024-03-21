<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}

// class AuthController extends Controller
// {
//     public function createToken(Request $request)
//     {
//         $credentials = $request->only('email', 'password');

//         if (! $token = JWTAuth::attempt($credentials)) {
//             return response()->json(['error' => 'Unauthorized'], 401);
//         }

//         return $this->respondWithToken($token);
//     }

//     protected function respondWithToken($token)
//     {
//         return response()->json([
//             'access_token' => $token,
//             'token_type' => 'bearer',
//             'expires_in' => JWTAuth::factory()->getTTL() * 60
//         ]);
//     }
// }