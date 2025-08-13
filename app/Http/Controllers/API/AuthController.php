<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\CreateUserRequest;
use App\Http\Requests\API\UserLoginRequest;
use App\Services\API\AuthService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class AuthController extends Controller
{

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    public function createUser(CreateUserRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $userToken = $this->authService->createUser($data['name'], $data['email'], $data['password']);  

            return response()->json([
                'status' => true,
                'message' => 'User Created Successfully',
                'token' => $userToken
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }

    }

    public function login(UserLoginRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            
            $userToken = $this->authService->login($data['email'], $data['password']);

            return response()->json([
                'status' => true,
                'message' => 'User Logged In Successfully',
                'token' => $userToken
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
