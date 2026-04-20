# Changelog

All notable changes to this project are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-04-20

First release of the greenfield rewrite. No migration path from pre-v1 forks.

### Added

- **Content model.** Four entities (`blog_post`, `blog_category`, `blog_tag`, `blog_author`) with DB-level foreign keys and cascading deletes. Store-view scoping via pivot tables. Fulltext indexes on post and category content for search.
- **Admin UI.** UI-component grids and forms for posts, categories, tags, authors. Image upload (featured image, OG image, gallery) via a single `MageOS\Blog\ImageUpload` virtualType. Related-posts and related-products pickers on the post form.
- **Storefront.** Custom router with URL-rewrite integration. Post detail, category detail, tag detail, author detail, blog index, search, pagination. Luma template set under `view/frontend/templates/`; Hyvä template set ships in the companion package `mageos/module-blog-hyva`.
- **Scheduled publishing.** `Cron\PublishScheduledPosts` runs every minute, finds posts whose `publish_date` just passed, and re-saves them to invalidate FPC. Gated by `mageos_blog/general/enabled`.
- **SEO.** `meta title`, `meta description`, `meta keywords`, `meta robots`, canonical link, Open Graph, Twitter Cards, and `Article` JSON-LD on post detail pages. Configurable defaults under `mageos_blog/seo/*`.
- **RSS.** `/blog/rss` emits RSS 2.0 XML. Limit configurable via `mageos_blog/rss/limit`.
- **Sitemap.** Three `ItemProvider`s (post, category, tag) wired into `Magento\Sitemap\Model\ItemProvider\Composite`. Per-entity enable / frequency / priority under `mageos_blog/sitemap/*`.
- **Search.** DB-fulltext-backed search against the `MAGEOS_BLOG_POST_FULLTEXT` index. Controller at `/blog/search?q=...`. OpenSearch / Magento_Search integration deferred to v1.1.
- **Widgets.** 6 storefront widgets: recent posts, featured post, post list, post link, category link, tag link. Admin chooser blocks for picking a post / category / tag from grids.
- **GraphQL.** Queries (`blogPost`, `blogPosts`, `blogCategory`, `blogCategories`, `blogTag`, `blogTags`, `blogAuthor`, `blogAuthors`). Mutations (`create` / `update` / `delete` for all four entities). URL resolver integration: `urlResolver(url:"/blog/my-post")` returns `type: BLOG_POST`. Mutations require admin token plus ACL.
- **Hyvä support.** A `Plugin\Magento\Framework\View\TemplateEngine\Php` plugin injects a `HyvaThemeDetection` helper into every `.phtml` scope. `Plugin\Magento\Framework\View\Element\TemplateRewrite` remaps `MageOS_Blog::X` paths to `MageOS_Blog::hyva/X` on Hyvä themes.
- **i18n.** Seed `i18n/en_US.csv` with 236 phrases.

### Notes on design

Design inspired by [Magefan Blog](https://magefan.com/magento2-blog-extension) (OSL-3.0). v1 is an independent implementation with no shared code.

### Deferred to v1.1

- Comments (native, moderation, spam, email).
- Content importers from Mageplaza, Magefan, Aheadworks, Mirasvit, WordPress. Will ship as a separate `mageos/module-blog-migration` package.
- OpenSearch / `Magento_Search` indexer + mview + `etc/search_request.xml`.
- PageBuilder content editing.
- Hyvä-native `.phtml` set (detection plugin is in place; the companion package is empty until v1.1).
- Configurable URL prefix, custom per-page CSS, preview-token model, Commerce-only AdminGWS plugin, MFTF tests, gravatar autofetch, per-post multi-language content variants.

[1.0.0]: https://github.com/mage-os/module-blog/releases/tag/v1.0.0
