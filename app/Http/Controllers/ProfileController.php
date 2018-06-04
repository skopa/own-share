<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => $request->user()
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $this->validate($request, [
            'email' => 'email|unique:users,email,'.$user->id,
            'username' => 'string|unique:users,username,'.$user->id,
            'password' => 'confirmed'
        ]);

        $user->update($request->all());

        return response()->json([
            'status' => 'success',
            'data' => $user->toArray()
        ]);
    }
}
