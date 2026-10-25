<?php

namespace App\GraphQL\Mutations;

use App\Models\Reply;
use Illuminate\Support\Facades\Auth;

final class DeleteReply
{
    /**
     * @param  null  $_
     * @param  array{id: string}  $args
     */
    public function __invoke(mixed $_, array $args): Reply
    {
        $reply = Reply::findOrFail($args['id']);

        if ($reply->user_id !== Auth::id()) {
            throw new \GraphQL\Error\Error('You can only delete your own replies.');
        }

        $reply->delete();

        return $reply;
    }
}
