# Changelog

All notable changes to this project are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.3.1](https://github.com/mage-os-lab/module-blog/compare/v1.3.0...v1.3.1) (2026-08-28)


### Miscellaneous Chores

* **actions:** Update ci to use the latest versions ([28fe7f2](https://github.com/mage-os-lab/module-blog/commit/28fe7f26fd4d1458ef31bd679dceec7d2b798026))
* **ci:** Update Check Extension CI to the test versions ([04179ec](https://github.com/mage-os-lab/module-blog/commit/04179ec87cb6dd7adc26e3d1aed6907539511fec))

## [1.3.0](https://github.com/mage-os-lab/module-blog/compare/v1.2.0...v1.3.0) (2026-08-25)


### Features

* **blog:** resolve published posts linked to a product ([b04aa93](https://github.com/mage-os-lab/module-blog/commit/b04aa93a11dfa3dd8236194d629b7aac9348fff5))
* **config:** add related-posts config group, disabled by default ([1689f50](https://github.com/mage-os-lab/module-blog/commit/1689f504cf2d648387dee169df8acf7c8dbc8647)), closes [#20](https://github.com/mage-os-lab/module-blog/issues/20)
* **viewmodel:** add PDP related-posts view model ([3396caf](https://github.com/mage-os-lab/module-blog/commit/3396cafe9e9b1d1f4e75f819e8d1e248f7c129c5)), closes [#20](https://github.com/mage-os-lab/module-blog/issues/20)


### Bug Fixes

* **blog:** filter related posts by published status and store scope ([87d8c64](https://github.com/mage-os-lab/module-blog/commit/87d8c64352caca3abf1b7a0033acea546d2424fb))
* **cache:** declare cache identities on storefront blog blocks ([e8b7264](https://github.com/mage-os-lab/module-blog/commit/e8b726473cef199521bd8f7cd9d3c8d552f45168)), closes [#20](https://github.com/mage-os-lab/module-blog/issues/20)
* **pdp:** replace related-posts placeholder with working block ([5b823c5](https://github.com/mage-os-lab/module-blog/commit/5b823c59873195162186e4d04bfa21eaafb470ec)), closes [#20](https://github.com/mage-os-lab/module-blog/issues/20)
* **styling:** move away from css layout declarations to the luma styles generation ([92af6f3](https://github.com/mage-os-lab/module-blog/commit/92af6f34cfeb2922eebf15990ce25692dabeaba9))

## [Unreleased]

### Fixed

- **Product pages no longer show a developer placeholder** ([#20](https://github.com/mage-os-lab/module-blog/issues/20)). 1.1.0 injected an unimplemented "Related Posts" block into every product detail page, rendering the text *"Related posts for products — scoped ViewModel lands in v1.1."* to customers, with no way to switch it off short of a theme layout override. The block is now a working feature and is **disabled by default**.
- **Related posts no longer leak drafts or other stores' content.** `Model\RelatedPostsProvider\ManualRelationLoader` selected on the pivot alone, with no status or store-scope filter, so unpublished drafts and posts assigned to a different store view could appear in the related-posts section of any post detail page. `AlgorithmicLoader` filtered status but had the same store-scope gap. Both now apply the predicates the storefront listings already used.
- **Blog pages are now invalidated in the full-page cache.** No block in the module implemented `IdentityInterface`, so blog pages carried no blog cache tags and editing a post left them stale. This also meant `Cron\PublishScheduledPosts` could not do the one thing it exists for — re-saving a post to invalidate FPC invalidates nothing when nothing is tagged. Ten storefront blocks and content widgets now declare identities.
- **22 storefront and admin strings were untranslatable**, missing from `i18n/en_US.csv`. Pagination labels, "Related posts", the reading time, the search heading and all four empty-state messages were among them, so a translated store still showed English on those pages. Admin gained `All Store Views`, `Parent Category`, `Related Products` and the image field help texts.

### Added

- **Related posts on product detail pages.** Posts linked to a product through the post form's Related Products field are shown in a product information tab, store-scoped and published-only. Controlled by `mageos_blog/related_posts/*` — `enabled` (**default `0`**), `limit` (default 3) and `title`. The section renders nothing at all when the module is off, the feature is off, or a product has no linked posts, so no empty tab appears.
- Screenshots of every storefront page, desktop and mobile, in [`docs/storefront.md`](docs/storefront.md).

### Changed

- `view/frontend/web/css/blog.css` split: design tokens, base typography and the post-card component moved to `blog-cards.css`, so product pages can load the card styles without the blog page layout. `blog.css` now requires `blog-cards.css` to be loaded first; `blog_default.xml` does so.
- `i18n/en_US.csv` is sorted deterministically, so regenerating it produces a readable diff. Nothing was removed, including 14 strings left over from earlier versions that no longer appear in the code.
- Removed five retired placeholder strings from `i18n/en_US.csv`, including the one shown by the #20 block.
- README now points at the Hyvä companion package instead of promising it for a future release. It also explains what happens on a Hyvä theme without it: the pages render, but unstyled.
- Improved the look and feel of the blog on Luma so it fits the theme's own design. Blog text was rendering much smaller than the rest of the store, and in a different font.

### Removed

- Unused Hyvä template-swap code — `Api/HyvaThemeDetectionInterface`, `Model\HyvaThemeDetection` and the `TemplateEngine\Php` plugin. It looked for templates in a `hyva/` directory that has never existed in any release, so it never did anything. Hyvä support now comes from `mage-os/module-blog-hyva`.

No public `Api/` signature changed, apart from `HyvaThemeDetectionInterface` above, which had no callers.

## [1.2.0](https://github.com/mage-os-lab/module-blog/compare/v1.1.0...v1.2.0) (2026-08-12)


### Features

* add url rewrite regenerate command ([7a2be85](https://github.com/mage-os-lab/module-blog/commit/7a2be85b76ec3f42aaa25b1a6afea94c244dee93))


### Bug Fixes

* Exception on NULL URL Key Value ([3db2f76](https://github.com/mage-os-lab/module-blog/commit/3db2f76d483a2d2c4408f68c9d60c27c5ab5f556))
* resolve url_key instead of nulling it on save ([9850042](https://github.com/mage-os-lab/module-blog/commit/9850042e1d0510fe2427dda175a94746cad4975f))
* TypeError on post, category, tag, author save with empty URL key ([3db2f76](https://github.com/mage-os-lab/module-blog/commit/3db2f76d483a2d2c4408f68c9d60c27c5ab5f556))
* write url rewrites for posts, categories and tags ([4e144bd](https://github.com/mage-os-lab/module-blog/commit/4e144bdc9658c40593688288b99962a23dc93ddf))

## [1.1.0] - 2026-06-25

Admin usability and storefront-design release. No schema or API breaking changes; safe upgrade from 1.0.0.

### Added

- **Admin "no raw IDs" pickers.** The post form now uses real pickers instead of hand-typed entity IDs: author, category, tag, related-post, and related-product. The category form gains a hierarchical parent-category picker. Backed by new option sources under `Ui/Component/Form/{Authors,Categories,Tags,ParentCategory,RelatedPosts,RelatedProducts}/Options.php`. Related-post and related-product assignments now actually persist on save (`Model\Post\Link\RelatedPostLinkManager`, `RelatedProductLinkManager`, `Model\Post\PostsByAssignmentProvider`).
- **Human-readable admin listings.** Linked author-name column on the post grid and a parent-category column on the category grid (`Ui/Component/Listing/Column/{AuthorName,ParentCategoryName}.php`).
- **Storefront redesign.** New `view/frontend/web/css/blog.css` with a typography scale, layout primitives, and card components. Shared `post/card.phtml` (replacing `post/item.phtml`), hero-on-top post cards with pagination, a redesigned post-detail page (hero, tag chips, related posts), and context headers on category / tag / author / search pages.
- **API surface.** Additional data fields and interface docblocks on `Api\Data\{Author,Category,Post,Tag}Interface` (cleans up `setup:di:compile`).
- **Project docs & CI.** `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`, `SECURITY.md`, GitHub issue/PR templates, and a GitHub Actions CI workflow.

### Changed

- **Naming.** Module name references aligned to the `mage-os` naming convention (PR #12).
- **Layout.** Blog storefront switched to a 1-column layout; Luma sidebar bleed removed. Body / heading / post-content type scale bumped; `.mageos-blog-container` left-aligned.
- **Compatibility.** Widened the `magento/framework` constraint to support Magento 2.4.6-p15.

### Fixed

- Double `<h1>` on category / tag / author / search pages (removed redundant `page.main.title`).
- Author avatar upload (dropped an invalid nested `xsi:type="string"` param).
- Admin form rendering and category / tag / author storefront post listings; flattened per-entity DataProvider row shapes and stringified IDs for ui-select.
- `setStringify`/escaping fixes and numerous PHPCS warnings cleared; integration and unit test suites updated for 2.4.6-p15, 2.4.7-p10, 2.4.8-p5 & 2.4.9 & Equivilent MageOS versions.

### Deferred

The following items were listed under "Deferred to v1.1" in the 1.0.0 release but are not included in 1.1.0. They remain planned for a future release:

- Comments (native, moderation, spam, email).
- Content importers from Mageplaza, Magefan, Aheadworks, Mirasvit, WordPress (planned as a separate `mage-os/module-blog-migration` package).
- OpenSearch / `Magento_Search` indexer + mview + `etc/search_request.xml`.
- PageBuilder content editing.
- Hyvä-native `.phtml` set (detection plugin is in place; the companion `mage-os/module-blog-hyva` package remains pending — see README).
- Configurable URL prefix, custom per-page CSS, preview-token model, Commerce-only AdminGWS plugin, MFTF tests, gravatar autofetch, per-post multi-language content variants.
- Infection MSI ≥ 75% / Covered MSI ≥ 80%.

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
- **Quality gates.** PHPStan level 8, PHPCS (Magento2 ruleset), PHP-CS-Fixer, PHPUnit unit suite (57 tests), Infection mutation testing (baselines: MSI 54%, Covered MSI 70%).

### Notes on design

Design inspired by [Magefan Blog](https://magefan.com/magento2-blog-extension) (OSL-3.0). v1 is an independent implementation with no shared code.

### Deferred to v1.1

- Comments (native, moderation, spam, email).
- Content importers from Mageplaza, Magefan, Aheadworks, Mirasvit, WordPress. Will ship as a separate `mageos/module-blog-migration` package.
- OpenSearch / `Magento_Search` indexer + mview + `etc/search_request.xml`.
- PageBuilder content editing.
- Hyvä-native `.phtml` set (detection plugin is in place; the companion package is empty until v1.1).
- Configurable URL prefix, custom per-page CSS, preview-token model, Commerce-only AdminGWS plugin, MFTF tests, gravatar autofetch, per-post multi-language content variants.
- Infection MSI ≥ 75% / Covered MSI ≥ 80%. v1.0 ships at 54% / 70% respectively; raising the floor depends on expanding unit-test coverage into `ViewModel/Post/Detail` (JSON-LD shape edge cases) and `Model/HyvaThemeDetection` (theme-chain walking).

[1.1.0]: https://github.com/mage-os/module-blog/releases/tag/v1.1.0
[1.0.0]: https://github.com/mage-os/module-blog/releases/tag/v1.0.0
