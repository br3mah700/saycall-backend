<?php

namespace App\GraphQL\Queries;

use App\Models\Comment;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final class Comments
{
    /**
     * Cursor-based comments resolver for a post.
     *
     * @param  mixed  $_
     * @param  array{post_id: int, first?: int, after?: string}  $args
     */
    public function __invoke(mixed $_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $first = max(1, min($args['first'] ?? 10, 50));
        $after = $args['after'] ?? null;

        $query = Comment::with(['user', 'replies.user'])
            ->where('post_id', $args['post_id'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

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

        $comments = $query->limit($first + 1)->get();

        $hasNextPage = $comments->count() > $first;
        if ($hasNextPage) {
            $comments = $comments->slice(0, $first);
        }

        $edges = $comments->map(function (Comment $comment) {
            return [
                'cursor' => $this->encodeCursor($comment),
                'node'   => $comment,
            ];
        })->values()->all();

        $endCursor = $comments->isNotEmpty()
            ? $this->encodeCursor($comments->last())
            : null;

        return [
            'edges'   => $edges,
            'pageInfo' => [
                'hasNextPage' => $hasNextPage,
                'endCursor'   => $endCursor,
            ],
        ];
    }

    private function encodeCursor(Comment $comment): string
    {
        return base64_encode(json_encode([
            'id'          => $comment->id,
            'created_at'  => $comment->created_at->toDateTimeString(),
        ]));
    }

    private function decodeCursor(string $cursor): ?array
    {
        $decoded = json_decode(base64_decode($cursor, true), true);
        if (! is_array($decoded) || ! isset($decoded['id'], $decoded['created_at'])) {
            return null;
        }

        return $decoded;
    }
}
