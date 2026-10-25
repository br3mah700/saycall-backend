<?php

namespace App\GraphQL\Mutations;

use App\Models\Comment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

final class UpdateComment
{
    /**
     * @param  null  $_
     * @param  array{id: string, body: string}  $args
     */
    public function __invoke(mixed $_, array $args): Comment
    {
        $comment = Comment::findOrFail($args['id']);

        if ($comment->user_id !== Auth::id()) {
            throw new \GraphQL\Error\Error('You can only update your own comments.');
        }

        $comment->update([
            'body' => $args['body'],
        ]);

        return $comment->fresh()->load('user');
    }
}
