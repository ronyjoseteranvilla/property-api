<?php

namespace App\Services\API;
use App\Repositories\API\UserRepository;
use Illuminate\Support\Facades\Auth;
class AuthService
{
    protected UserRepository $userRepository;


    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;    
    }

    public function createUser(string $name, string $email, string $password): string
    {
        $user = $this->userRepository->createUser($name, $email, $password);

        return $user->createToken('API TOKEN')->plainTextToken;
    }

    public function login(string $email, string $password): string
    {
        if(!Auth::attempt(['email'=> $email,'password'=> $password])) {
            abort(401, 'Email11 & Password does not match with our record.');
        }

        $user = $this->userRepository->getUserByEmail($email);

        return $user->createToken('API TOKEN')->plainTextToken;
    }
}