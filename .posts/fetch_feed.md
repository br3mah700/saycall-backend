# Fetch Feed — Usage Guide (with Images)

## Overview

The `feed` query returns cursor-paginated posts including their attached images. Each post node now includes an `images` list with `url` and `alt` fields.

## Schema (GraphQL)

```graphql
type Query {
    feed(
        first: Int = 10
        after: String
    ): PostConnection! @field(resolver: "App\\GraphQL\\Queries\\Feed")
}

type PostConnection {
    edges: [PostEdge!]!
    pageInfo: PageInfo!
}

type PostEdge {
    cursor: String!
    node: Post!
}

type Post {
    id: ID!
    title: String!
    body: String!
    published: Boolean!
    user: User!
    images: [PostImage!]! @hasMany
    created_at: DateTime!
    updated_at: DateTime!
}

type PostImage {
    id: ID!
    url: String!
    alt: String
    created_at: DateTime!
}

type PageInfo {
    hasNextPage: Boolean!
    endCursor: String
}
```

## Apollo Client Query (Expo React Native)

```typescript
import { gql, useQuery } from '@apollo/client';

export const FEED_QUERY = gql`
  query Feed($first: Int, $after: String) {
    feed(first: $first, after: $after) {
      edges {
        cursor
        node {
          id
          title
          body
          published
          user {
            id
            name
          }
          images {
            id
            url
            alt
            created_at
          }
          created_at
        }
      }
      pageInfo {
        hasNextPage
        endCursor
      }
    }
  }
`;
```

## React Native Hook with Cursor Pagination

```typescript
import { useQuery } from '@apollo/client';
import { FEED_QUERY } from '../graphql/postOperations';

interface FeedEdge {
  cursor: string;
  node: {
    id: string;
    title: string;
    body: string;
    published: boolean;
    user: { id: string; name: string };
    images: Array<{ id: string; url: string; alt?: string; created_at: string }>;
    created_at: string;
  };
}

interface FeedPageInfo {
  hasNextPage: boolean;
  endCursor: string | null;
}

export function useFeed(first = 10) {
  const { data, loading, error, fetchMore, refetch } = useQuery(FEED_QUERY, {
    variables: { first, after: null },
    fetchPolicy: 'cache-and-network',
    notifyOnNetworkStatusChange: true,
  });

  const feed = data?.feed;
  const edges: FeedEdge[] = feed?.edges ?? [];
  const pageInfo: FeedPageInfo = feed?.pageInfo ?? { hasNextPage: false, endCursor: null };

  const loadMore = () => {
    if (pageInfo.hasNextPage && !loading) {
      fetchMore({
        variables: { after: pageInfo.endCursor },
        updateQuery: (prev, { fetchMoreResult }) => {
          if (!fetchMoreResult) return prev;

          const prevEdges = prev.feed?.edges ?? [];
          const newEdges = fetchMoreResult.feed?.edges ?? [];
          const newPageInfo = fetchMoreResult.feed?.pageInfo ?? prev.feed?.pageInfo;

          return {
            feed: {
              ...prev.feed,
              edges: [...prevEdges, ...newEdges],
              pageInfo: newPageInfo,
            },
          };
        },
      });
    }
  };

  return {
    edges,
    pageInfo,
    loading,
    error,
    loadMore,
    refetch,
  };
}
```

## React Native Component Example

```typescript
import { FlatList, View, Text, ActivityIndicator, StyleSheet, Image } from 'react-native';
import { useFeed } from '../hooks/useFeed';

interface PostCardProps {
  item: {
    cursor: string;
    node: {
      id: string;
      title: string;
      body: string;
      images: Array<{ url: string; alt?: string }>;
      user: { name: string };
      created_at: string;
    };
  };
}

function PostCard({ item }: PostCardProps) {
  const { node } = item;
  const primaryImage = node.images[0];

  return (
    <View style={styles.card}>
      {primaryImage && (
        <Image
          source={{ uri: primaryImage.url }}
          style={styles.thumbnail}
          resizeMode="cover"
        />
      )}
      <Text style={styles.title}>{node.title}</Text>
      <Text style={styles.body} numberOfLines={3}>{node.body}</Text>
      <View style={styles.meta}>
        <Text style={styles.author}>{node.user.name}</Text>
        <Text style={styles.imagesCount}>
          📷 {node.images.length}
        </Text>
      </View>
    </View>
  );
}

export function FeedScreen() {
  const { edges, pageInfo, loading, error, loadMore } = useFeed(10);

  if (loading && edges.length === 0) {
    return <ActivityIndicator size="large" style={styles.center} />;
  }

  if (error) {
    return <Text style={styles.error}>Error: {error.message}</Text>;
  }

  return (
    <FlatList
      data={edges}
      keyExtractor={(item) => item.cursor}
      renderItem={({ item }) => <PostCard item={item} />}
      onEndReached={() => {
        if (pageInfo.hasNextPage) {
          loadMore();
        }
      }}
      onEndReachedThreshold={0.5}
      ListEmptyComponent={
        <View style={styles.center}>
          <Text>No posts yet.</Text>
        </View>
      }
    />
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  card: {
    padding: 12,
    margin: 12,
    backgroundColor: '#fff',
    borderRadius: 8,
    elevation: 2,
  },
  thumbnail: { width: '100%', height: 200, borderRadius: 8, marginBottom: 8 },
  title: { fontSize: 18, fontWeight: '600', marginBottom: 4 },
  body: { fontSize: 14, color: '#333', marginBottom: 8 },
  meta: { flexDirection: 'row', justifyContent: 'space-between' },
  author: { fontSize: 12, color: '#666' },
  imagesCount: { fontSize: 12, color: '#999' },
  error: { color: 'red', padding: 16 },
});
```

## Response Shape

```json
{
  "data": {
    "feed": {
      "edges": [
        {
          "cursor": "eyJpZCI6MSwiY3JlYXRlZF9hdCI6IjIwMjYtMTAtMjMgMDg6MzQ6MDAifQ==",
          "node": {
            "id": "1",
            "title": "My Vacation",
            "body": "Had a great time!",
            "published": true,
            "user": { "id": "1", "name": "Alice" },
            "images": [
              { "id": "1", "url": "https://cdn.example.com/vacation1.jpg", "alt": "Beach sunset", "created_at": "2026-10-23 08:34:00" },
              { "id": "2", "url": "https://cdn.example.com/vacation2.jpg", "alt": "Ocean waves", "created_at": "2026-10-23 08:34:00" }
            ],
            "created_at": "2026-10-23 08:34:00"
          }
        }
      ],
      "pageInfo": {
        "hasNextPage": true,
        "endCursor": "eyJpZCI6MTAiLCJjcmVhdGVkX2F0IjoiMjAyNi0xMC0yMyAwODozMzo0MCJ9"
      }
    }
  }
}
```

## Cursor Pagination Flow

```
1. Initial:  feed(first: 10, after: null)
   → Returns edges[0..9] + pageInfo.endCursor="abc123"

2. Scroll:   feed(first: 10, after: "abc123")
   → Returns edges[10..19] + new endCursor
   → Appended via updateQuery, scroll position preserved

3. End:      hasNextPage: false → stop fetching
```

## Image Rendering Tips for Expo

- Use `expo-image` for optimized image loading with caching
- Set `resizeMode="cover"` for feed thumbnails
- Add `placeholder`/`blurhash` for perceived performance
- Handle failed image loads with `onError` fallback

```tsx
import { Image } from 'expo-image';

<Image
  source={{ uri: image.url }}
  style={styles.thumbnail}
  placeholder={{ uri: 'https://placeholder.com/300x200' }}
  onError={() => setShowFallback(true)}
/>
```
