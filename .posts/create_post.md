# Create Post — Usage Guide (with Images)

## Overview

The `createPost` mutation now accepts an optional `images` list. Each image entry contains a `url` (publicly accessible URL or data URL) and an optional `alt` text for accessibility.

## Schema (GraphQL)

```graphql
input PostImageInput {
    url: String!
    alt: String
}

type PostImage {
    id: ID!
    url: String!
    alt: String
    created_at: DateTime!
}

type Mutation {
    createPost(
        title: String!
        body: String!
        published: Boolean
        user_id: ID!
        images: [PostImageInput!]
    ): Post @field(resolver: "App\\GraphQL\\Mutations\\CreatePost")
}
```

## Apollo Client Mutation (Expo React Native)

```typescript
import { gql, useMutation } from '@apollo/client';

export const CREATE_POST_WITH_IMAGES = gql`
  mutation CreatePost(
    $title: String!,
    $body: String!,
    $published: Boolean,
    $user_id: ID!,
    $images: [PostImageInput!]
  ) {
    createPost(
      title: $title
      body: $body
      published: $published
      user_id: $user_id
      images: $images
    ) {
      id
      title
      body
      published
      user { id name }
      images {
        id
        url
        alt
        created_at
      }
      created_at
    }
  }
`;
```

## React Native Component Example

```typescript
import { useMutation } from '@apollo/client';
import { Button, TextInput, View, Text, ActivityIndicator, Alert } from 'react-native';
import { CREATE_POST_WITH_IMAGES } from '../graphql/postOperations';
import { useAuth } from '../context/AuthContext';

interface ImageInput {
  url: string;
  alt?: string;
}

export function CreatePostScreen() {
  const { token, user } = useAuth();
  const [title, setTitle] = useState('');
  const [body, setBody] = useState('');
  const [images, setImages] = useState<ImageInput[]>([]);
  const [loading, setLoading] = useState(false);

  const [createPost] = useMutation(CREATE_POST_WITH_IMAGES, {
    context: { headers: { Authorization: `Bearer ${token}` } },
  });

  const handleAddImage = (url: string, alt?: string) => {
    setImages([...images, { url, alt }]);
  };

  const handleRemoveImage = (index: number) => {
    setImages(images.filter((_, i) => i !== index));
  };

  const handleSubmit = async () => {
    if (!title || !body || !user) return;

    setLoading(true);
    try {
      const result = await createPost({
        variables: {
          title,
          body,
          published: true,
          user_id: user.id,
          images, // [{ url: 'https://...', alt: 'description' }]
        },
      });

      Alert.alert('Success', 'Post created with images!');
      // Navigate to feed or post detail
    } catch (error) {
      Alert.alert('Error', (error as Error).message);
    } finally {
      setLoading(false);
    }
  };

  if (loading) return <ActivityIndicator size="large" />;

  return (
    <View>
      <TextInput placeholder="Title" value={title} onChangeText={setTitle} />
      <TextInput placeholder="Body" value={body} onChangeText={setBody} multiline />
      
      <Text>Images ({images.length}):</Text>
      {images.map((img, i) => (
        <View key={i}>
          <Text>{img.url}</Text>
          <Text>{img.alt || 'No alt text'}</Text>
          <Button title="Remove" onPress={() => handleRemoveImage(i)} />
        </View>
      ))}
      
      <Button title="Add Image URL" onPress={() => {
        // Prompt user for image URL
      }} />
      
      <Button title="Create Post" onPress={handleSubmit} disabled={loading} />
    </View>
  );
}
```

## Response Shape

```json
{
  "data": {
    "createPost": {
      "id": "1",
      "title": "My Post",
      "body": "Hello world",
      "published": true,
      "user": { "id": "1", "name": "Alice" },
      "images": [
        { "id": "1", "url": "https://example.com/img1.jpg", "alt": "Sunset", "created_at": "2026-10-23 08:34:00" },
        { "id": "2", "url": "https://example.com/img2.jpg", "alt": "Mountains", "created_at": "2026-10-23 08:34:00" }
      ],
      "created_at": "2026-10-23 08:34:00"
    }
  }
}
```

## Image URL Sources

Images must already be publicly accessible. Options:

1. **Upload to S3/R2/Cloudinary first** → pass resulting URL in `images[].url`
2. **Base64 data URLs** → pass `data:image/jpeg;base64,/9j/4AAQSkZ...` as `images[].url`
3. **Local Expo Asset** → use `Asset.fromModule(require('./image.jpg')).uri` after downloading to cache

## Example Variables

```json
{
  "title": "My Vacation",
  "body": "Had a great time at the beach!",
  "published": true,
  "user_id": "1",
  "images": [
    { "url": "https://cdn.example.com/vacation1.jpg", "alt": "Beach sunset" },
    { "url": "https://cdn.example.com/vacation2.jpg", "alt": "Ocean waves" }
  ]
}
```

## Error Handling

- Missing `title` or `body` → validation error
- Invalid `user_id` → `exists:users,id` validation error
- Invalid image URL format → stored as-is (no remote validation performed)
