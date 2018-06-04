<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function register(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email|unique:users',
            'username' => 'required|string|unique:users',
            'password' => 'required|confirmed'
        ]);

        $user = User::create($request->only(['email', 'username', 'password']));
        $user->api_token = base64_encode(str_random(40));
        $user->save();

        return response()->json([
            'status' => 'success',
            'api_token' => $user->api_token,
            'data' => $user->toArray()
        ]);
    }
}
