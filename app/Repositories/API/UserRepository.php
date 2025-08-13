<?php

namespace App\Repositories\API;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserRepository
{
    public function createUser(string $name, string $email, string $password): User
    {
        return User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password)
            ]);
    }

    public function getUserByEmail(string $email): User
    {
        return User::where('email', $email)->first();
    }
}