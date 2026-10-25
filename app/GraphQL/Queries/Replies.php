<?php

namespace App\GraphQL\Queries;

use App\Models\Reply;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final class Replies
{
    /**
     * Cursor-based replies resolver for a comment.
     *
     * @param  mixed  $_
     * @param  array{comment_id: int, first?: int, after?: string}  $args
     */
    public function __invoke(mixed $_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $first = max(1, min($args['first'] ?? 10, 50));
        $after = $args['after'] ?? null;

        $query = Reply::with(['user'])
            ->where('comment_id', $args['comment_id'])
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

        $replies = $query->limit($first + 1)->get();

        $hasNextPage = $replies->count() > $first;
        if ($hasNextPage) {
            $replies = $replies->slice(0, $first);
        }

        $edges = $replies->map(function (Reply $reply) {
            return [
                'cursor' => $this->encodeCursor($reply),
                'node'   => $reply,
            ];
        })->values()->all();

        $endCursor = $replies->isNotEmpty()
            ? $this->encodeCursor($replies->last())
            : null;

        return [
            'edges'   => $edges,
            'pageInfo' => [
                'hasNextPage' => $hasNextPage,
                'endCursor'   => $endCursor,
            ],
        ];
    }

    private function encodeCursor(Reply $reply): string
    {
        return base64_encode(json_encode([
            'id'          => $reply->id,
            'created_at'  => $reply->created_at->toDateTimeString(),
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
