# Comments & Replies — Usage Guide (GraphQL)

## Overview

Comments and replies enable threaded discussion on posts. A `Comment` belongs to a `Post` and a `User`, and has many `Reply`s. A `Reply` belongs to one `Comment` and one `User`.

All comment/reply mutations require authentication (`@guard`). Users can only update or delete their own comments/replies.

Comments and replies are fetched with cursor-based pagination (Relay-style), consistent with the `feed` query.

## Database

| Table | Columns |
|-------|---------|
| `comments` | `id`, `post_id` (FK), `user_id` (FK), `body` (text), `created_at`, `updated_at` |
| `replies` | `id`, `comment_id` (FK), `user_id` (FK), `body` (text), `created_at`, `updated_at` |

Cascade delete: deleting a post deletes its comments; deleting a comment deletes its replies.

## Schema (GraphQL)

### Types

```graphql
type Comment {
    id: ID!
    body: String!
    post: Post! @belongsTo
    user: User! @belongsTo
    replies: [Reply!]! @hasMany
    created_at: DateTime!
    updated_at: DateTime!
}

type Reply {
    id: ID!
    body: String!
    comment: Comment! @belongsTo
    user: User! @belongsTo
    created_at: DateTime!
    updated_at: DateTime!
}

type CommentEdge {
    cursor: String!
    node: Comment!
}

type CommentConnection {
    edges: [CommentEdge!]!
    pageInfo: PageInfo!
}

type ReplyEdge {
    cursor: String!
    node: Reply!
}

type ReplyConnection {
    edges: [ReplyEdge!]!
    pageInfo: PageInfo!
}
```

### Queries

```graphql
type Query {
    "Fetch cursor-paginated comments for a post."
    comments(
        post_id: ID!
        first: Int = 10
        after: String
    ): CommentConnection! @field(resolver: "App\\GraphQL\\Queries\\Comments")

    "Fetch cursor-paginated replies for a comment."
    replies(
        comment_id: ID!
        first: Int = 10
        after: String
    ): ReplyConnection! @field(resolver: "App\\GraphQL\\Queries\\Replies")
}
```

### Mutations

```graphql
type Mutation {
    "Create a comment on a post."
    createComment(
        post_id: ID!
        body: String!
    ): Comment! @guard @field(resolver: "App\\GraphQL\\Mutations\\CreateComment")

    "Update a comment's body."
    updateComment(
        id: ID!
        body: String!
    ): Comment! @guard @field(resolver: "App\\GraphQL\\Mutations\\UpdateComment")

    "Delete a comment by ID."
    deleteComment(id: ID!): Comment! @guard @field(resolver: "App\\GraphQL\\Mutations\\DeleteComment")

    "Create a reply to a comment."
    createReply(
        comment_id: ID!
        body: String!
    ): Reply! @guard @field(resolver: "App\\GraphQL\\Mutations\\CreateReply")

    "Update a reply's body."
    updateReply(
        id: ID!
        body: String!
    ): Reply! @guard @field(resolver: "App\\GraphQL\\Mutations\\UpdateReply")

    "Delete a reply by ID."
    deleteReply(id: ID!): Reply! @guard @field(resolver: "App\\GraphQL\\Mutations\\DeleteReply")
}
```

## Apollo Client Operations (Expo React Native)

```typescript
import { gql, useMutation, useQuery } from '@apollo/client';

// ─── Queries ───

export const GET_COMMENTS = gql`
  query GetComments($post_id: ID!, $first: Int, $after: String) {
    comments(post_id: $post_id, first: $first, after: $after) {
      edges {
        cursor
        node {
          id
          body
          created_at
          updated_at
          user { id name }
          replies {
            id
            body
            created_at
            user { id name }
          }
        }
      }
      pageInfo {
        hasNextPage
        endCursor
      }
    }
  }
`;

export const GET_REPLIES = gql`
  query GetReplies($comment_id: ID!, $first: Int, $after: String) {
    replies(comment_id: $comment_id, first: $first, after: $after) {
      edges {
        cursor
        node {
          id
          body
          created_at
          updated_at
          user { id name }
        }
      }
      pageInfo {
        hasNextPage
        endCursor
      }
    }
  }
`;

// ─── Mutations ───

export const CREATE_COMMENT = gql`
  mutation CreateComment($post_id: ID!, $body: String!) {
    createComment(post_id: $post_id, body: $body) {
      id
      body
      created_at
      user { id name }
    }
  }
`;

export const UPDATE_COMMENT = gql`
  mutation UpdateComment($id: ID!, $body: String!) {
    updateComment(id: $id, body: $body) {
      id
      body
      updated_at
    }
  }
`;

export const DELETE_COMMENT = gql`
  mutation DeleteComment($id: ID!) {
    deleteComment(id: $id) {
      id
      body
    }
  }
`;

export const CREATE_REPLY = gql`
  mutation CreateReply($comment_id: ID!, $body: String!) {
    createReply(comment_id: $comment_id, body: $body) {
      id
      body
      created_at
      user { id name }
    }
  }
`;

export const UPDATE_REPLY = gql`
  mutation UpdateReply($id: ID!, $body: String!) {
    updateReply(id: $id, body: $body) {
      id
      body
      updated_at
    }
  }
`;

export const DELETE_REPLY = gql`
  mutation DeleteReply($id: ID!) {
    deleteReply(id: $id) {
      id
      body
    }
  }
`;
```

## React Native Component Example but design based on your current UI

```typescript
import { useQuery, useMutation } from '@apollo/client';
import { Button, TextInput, FlatList, View, Text, ActivityIndicator, Alert } from 'react-native';
import { useAuth } from '../context/AuthContext';
import {
  GET_COMMENTS,
  CREATE_COMMENT,
  UPDATE_COMMENT,
  DELETE_COMMENT,
  GET_REPLIES,
  CREATE_REPLY,
} from '../graphql/commentOperations';

interface CommentNode {
  id: string;
  body: string;
  created_at: string;
  user: { id: string; name: string };
  replies?: Array<{
    id: string;
    body: string;
    created_at: string;
    user: { id: string; name: string };
  }>;
}

export function CommentThreadScreen({ postId }: { postId: string }) {
  const { token, user } = useAuth();
  const [newBody, setNewBody] = useState('');

  const { data, loading, fetchMore, refetch } = useQuery(GET_COMMENTS, {
    variables: { post_id: postId, first: 10 },
    context: { headers: { Authorization: `Bearer ${token}` } },
  });

  const [createComment] = useMutation(CREATE_COMMENT, {
    context: { headers: { Authorization: `Bearer ${token}` } },
    refetchQueries: [{ query: GET_COMMENTS, variables: { post_id: postId, first: 10 } }],
  });

  const handleCreateComment = async () => {
    if (!newBody.trim() || !user) return;
    try {
      await createComment({
        variables: { post_id: postId, body: newBody.trim() },
      });
      setNewBody('');
    } catch (error) {
      Alert.alert('Error', (error as Error).message);
    }
  };

  const comments = data?.comments?.edges?.map(e => e.node) ?? [];

  if (loading) return <ActivityIndicator size="large" />;

  return (
    <View>
      <FlatList
        data={comments}
        keyExtractor={(item) => item.id}
        renderItem={({ item }) => (
          <View style={{ padding: 10, borderBottomWidth: 1 }}>
            <Text>{item.user.name}</Text>
            <Text>{item.body}</Text>
            <Text>{item.created_at}</Text>
            {user && item.user.id === user.id && (
              <>
                <TextInput
                  value={newBody}
                  onChangeText={setNewBody}
                  placeholder="Edit comment..."
                />
                <Button title="Update" onPress={() => {
                  // implement update flow
                }} />
                <Button title="Delete" onPress={() => {
                  // implement delete flow
                }} />
              </>
            )}
            <RepliesList commentId={item.id} token={token} />
          </View>
        )}
      />

      <View style={{ padding: 10, flexDirection: 'row' }}>
        <TextInput
          value={newBody}
          onChangeText={setNewBody}
          placeholder="Write a comment..."
          style={{ flex: 1, borderWidth: 1, padding: 8 }}
        />
        <Button title="Post" onPress={handleCreateComment} />
      </View>
    </View>
  );
}

function RepliesList({ commentId, token }: { commentId: string; token: string }) {
  const { data, loading } = useQuery(GET_REPLIES, {
    variables: { comment_id: commentId, first: 10 },
    context: { headers: { Authorization: `Bearer ${token}` } },
    skip: !commentId,
  });

  const [createReply] = useMutation(CREATE_REPLY, {
    context: { headers: { Authorization: `Bearer ${token}` } },
    refetchQueries: [{ query: GET_REPLIES, variables: { comment_id: commentId, first: 10 } }],
  });

  const [replyBody, setReplyBody] = useState('');

  if (loading) return <ActivityIndicator size="small" />;

  const replies = data?.replies?.edges?.map(e => e.node) ?? [];

  return (
    <View style={{ paddingLeft: 20 }}>
      {replies.map((reply) => (
        <View key={reply.id} style={{ padding: 5 }}>
          <Text>{reply.user.name}</Text>
          <Text>{reply.body}</Text>
        </View>
      ))}
      <TextInput
        value={replyBody}
        onChangeText={setReplyBody}
        placeholder="Write a reply..."
        style={{ borderWidth: 1, padding: 8 }}
      />
      <Button
        title="Reply"
        onPress={async () => {
          if (!replyBody.trim()) return;
          try {
            await createReply({
              variables: { comment_id: commentId, body: replyBody.trim() },
            });
            setReplyBody('');
          } catch (error) {
            Alert.alert('Error', (error as Error).message);
          }
        }}
      />
    </View>
  );
}
```

## Response Shapes

### Create Comment

```json
{
  "data": {
    "createComment": {
      "id": "1",
      "body": "Great post!",
      "created_at": "2026-10-24 10:00:00",
      "user": { "id": "1", "name": "Alice" }
    }
  }
}
```

### Fetch Comments

```json
{
  "data": {
    "comments": {
      "edges": [
        {
          "cursor": "eyJpZCI6MSwiY3JlYXRlZF9hdCI6IjIwMjYtMTAtMjQgMTA6MDA6MDAifQ==",
          "node": {
            "id": "1",
            "body": "Great post!",
            "created_at": "2026-10-24 10:00:00",
            "updated_at": "2026-10-24 10:00:00",
            "user": { "id": "1", "name": "Alice" },
            "replies": [
              {
                "id": "1",
                "body": "Thanks!",
                "created_at": "2026-10-24 10:05:00",
                "user": { "id": "2", "name": "Bob" }
              }
            ]
          }
        }
      ],
      "pageInfo": {
        "hasNextPage": false,
        "endCursor": "eyJpZCI6MSwiY3JlYXRlZF9hdCI6IjIwMjYtMTAtMjQgMTA6MDA6MDAifQ=="
      }
    }
  }
}
```

### Create Reply

```json
{
  "data": {
    "createReply": {
      "id": "1",
      "body": "Thanks!",
      "created_at": "2026-10-24 10:05:00",
      "user": { "id": "2", "name": "Bob" }
    }
  }
}
```

## Pagination

Both `comments` and `replies` use cursor-based pagination:

- `first` (default 10, max 50): number of items per page
- `after`: opaque cursor returned from `pageInfo.endCursor` of the previous page

To fetch the next page, pass the previous `endCursor` as `after`:

```typescript
const { data, fetchMore } = useQuery(GET_COMMENTS, {
  variables: { post_id: postId, first: 10 },
});

const loadMore = async () => {
  const endCursor = data?.comments?.pageInfo?.endCursor;
  if (!endCursor) return;
  await fetchMore({
    variables: { after: endCursor },
    updateQuery: (prev, { fetchMoreResult }) => {
      if (!fetchMoreResult) return prev;
      return {
        ...fetchMoreResult,
        comments: {
          ...fetchMoreResult.comments,
          edges: [...prev.comments.edges, ...fetchMoreResult.comments.edges],
        },
      };
    },
  });
};
```

## Authorization

- `createComment`, `createReply`: require authenticated user (`@guard`). `user_id` is set from the auth token, not from the request args.
- `updateComment`, `updateReply`, `deleteComment`, `deleteReply`: require the authenticated user to be the author of the comment/reply. Otherwise returns a GraphQL error: `"You can only update/delete your own comments/replies."`

## Error Handling

| Situation | Error |
|-----------|-------|
| Unauthenticated user calls a mutation | GraphQL authentication error |
| Invalid `post_id` or `comment_id` | Validation error (`exists` rule) |
| Empty `body` | Validation error (`required`) |
| User tries to edit/delete another user's comment/reply | `"You can only update/delete your own comments/replies."` |
| Comment/reply not found | `findOrFail` 404 → GraphQL error |

## Example Variables

Create comment:
```json
{ "post_id": "1", "body": "This is a great post!" }
```

Create reply:
```json
{ "comment_id": "1", "body": "I agree completely!" }
```

Update comment:
```json
{ "id": "1", "body": "Updated comment text." }
```

Delete comment:
```json
{ "id": "1" }
```
