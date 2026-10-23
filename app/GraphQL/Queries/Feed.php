<?php

namespace App\GraphQL\Queries;

use App\Models\Post;
use App\Models\PostImage;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final class Feed
{
    /**
     * Cursor-based feed resolver following Relay-style pagination.
     *
     * @param  mixed  $_
     * @param  array{first?: int, after?: string}  $args
     */
    public function __invoke(mixed $_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $first = max(1, min($args['first'] ?? 10, 50));
        $after = $args['after'] ?? null;

        $query = Post::with(['user', 'images'])
            ->where('published', true)
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        // Cursor-based pagination: decode cursor and filter
        if ($after) {
            $cursor = $this->decodeCursor($after);
            if ($cursor) {
                $query->where(function ($q) use ($cursor) {
                    $q->where('created_at', '<', $cursor['created_at'])
                      ->orWhere(function ($q2) use ($cursor) {
                          $q2->where('created_at', '=', $cursor['created_at'])
                             ->where('id', '<', $cursor['id']);
                      });
                });
            }
        }

        // Fetch one extra to determine if there's a next page
        $posts = $query->limit($first + 1)->get();

        $hasNextPage = $posts->count() > $first;
        if ($hasNextPage) {
            $posts = $posts->slice(0, $first);
        }

        $edges = $posts->map(function (Post $post) {
            return [
                'cursor' => $this->encodeCursor($post),
                'node'   => $post,
            ];
        })->values()->all();

        $endCursor = $posts->isNotEmpty()
            ? $this->encodeCursor($posts->last())
            : null;

        return [
            'edges'   => $edges,
            'pageInfo' => [
                'hasNextPage' => $hasNextPage,
                'endCursor'   => $endCursor,
            ],
        ];
    }

    /**
     * Encode a post into a base64 cursor.
     */
    private function encodeCursor(Post $post): string
    {
        return base64_encode(json_encode([
            'id'          => $post->id,
            'created_at'  => $post->created_at->toDateTimeString(),
        ]));
    }

    /**
     * Decode a cursor back into its components.
     *
     * @return array{id: int, created_at: string}|null
     */
    private function decodeCursor(string $cursor): ?array
    {
        $decoded = json_decode(base64_decode($cursor, true), true);
        if (! is_array($decoded) || ! isset($decoded['id'], $decoded['created_at'])) {
            return null;
        }

        return $decoded;
    }
}
