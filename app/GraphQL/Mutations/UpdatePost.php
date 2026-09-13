<?php

namespace App\GraphQL\Mutations;

use App\Models\Post;

final class UpdatePost
{
    /**
     * Update an existing post.
     *
     * @param  null  $_
     * @param  array{id: string, title?: string, body?: string, published?: bool}  $args
     */
    public function __invoke(mixed $_, array $args): Post
    {
        $post = Post::findOrFail($args['id']);

        $post->update(array_filter([
            'title'     => $args['title'] ?? null,
            'body'      => $args['body'] ?? null,
            'published' => $args['published'] ?? null,
        ], fn ($value) => !is_null($value)));

        return $post->fresh();
    }
}
