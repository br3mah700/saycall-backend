<?php

namespace App\GraphQL\Mutations;

use App\Models\Reply;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

final class CreateReply
{
    /**
     * @param  null  $_
     * @param  array{comment_id: string, body: string}  $args
     */
    public function __invoke(mixed $_, array $args): Reply
    {
        $validator = Validator::make($args, [
            'comment_id' => ['required', 'exists:comments,id'],
            'body'       => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            throw new \GraphQL\Error\Error($validator->errors()->first());
        }

        $reply = Reply::create([
            'comment_id' => $args['comment_id'],
            'user_id'    => Auth::id(),
            'body'       => $args['body'],
        ]);

        return $reply->load('user');
    }
}
