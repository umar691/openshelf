<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $abilities = $request->user()->hasAnyRole(['author', 'instructor', 'freelancer', 'employer', 'vendor', 'admin'])
            ? ['read', 'write']
            : ['read'];
        $token = $request->user()->createToken($data['name'], $abilities);

        return response()->json([
            'token' => $token->plainTextToken,
            'abilities' => $abilities,
        ], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['revoked' => true]);
    }
}
