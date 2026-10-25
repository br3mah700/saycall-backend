<?php

namespace App\GraphQL\Mutations;

use App\Models\Comment;
use Illuminate\Support\Facades\Auth;

final class DeleteComment
{
    /**
     * @param  null  $_
     * @param  array{id: string}  $args
     */
    public function __invoke(mixed $_, array $args): Comment
    {
        $comment = Comment::findOrFail($args['id']);

        if ($comment->user_id !== Auth::id()) {
            throw new \GraphQL\Error\Error('You can only delete your own comments.');
        }

        $comment->delete();

        return $comment;
    }
}
