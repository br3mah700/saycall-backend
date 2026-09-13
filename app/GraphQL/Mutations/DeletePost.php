<?php

namespace App\GraphQL\Mutations;

use App\Models\Post;

final class DeletePost
{
    /**
     * Delete a post by ID.
     *
     * @param  null  $_
     * @param  array{id: string}  $args
     */
    public function __invoke(mixed $_, array $args): Post
    {
        $post = Post::findOrFail($args['id']);
        $post->delete();

        return $post;
    }
}
