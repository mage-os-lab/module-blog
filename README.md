# MageOS Blog

A blog module for Mage-OS and Magento 2. Posts, categories, tags, authors, scheduled publishing, RSS, sitemap, 6 storefront widgets, SEO (meta tags, Open Graph, Twitter Cards, JSON-LD), and a full GraphQL API. Works with Luma and Hyvä themes.

## Install

```bash
composer require mageos/module-blog
bin/magento module:enable MageOS_Blog
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:clean
```

For Hyvä storefronts, also install the companion template package:

```bash
composer require mageos/module-blog-hyva
```

## Enable

The module ships disabled. Turn it on under **Stores → Configuration → MageOS → Blog → General → Enabled** (or set `mageos_blog/general/enabled = 1`).

## Key admin settings

All settings live under **Stores → Configuration → MageOS → Blog**.

| Path | Purpose |
| --- | --- |
| `mageos_blog/general/enabled` | Master kill switch. Router and cron both short-circuit when off. |
| `mageos_blog/post/posts_per_page` | Listing pagination size. |
| `mageos_blog/post/default_robots` | Fallback `meta robots` value for posts that don't override it. |
| `mageos_blog/seo/og_default_type` | Default `og:type`. Usually `article`. |
| `mageos_blog/seo/json_ld_enabled` | Emit `Article` JSON-LD on post detail pages. |
| `mageos_blog/seo/twitter_site` | Twitter handle used for `twitter:site`. |
| `mageos_blog/sidebar/*` | Per-widget toggles for search, recent posts, category list, tag cloud, archive. |
| `mageos_blog/sitemap/{post,category,tag}/*` | Per-entity sitemap enable + `changefreq` + `priority`. |
| `mageos_blog/rss/enabled`, `mageos_blog/rss/limit` | RSS feed at `/blog/rss`. |

## Compatibility

| | PHP 8.2 | PHP 8.3 |
| --- | --- | --- |
| Magento 2.4.6 | yes | yes |
| Magento 2.4.7 | yes | yes |
| Hyvä 1.3+ | yes | yes |
| Luma | yes | yes |

Requires `magento/module-url-rewrite-graph-ql` for the GraphQL URL resolver integration.

## Storefront URLs

By default:

- Post: `/blog/{url-key}`
- Category: `/blog/category/{url-key}`
- Tag: `/blog/tag/{url-key}`
- Author: `/blog/author/{slug}`
- RSS: `/blog/rss`
- Search: `/blog/search?q=...`

URL shape is driven by `mageos_blog/permalink/*` config. URL rewrites populate on save via the repository plugins in `Plugin/Repository/`.

## GraphQL

Queries: `blogPost`, `blogPosts`, `blogCategory`, `blogCategories`, `blogTag`, `blogTags`, `blogAuthor`, `blogAuthors`. Each list query accepts `filter`, `sort`, `pageSize`, `currentPage` and returns `items` + `page_info` + `total_count`.

Mutations: `createBlogPost`, `updateBlogPost`, `deleteBlogPost` (and the equivalent for category / tag / author). Every mutation requires an admin token and passes through `Magento\Framework\AuthorizationInterface` against the entity's ACL resource (`MageOS_Blog::post`, `MageOS_Blog::category`, `MageOS_Blog::tag`, `MageOS_Blog::author`).

`urlResolver(url: "/blog/my-post")` returns `{ type: BLOG_POST, id, relative_url }`. Supported types: `BLOG_POST`, `BLOG_CATEGORY`, `BLOG_TAG`, `BLOG_AUTHOR`.

Full schema: `etc/schema.graphqls`.

## Upgrade notes

v1.0.0 is a greenfield rewrite. There is no migration path from any v0.x fork, including the original Magefan-derived codebase that lived under this package name before the rewrite. To move off a v0 install, export content from the old admin, fresh-install v1, and re-import via the admin or GraphQL.

## Development

```bash
composer install
vendor/bin/phpunit --testsuite unit
vendor/bin/phpstan analyse --memory-limit=1G
vendor/bin/phpcs --standard=phpcs.xml.dist
vendor/bin/php-cs-fixer fix --dry-run --diff
vendor/bin/infection --min-msi=75 --threads=4
```

Integration tests live under `Test/Integration/` and run in CI against a live Magento install via `graycoreio/github-actions-magento2`.

## Contributing

Issues and PRs welcome at https://github.com/mage-os/module-blog. Please follow [Conventional Commits](https://www.conventionalcommits.org/), include tests for new behavior, and keep PRs small and reviewable.

## License

OSL-3.0. See `LICENSE`.

## Attribution

Design inspired by [Magefan Blog](https://magefan.com/magento2-blog-extension) (OSL-3.0). v1 is an independent implementation with no shared code.
