<?php

namespace App\GraphQL\Mutations;

use App\Models\Reply;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

final class UpdateReply
{
    /**
     * @param  null  $_
     * @param  array{id: string, body: string}  $args
     */
    public function __invoke(mixed $_, array $args): Reply
    {
        $reply = Reply::findOrFail($args['id']);

        if ($reply->user_id !== Auth::id()) {
            throw new \GraphQL\Error\Error('You can only update your own replies.');
        }

        $reply->update([
            'body' => $args['body'],
        ]);

        return $reply->fresh()->load('user');
    }
}
