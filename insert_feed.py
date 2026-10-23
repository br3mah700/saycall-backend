with open(r'C:\xampp\htdocs\APPOLLO\graphql\schema.graphql', 'r', encoding='utf-8') as f:
    lines = f.readlines()

# Find the line with "Post Queries" comment
insert_idx = None
for i, line in enumerate(lines):
    if 'Post Queries' in line:
        insert_idx = i
        break

assert insert_idx is not None, 'Post Queries not found'

feed_lines = [
    '    # \u2500\u2500\u2500 Feed Queries \u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\n',
    '    "Fetch a cursor-paginated feed of published posts."\n',
    '    feed(\n',
    '        "Number of items to fetch (max 50)."\n',
    '        first: Int = 10\n',
    '\n',
    '        "Cursor to fetch items after."\n',
    '        after: String\n',
    '    ): PostConnection! @field(resolver: "App\\\\GraphQL\\\\Queries\\\\Feed")\n',
    '\n',
]

# Insert before the Post Queries line
lines[insert_idx:insert_idx] = feed_lines

# Now find Types marker
types_idx = None
for i, line in enumerate(lines):
    if 'Types' in line and '\u2500' in line:
        types_idx = i
        break

assert types_idx is not None, 'Types not found'

types_lines = [
    '\n',
    '"Relay-style edge containing a post node and its cursor."\n',
    'type PostEdge {\n',
    '    "Opaque cursor for this edge."\n',
    '    cursor: String!\n',
    '\n',
    '    "The post node."\n',
    '    node: Post!\n',
    '}\n',
    '\n',
    '"Relay-style connection for cursor-paginated posts."\n',
    'type PostConnection {\n',
    '    "List of post edges."\n',
    '    edges: [PostEdge!]!\n',
    '\n',
    '    "Pagination info."\n',
    '    pageInfo: PageInfo!\n',
    '}\n',
    '\n',
]

lines[types_idx:types_idx] = types_lines

with open(r'C:\xampp\htdocs\APPOLLO\graphql\schema.graphql', 'w', encoding='utf-8') as f:
    f.writelines(lines)

print('Done!')