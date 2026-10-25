<?php

namespace App\GraphQL\Mutations;

use App\Models\Comment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

final class CreateComment
{
    /**
     * @param  null  $_
     * @param  array{post_id: string, body: string}  $args
     */
    public function __invoke(mixed $_, array $args): Comment
    {
        $validator = Validator::make($args, [
            'post_id' => ['required', 'exists:posts,id'],
            'body'    => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            throw new \GraphQL\Error\Error($validator->errors()->first());
        }

        $comment = Comment::create([
            'post_id' => $args['post_id'],
            'user_id' => Auth::id(),
            'body'    => $args['body'],
        ]);

        return $comment->load('user');
    }
}
