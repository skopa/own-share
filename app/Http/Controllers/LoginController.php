<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        $this->validate($request, [
            'username' => 'required',
            'password' => 'required'
        ]);

        /** @var User $user */
        $user = User::where('username', $request->get('username'))->first();

        if (Hash::check($request->get('password'), $user->password)) {
            if ($user->api_token == null || $request->has('logout_other')) {
                $user->api_token = base64_encode(str_random(40));
                $user->save();
            }
            return response()->json([
                'status' => 'success',
                'api_token' => $user->api_token
            ]);
        }

        return response()->json([
            'status' => 'fail'
        ], 401);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $user->api_token = base64_encode(str_random(40));
        $user->save();

        return response()->json([], 204);
    }
}
