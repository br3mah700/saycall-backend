<?php

namespace App\GraphQL\Mutations;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

final class Register
{
    /**
     * Register a new user and return user with access token.
     *
     * @param  null  $_
     * @param  array{name: string, email: string, password: string, device_name?: string}  $args
     * @return array{token: string, user: User}
     */
    public function __invoke(mixed $_, array $args): array
    {
        $user = User::create([
            'name'     => $args['name'],
            'email'    => $args['email'],
            'password' => Hash::make($args['password']),
        ]);

        $deviceName = $args['device_name'] ?? 'expo-app';
        $token = $user->createToken($deviceName)->plainTextToken;

        return [
            'token' => $token,
            'user'  => $user,
        ];
    }
}

