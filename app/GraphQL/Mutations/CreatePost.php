<?php

namespace App\GraphQL\Mutations;

use App\Models\Post;
use Illuminate\Support\Facades\Auth;

final class CreatePost
{
    /**
     * Create a new post.
     *
     * @param  null  $_
     * @param  array{title: string, body: string, published?: bool}  $args
     */
    public function __invoke(mixed $_, array $args): Post
    {
        return Post::create([
            'title'     => $args['title'],
            'body'      => $args['body'],
            'published' => $args['published'] ?? false,
            'user_id'   => $args['user_id'],
        ]);
    }
}
