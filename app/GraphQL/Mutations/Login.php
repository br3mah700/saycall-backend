<?php

namespace App\GraphQL\Mutations;

use App\Models\User;
use GraphQL\Error\Error;
use Illuminate\Support\Facades\Hash;

final class Login
{
    /**
     * Authenticate user credentials and return an access token.
     *
     * @param  null  $_
     * @param  array{email: string, password: string, device_name?: string}  $args
     * @return array{token: string, user: User}
     * @throws Error
     */
    public function __invoke(mixed $_, array $args): array
    {
        $user = User::where('email', $args['email'])->first();

        if (! $user || ! Hash::check($args['password'], $user->password)) {
            throw new Error('Invalid email or password.');
        }

        $deviceName = $args['device_name'] ?? 'expo-app';
        $token = $user->createToken($deviceName)->plainTextToken;

        return [
            'token' => $token,
            'user'  => $user,
        ];
    }
}

