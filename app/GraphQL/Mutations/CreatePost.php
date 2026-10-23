<?php

namespace App\GraphQL\Mutations;

use App\Models\Post;
use App\Models\PostImage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

final class CreatePost
{
    /**
     * Create a new post with optional images.
     *
     * @param  null  $_
     * @param  array{title: string, body: string, published?: bool, user_id: string, images?: list<array{url: string, alt?: string>}>  $args
     */
    public function __invoke(mixed $_, array $args): Post
    {
        $validator = Validator::make($args, [
            'title'     => ['required', 'string', 'max:255'],
            'body'      => ['required', 'string'],
            'published' => ['sometimes', 'boolean'],
            'user_id'   => ['required', 'exists:users,id'],
            'images'    => ['sometimes', 'array'],
        ]);

        if ($validator->fails()) {
            throw new \GraphQL\Error\Error($validator->errors()->first());
        }

        $post = Post::create([
            'title'     => $args['title'],
            'body'      => $args['body'],
            'published' => $args['published'] ?? false,
            'user_id'   => $args['user_id'],
        ]);

        if (! empty($args['images'])) {
            foreach ($args['images'] as $imageData) {
                PostImage::create([
                    'post_id' => $post->id,
                    'url'     => $imageData['url'],
                    'alt'     => $imageData['alt'] ?? null,
                ]);
            }
        }

        return $post->load('images');
    }
}
