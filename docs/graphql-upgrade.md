# GraphQL upgrade guide

This guide covers the GraphQL changes of the 1.x series that prepare 2.0: what is new, what is
deprecated and what to use instead, and what 2.0 will remove. Nothing is removed or retyped in 1.x.
One behaviour change is deliberate and listed under "Behaviour changes".

Deprecated fields carry `@deprecated(reason: ...)` in the schema, so introspection
(`includeDeprecated: true`) and tooling such as GraphiQL show them. Magento's schema reader only
applies `@deprecated` to object type fields. Arguments and input fields cannot carry it, so their
deprecation is written into the `description` (it starts with "Deprecated: use ...").

## What is new

### uid

`BlogPost`, `BlogCategory`, `BlogTag` and `BlogAuthor` have `uid: ID!`. It is the core encoding
(base64 of the id), the same value core returns as `entity_uid` from `route`. The detail queries
accept it: `blogPost(uid:)`, `blogCategory(uid:)`, `blogTag(uid:)`, `blogAuthor(uid:)`. Send exactly
one of `id`, `uid` and `url_key` (`slug` for authors). A malformed or unknown uid behaves like an id
that does not exist.

### Filter keys

The filter inputs take unsuffixed keys: `author`, `category`, `tag`, `store` on posts, `store` on
categories and tags, `parent` on categories. They are the same filters as the old `*_id` keys. Sending
both spellings of one filter is an error. Filtering by something the caller may not read matches
nothing, exactly like an id that does not exist.

### posts on categories, tags and authors

`BlogCategory.posts`, `BlogTag.posts` and `BlogAuthor.posts` take `filter`, `sort`, `pageSize` and
`currentPage` and return `BlogPosts`. The parent constraint cannot be widened by `filter`. The page
sizes of all nested `posts` fields in one request are added up, and the request is rejected above
1000 posts.

### Routable types

The four types implement core's `RoutableInterface` (`relative_url`, `redirect_code`, `type`), so
core's `route(url:)` query returns them. Core already deprecates `urlResolver` in favour of `route`.

### pageSize cap

The list queries (`blogPosts`, `blogCategories`, `blogTags`, `blogAuthors`) and the nested `posts`
fields clamp `pageSize` to 100. `pageSize` or `currentPage` below 1 is an error.

### BlogCategory.parent

`BlogCategory.parent: BlogCategory` is the parent category. It is null for a top-level category and
when the caller may not read the parent.

## What is deprecated

| Deprecated | Use instead |
| --- | --- |
| `id` on `BlogPost`, `BlogCategory`, `BlogTag`, `BlogAuthor` | `uid` |
| `BlogPost.author_id` | `BlogPost.author { uid }` |
| `BlogPost.category_ids` | `BlogPost.categories { uid }` |
| `BlogPost.tag_ids` | `BlogPost.tags { uid }` |
| `BlogCategory.parent_id` | `BlogCategory.parent { uid }` |
| Filter key `author_id` (posts) | `author` |
| Filter key `category_id` (posts) | `category` |
| Filter key `tag_id` (posts) | `tag` |
| Filter key `store_id` (posts, categories, tags) | `store` |
| Filter key `parent_id` (categories) | `parent` |
| `id` argument of `blogPost`, `blogCategory`, `blogTag`, `blogAuthor` (documented "Prefer uid") | `uid` |
| `urlResolver` (core deprecation) | `route` |

Not deprecated: `store_ids` on all types. Store views are not blog entities and core exposes them
too, the `store` filter and the admin workflows still need them, and ids of store views cannot point
at a hidden blog entity. The `id` filter key and `id` sort key stay as they are, since there is no uid
filter yet. The create and update mutation inputs (`author_id`, `category_ids`, `tag_ids`, `parent_id`,
`store_ids`) and the `id` arguments of the update and delete mutations are unchanged, there is no uid
input yet.

## Behaviour changes

The deprecated raw id fields no longer name entities the caller may not read. This is deliberate: an
id of a draft-only or inactive category in `category_ids` told a visitor that a hidden entity exists.

For a visitor (no token, a customer token, or an admin token without the entity's ACL resource), and
for everyone while the blog is disabled for the store view:

* `BlogPost.category_ids` and `tag_ids` list only categories and tags that are active and visible on the current store view.
* `BlogPost.author_id` is null when the author is inactive.
* `BlogCategory.parent_id` is null when the parent is inactive or not visible on the current store view. A top-level category keeps its stored value (0 or null).
* Ids of entities that were deleted are left out or null as well, so a hidden entity cannot be told from a missing one.

Admin tokens with the entity's ACL resource (`MageOS_Blog::category`, `::tag`, `::author`) still get
every id. The nested fields (`categories`, `tags`, `author`, `parent`) always followed the same rule.

Client impact: code that read `category_ids` to find categories a post is assigned to gets fewer ids
for visitors, the same ones `categories` returns. Code that used `category_ids` as the source of truth
for admin screens should use an admin token.

## Before and after

Reading a post with its relations.

```graphql
# Before
{
  blogPost(id: 5) {
    id
    author_id
    category_ids
    tag_ids
  }
}
```

```graphql
# After
{
  blogPost(uid: "NQ==") {
    uid
    author { uid name }
    categories { uid title }
    tags { uid title }
  }
}
```

Filtering.

```graphql
# Before
{ blogPosts(filter: { category_id: { eq: "3" }, store_id: { eq: "1" } }) { items { title } } }
```

```graphql
# After
{ blogPosts(filter: { category: { eq: "3" }, store: { eq: "1" } }) { items { title } } }
```

Category tree and posts in one query.

```graphql
# Before: two requests, parent_id then a second query for the parent
{ blogCategory(id: 7) { title parent_id } }
```

```graphql
# After
{
  blogCategory(uid: "Nw==") {
    title
    parent { uid title }
    posts(pageSize: 5) { items { title } total_count }
  }
}
```

Routing.

```graphql
# Before
{ urlResolver(url: "blog/my-post") { type id relative_url } }
```

```graphql
# After
{
  route(url: "blog/my-post") {
    type
    redirect_code
    ... on BlogPost { uid title }
  }
}
```

## What changes in 2.0

Removals proposed for 2.0. Each is listed as deprecated above, nothing else is removed.

* `id` on `BlogPost`, `BlogCategory`, `BlogTag`, `BlogAuthor`.
* `BlogPost.author_id`, `category_ids`, `tag_ids` and `BlogCategory.parent_id`.
* Filter keys `author_id`, `category_id`, `tag_id`, `store_id`, `parent_id`.
* The `id` argument of the four detail queries (`uid`, `url_key` and `slug` remain).

Open for 2.0, not decided: uid arguments on the update and delete mutations, a uid filter next to
the `id` filter key, and whether `store_ids` stays on the output types.
