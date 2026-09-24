<?php

namespace App\GraphQL\Mutations;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

final class Logout
{
    /**
     * Revoke the current user's personal access token.
     *
     * @param  null  $_
     * @param  array<string, mixed>  $args
     * @return array{status: string, message: string}
     */
    public function __invoke(mixed $_, array $args): array
    {
        /** @var User|null $user */
        $user = Auth::guard('sanctum')->user() ?? Auth::user();

        if ($user) {
            $token = $user->currentAccessToken();
            if ($token && method_exists($token, 'delete')) {
                $token->delete();
            }
        }

        return [
            'status'  => 'SUCCESS',
            'message' => 'Successfully logged out.',
        ];
    }
}

