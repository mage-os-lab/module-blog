# MageOS_Blog v1 — Clean Rewrite Design

| | |
|---|---|
| **Date** | 2026-04-19 |
| **Status** | Approved |
| **Approver** | David Lambauer |
| **Approach** | Option B — complete rewrite (no code reuse from current fork) |

---

## 1. Context & motivation

### 1.1 Why a rewrite

The repository's initial commit (2026, `alin.munteanu@roweb.com`) dropped ~20k lines of code in a single changeset with no authorship history. Investigation shows the code is a find-and-replace fork of **Magefan Blog** (not Mageplaza, as initially suspected):

- `Block/Post/View/Comments/MageOS.php` + nested `MageOS/Comment.php` is a verbatim shape of Magefan's `Comments/Magefan.php` + `Magefan/Comment.php` — the "vendor name as class name" pattern is Magefan's convention for the native comments implementation. Renaming `Magefan` → `MageOS` produces a class whose name makes no semantic sense (it's a vendor token, not a feature).
- `Model/Import/Magefun.php` queries `magefan_blog_category` tables despite its classname — the "Magefun" label is obfuscation; the SQL betrays the source.
- The importer set (`Aw`, `Mageplaza`, `Mirasvit`, `Wordpress`, `Magefun`), feature surface (Featured widget + grid chooser, AddThis, reading time, views count, `UpgradableEntitiesPool`, sidebar composition, `CustomCss`, `PreviewUrl`, AdminGws plugin, sitemap item providers), and general module shape match Magefan Blog — they do not match Mageplaza (no `Topic`, no `Author/AuthorRole`, no `mageplaza_blog_*` tables).
- The Mageplaza overlap the co-worker flagged is lineage — all OSL-3.0-era M2 blog modules share common patterns. The only Mageplaza artifacts in this repo are *import adapters* that read from Mageplaza installs (the opposite of copying).

### 1.2 Legal/licensing risk (the real problem)

- **No LICENSE file**, no copyright headers anywhere in PHP, no attribution in `composer.json`, no README or NOTICE.
- Magefan Blog is **OSL-3.0**. Derivatives must remain OSL-3.0, retain copyright notices, and credit upstream.
- The mechanical rename pattern (`Magefan` → `MageOS`, `Magefun` retained but rewired to query `magefan_*`) reads as deliberate obfuscation of source.
- Shipping this as `mageos/module-blog` would be a reputational and legal hazard for the Mage-OS project.

### 1.3 Decision

Throw out the forked code entirely. Rewrite in modern idioms with no code reuse. Keep the module name (`MageOS_Blog`), composer package (`mageos/module-blog`), and namespace (`MageOS\Blog`) — these are Mage-OS-branded and legitimate.

---

## 2. Decisions summary

| Dimension | Choice |
|---|---|
| Audience | Luma + Hyvä (bundled, transparent template-path resolution) |
| Entities (v1) | Post, Category, Tag, Author |
| Compat floor | Magento 2.4.6 / Mage-OS 1.1+ / PHP 8.2 |
| URL strategy | Standard `url_rewrite` table, fixed `/blog/` prefix — no custom Router |
| Detail features | OG/Twitter/JSON-LD/sitemap, reading time, views count, related posts, related products, RSS, native social share |
| Storefront pages | Index, category, tag, author, search, plus sidebar |
| GraphQL | Full — read queries + admin-authed mutations |
| Widgets | 6 — Recent Posts, Featured Post, Post Link, Category Link, Tag Link, Post List |
| Search | `Magento_Search` (OpenSearch/Elasticsearch), indexer in SCHEDULE mode |
| Package / NS / License | `mageos/module-blog` / `MageOS\Blog` / OSL-3.0 |
| Content editor | WYSIWYG (TinyMCE). No PageBuilder in v1. |
| Testing | Unit + Integration + Infection (MSI ≥ 75%) + PHPStan L8 + PHP-CS-Fixer |
| CI | GitHub Actions via `graycoreio/github-actions-magento2` |
| Admin auth for GraphQL | Resolver-side context check, no custom `@auth` directive |

---

## 3. Design

### 3.1 Foundation

**Module metadata**

- `composer.json`: `mageos/module-blog`, `license: OSL-3.0`, `type: magento2-module`. Requires `php ^8.2`, `magento/framework ^103.0.7`. Explicit module requires: `module-store`, `module-cms`, `module-catalog`, `module-customer`, `module-ui`, `module-backend`, `module-url-rewrite`, `module-search`, `module-sitemap`, `module-widget`, `module-graph-ql`, `module-media-storage`.
- `etc/module.xml` sequence: `Magento_UrlRewrite`, `Magento_Cms`, `Magento_Catalog`, `Magento_Search`, `Magento_Sitemap`, `Magento_Widget`.
- Explicitly **not** depended on: `Magento_PageBuilder`, `Magento_AdminGws`, `Magento_GraphQlCache`.

**Data model (`etc/db_schema.xml`)**

Entities (all tables prefixed `mageos_blog_`, all `innodb`, PK auto-increment):

| Table | Key columns |
|---|---|
| `mageos_blog_post` | post_id, url_key, title, content, short_content, featured_image, featured_image_alt, author_id (FK → author), publish_date, reading_time, views_count, status (smallint enum), meta_title, meta_description, meta_keywords, meta_robots, og_title, og_description, og_image, og_type, creation_time, update_time |
| `mageos_blog_category` | category_id, url_key, title, description, parent_id (nullable, FK self), position, meta_*, include_in_menu, include_in_sidebar, is_active |
| `mageos_blog_tag` | tag_id, url_key, title, description, meta_*, is_active |
| `mageos_blog_author` | author_id, name, slug, bio, avatar, email, twitter, linkedin, website, is_active |

Pivots: `mageos_blog_post_store`, `mageos_blog_category_store`, `mageos_blog_tag_store`, `mageos_blog_post_category`, `mageos_blog_post_tag`, `mageos_blog_post_related_product` (w/ position), `mageos_blog_post_related_post` (w/ position).

Indexes: fulltext on post `(title, short_content, content, meta_description)`; btree on `publish_date`, `status`, `views_count`, `parent_id`, `author_id`. FK cascades on delete for all pivots. URL key uniqueness scoped per-store via the `url_rewrite` table (not via a UNIQUE column on the entity — entity may have the same `url_key` across stores).

**Service contracts (`Api/`)**

- Repositories per entity: `PostRepositoryInterface`, `CategoryRepositoryInterface`, `TagRepositoryInterface`, `AuthorRepositoryInterface` — standard `getById()`, `getByUrlKey(string, int)`, `save()`, `delete()`, `getList(SearchCriteriaInterface)` → `*SearchResultsInterface`.
- Data interfaces: `Api/Data/{Post,Category,Tag,Author}Interface`, all supporting extension attributes.
- Management: `PostManagementInterface::publish(int): void`, `::incrementViews(int): void`, `::computeReadingTime(string): int`. `UrlKeyGeneratorInterface::generate(string $title, string $entityType, ?int $storeId): string` (collision-safe, reserved-slug-safe).
- Providers: `RelatedPostsProviderInterface::forPost(PostInterface, int): PostInterface[]`.
- Enum: PHP `enum BlogPostStatus: int { case Draft = 0; case Scheduled = 1; case Published = 2; case Archived = 3; }` persisted as smallint.

**Coding standards**

- `declare(strict_types=1);` every file.
- Constructor property promotion; `readonly` where safe.
- Return types on every method (including `void`, `never`).
- PSR-12 + `magento/magento-coding-standard`. PHP-CS-Fixer for autofix.
- No `Helper/` classes, no static methods in domain code, no `ObjectManager` outside factories.
- Unit test methods: `final` class, `snake_case` method names per project convention.

### 3.2 URL & routing

**URL scheme** (fixed `/blog/` prefix):

| URL | Purpose |
|---|---|
| `/blog/` | Index — paginated post list |
| `/blog/<post-slug>` | Post detail |
| `/blog/category/<slug>` | Category detail |
| `/blog/tag/<slug>` | Tag detail |
| `/blog/author/<slug>` | Author detail |
| `/blog/search?q=...` | Blog search |
| `/blog/rss` | RSS feed |

**Reserved slugs** (cannot be used as post `url_key`): `category`, `tag`, `author`, `search`, `rss`, `page`, `feed`. Validated in repository `save()`.

**`url_rewrite` integration — no custom Router**

Repositories call `Magento\UrlRewrite\Model\UrlPersistInterface::replace()` on save with rows like:

```
entity_type        entity_id  request_path            target_path                        store_id
mageos_blog_post   42         blog/my-post            blog/post/view/id/42               1
mageos_blog_cat    7          blog/category/news      blog/category/view/id/7            1
mageos_blog_tag    3          blog/tag/magento        blog/tag/view/id/3                 1
mageos_blog_auth   5          blog/author/jane        blog/author/view/id/5              1
```

Slug changes create a 301 (`OptionProvider::REDIRECT_TYPE_PERMANENT`) preserving the old path. Delete removes all rows for that entity. Magento's built-in `UrlRewrite\Controller\Router` dispatches — this module registers **no** `RouterInterface` implementation.

**Routing & controllers**

`etc/frontend/routes.xml` declares route `blog` with frontName `blog`. Controllers are single-action `HttpGetActionInterface` implementations:

```
Controller/Index/Index.php         → blog/index/index
Controller/Post/View.php           → blog/post/view          (id)
Controller/Category/View.php       → blog/category/view      (id)
Controller/Tag/View.php            → blog/tag/view           (id)
Controller/Author/View.php         → blog/author/view        (id)
Controller/Search/Index.php        → blog/search/index       (q)
Controller/Rss/Index.php           → blog/rss/index
Controller/Post/IncrementViews.php → blog/post/incrementViews (POST, CSRF)
```

Each controller: resolve entity → layout handle → 404 forward on not-found / inactive / wrong store. No abstract `App/Action/Action.php` parent — each controller stands alone (~30–60 lines).

Module-disabled short-circuit lives in a plugin on `FrontController` or a predispatch observer on the storefront actions (not a Router plugin).

**`UrlKeyGeneratorInterface`**

```php
public function generate(string $title, string $entityType, ?int $storeId = null): string;
```

Normalize: ASCII transliterate → lowercase → `[^a-z0-9]+` → `-` → trim/dedupe. Reserved-slug check runs first. Collision: query existing `url_key` for same entity table in the target store; if taken, append `-2`, `-3`… Manual editing of `url_key` in the admin form re-runs validation on save.

**GraphQL URL resolver**

Re-use Magento's `urlResolver(url: ...)` query. Schema additions:

```graphql
enum UrlRewriteEntityTypeEnum { ... BLOG_POST  BLOG_CATEGORY  BLOG_TAG  BLOG_AUTHOR }
```

Rewrites live in the standard `url_rewrite` table, so the stock resolver returns the right type/id. Register entity types via `CustomUrlLocatorInterface`.

**Pagination & canonicals**

- `?p=2` pagination (Magento standard). No `/blog/page/N` URLs.
- Canonical always points to slug URL (never paginated, never with query).
- `rel=prev/next` on paginated index/category/tag/author.

### 3.3 Storefront

**Blocks stay thin; logic lives in ViewModels.**

| Page | Block | ViewModel |
|---|---|---|
| Index | `Block\Post\Listing` | `ViewModel\Post\Listing` (index mode) |
| Post detail | `Block\Post\View` | `ViewModel\Post\Detail` |
| Category | `Block\Category\View` | `ViewModel\Category\Detail` + `Post\Listing` (filtered) |
| Tag | `Block\Tag\View` | `ViewModel\Tag\Detail` + `Post\Listing` |
| Author | `Block\Author\View` | `ViewModel\Author\Detail` + `Post\Listing` |
| Search | `Block\Search\Results` | `ViewModel\Search\Results` |

`ViewModel\Post\Detail` exposes: `getPost()`, `getFormattedPublishDate()`, `getReadingTimeLabel()`, `getViewsCount()`, `getCategories()`, `getTags()`, `getAuthor()`, `getCanonicalUrl()`, `getOgTags()`, `getTwitterTags()`, `getJsonLd()`, `getSocialShareLinks()`, `getRelatedPosts()`, `getRelatedProducts()`.

**Hyvä bundling — transparent template path resolution**

`Plugin\Magento\Framework\View\TemplateEngine\Php::beforeRender` inspects the current theme via `HyvaThemeDetectionInterface`. On Hyvä, it substitutes `MageOS_Blog::<path>.phtml` with `MageOS_Blog::hyva/<path>.phtml` if that file exists; otherwise the Luma template runs (graceful fallback).

**Zero Hyvä-branching code inside any `.phtml`.** Luma templates use Magento helpers freely; Hyvä templates are pure Alpine + Tailwind. Theme fallback works normally.

```
view/frontend/templates/post/view.phtml              ← Luma
view/frontend/templates/hyva/post/view.phtml         ← Hyvä
view/frontend/layout/blog_{index_index,post_view,category_view,tag_view,author_view,search_index,default}.xml
view/frontend/layout/catalog_product_view.xml        ← injects "Related Posts" into product page
```

**Sidebar**

`Block\Sidebar\Container` iterates enabled children (config `mageos_blog/sidebar/*_enabled` + `sort_order`):

- `Sidebar\SearchBox` — form posting to `/blog/search`
- `Sidebar\RecentPosts` — N most recent
- `Sidebar\CategoryList` — tree with post counts
- `Sidebar\TagCloud` — weighted by post count
- `Sidebar\Archive` — posts grouped by year → month

Each sidebar block has its own ViewModel.

**Widgets (`etc/widget.xml`)**

| Widget | Parameters (admin chooser UI) |
|---|---|
| `recent_posts` | count, template_variant |
| `featured_post` | post_id (grid chooser) |
| `post_link` | post_id, show_excerpt |
| `category_link` | category_id |
| `tag_link` | tag_id |
| `post_list` | category_ids[], tag_ids[], author_id, limit, sort |

Chooser grids — three plain `Magento\Backend\Block\Widget\Grid` subclasses in `Block\Adminhtml\Widget\Chooser\{Post,Category,Tag}.php`, ~60 lines each. No 245-line `Featured\Grid\Chooser` monolith.

### 3.4 Admin

- `etc/adminhtml/routes.xml` — frontName `mageos_blog` (distinct from storefront `blog`).
- `etc/adminhtml/menu.xml` — top-level **Blog** menu under **Content**: Posts, Categories, Tags, Authors, Configuration.
- `etc/acl.xml` — `MageOS_Blog::{post,category,tag,author,config}`.
- `etc/adminhtml/system.xml` — settings under `mageos_blog/*` with constants in `Model\Config`.

**UI components:**

```
view/adminhtml/ui_component/
  mageos_blog_post_listing.xml           mageos_blog_post_form.xml
  mageos_blog_category_listing.xml       mageos_blog_category_form.xml
  mageos_blog_tag_listing.xml            mageos_blog_tag_form.xml
  mageos_blog_author_listing.xml         mageos_blog_author_form.xml
  mageos_blog_post_related_posts_listing.xml      ← nested in post form
  mageos_blog_post_related_products_listing.xml   ← nested in post form
  mageos_blog_widget_chooser_{post,category,tag}.xml
```

Post form tabs: **General** (title, url_key, status, publish_date, author), **Content** (WYSIWYG short + full), **Images** (featured + alt + og), **SEO** (meta + OG + Twitter + robots), **Taxonomy** (categories + tags), **Related** (posts + products), **Stores**.

Data providers in `Ui/DataProvider/{Post,Category,Tag,Author}/Form/DataProvider.php` (extending `Magento\Ui\DataProvider\AbstractDataProvider`) and `Ui/DataProvider/{...}/Grid/Collection.php`.

Admin controllers — one action per file: `Controller/Adminhtml/Post/{Index,NewAction,Edit,Save,Delete,MassEnable,MassDisable,MassDelete,InlineEdit,UploadImage}.php`. Same shape for other entities.

**Deliberately excluded from v1:** `Helper/` classes, `CustomCss` block, AddThis integration, `PreviewUrl` model, `AdminGws` plugin, PageBuilder entities-pool registration.

### 3.5 SEO

**Post detail head tags** contributed via `Magento\Framework\View\Page\Config`:

| Tag | Source chain |
|---|---|
| `<title>` | `meta_title` → `title` |
| `meta description` | `meta_description` → truncated `short_content` |
| `meta robots` | `meta_robots` → `mageos_blog/post/default_robots` → store default |
| `rel=canonical` | Always `/blog/<slug>` |

**OpenGraph** (`ViewModel\Post\Detail::getOgTags()`): `og:type=article`, `og:title/description/image/url`, `article:published_time`, `article:modified_time`, `article:author`, `article:section`, `article:tag`. Override chain: `og_*` column → `meta_*` column → plain field.

**Twitter cards**: `summary_large_image` when og_image present else `summary`. `twitter:site` from config.

**JSON-LD**: `Block\Post\JsonLd` renders `schema.org/BlogPosting`. Fields: `headline`, `image`, `datePublished`, `dateModified`, `author` (Person), `publisher` (Organization, reuses store name + logo URL), `mainEntityOfPage`. Inline `<script type="application/ld+json">`.

**Category/Tag/Author detail**: canonical to slug URL, `rel=prev/next` on paginated variants, meta title/description cascade.

**Breadcrumbs** (standard Magento `breadcrumbs` block):

- Post: Home → Blog → [Category →] Post Title
- Category/Tag/Author: Home → Blog → X
- Index: Home → Blog

**Sitemap integration**: `Model\Sitemap\ItemProvider\{Post,Category,Tag}` registered with `Magento\Sitemap\Model\ItemProvider\Composite` in `etc/di.xml`. Read `mageos_blog/sitemap/<entity>/{enabled,frequency,priority}`. Author URLs excluded (low SEO value; thin-page risk).

### 3.6 Search (`Magento_Search`, OpenSearch/Elasticsearch)

Components:

- `etc/search_request.xml` — request `mageos_blog_post_search`, fulltext on `title^3 + short_content^2 + content + meta_description^0.5`, buckets for `category_id`, `tag_id`, `author_id`.
- `etc/indexer.xml` — indexer `mageos_blog_post_fulltext`, mode **SCHEDULE** (batched via cron; low volume makes realtime unnecessary).
- `etc/mview.xml` — subscribe to `mageos_blog_post`, `mageos_blog_post_store`, `mageos_blog_post_category`, `mageos_blog_post_tag`.
- `Model\Indexer\Post\Fulltext\Action\{Full,Rows}` — map post rows to search documents.
- `Model\Indexer\Post\Fulltext\IndexerHandler` — writes via `Magento\Framework\Indexer\SaveHandler\IndexerInterface`.
- `Controller\Search\Index` uses `Magento\Search\Api\SearchInterface` with request name `mageos_blog_post_search`; results rendered via `ViewModel\Search\Results`.

Empty query → redirect to `/blog/`. Zero hits → friendly "no results" template, not a 404.

### 3.7 GraphQL

Types (`etc/schema.graphqls`): `BlogPost`, `BlogCategory`, `BlogTag`, `BlogAuthor` — mirror `Api/Data` interfaces. `BlogPost` nests `categories`, `tags`, `author`, `related_posts`, `related_products`.

**Public queries:**

```graphql
blogPosts(filter: BlogPostFilterInput, sort: BlogPostSortInput, pageSize: Int = 20, currentPage: Int = 1): BlogPosts
blogCategories(filter: BlogCategoryFilterInput, pageSize: Int = 20, currentPage: Int = 1): BlogCategories
blogTags(filter: BlogTagFilterInput, pageSize: Int = 20, currentPage: Int = 1): BlogTags
blogAuthors(filter: BlogAuthorFilterInput, pageSize: Int = 20, currentPage: Int = 1): BlogAuthors
```

Plus `UrlRewriteEntityTypeEnum` extension for URL resolver support.

**Admin mutations:**

```graphql
createBlogPost(input: BlogPostInput): BlogPost
updateBlogPost(id: Int!, input: BlogPostInput): BlogPost
deleteBlogPost(id: Int!): Boolean
# same shape for BlogCategory, BlogTag, BlogAuthor
```

Auth: resolver-side check — `if (!$context->getExtensionAttributes()?->getIsAdmin()) throw new GraphQlAuthorizationException(...)` plus ACL check against `MageOS_Blog::<entity>` resource. No custom `@auth` directive.

Resolvers in `Model\Resolver\{Post,Category,Tag,Author}\{List,Detail,Create,Update,Delete}.php`. Each delegates to repository/management; zero domain logic in resolver.

### 3.8 RSS, cron, cross-cutting

**RSS**: `Controller\Rss\Index` → `Model\Rss\BlogFeed implements Magento\Framework\App\Rss\DataProviderInterface`. Emits Atom 1.0, last N posts (`mageos_blog/rss/limit`, default 20). Filters: `?category=<slug>`, `?tag=<slug>`, `?author=<slug>`. `etc/rss.xml` registers the feed.

**Cron** (`etc/crontab.xml`): single job `mageos_blog_publish_scheduled`, `* * * * *`:

```
Cron\PublishScheduledPosts::execute():
  1. Short-circuit if module disabled
  2. Fetch posts with status = Scheduled AND publish_date <= NOW()
  3. For each: PostManagement::publish() → status = Published, dispatch clean_cache_by_tags event
  4. Per-post try/catch; one bad post logs, loop continues
```

**Related posts algorithm (`RelatedPostsProviderInterface::forPost`)**:

```
1. Manual relations from mageos_blog_post_related_post (ordered by position), up to N
2. If results < N, append algorithmic:
   - Posts sharing ≥1 category or ≥1 tag with P
   - Ordered: overlap_count DESC, publish_date DESC
   - Exclude P and duplicates
   - Scoped: current store, status = Published
```

Cached via `Magento\Framework\App\CacheInterface`; key includes `post_id + store_id + limit`; tags include the post's cache tag + each related post's cache tag (auto-invalidates on save of any involved post).

**Reading time**: `PostManagementInterface::computeReadingTime(string): int` — strip tags, word count / 200 wpm, `max(1, ceil(...))`. Called on save via before-plugin on `PostRepository::save`, persisted to `reading_time` column. Never recomputed at view time.

**Views count**: non-cacheable POST endpoint `/blog/post/incrementViews` (CSRF via form_key). Luma: `view/frontend/web/js/views-count.js` on DOMContentLoaded. Hyvä: Alpine `x-init`. Server: atomic `UPDATE mageos_blog_post SET views_count = views_count + 1 WHERE post_id = ?`. Session throttle — one increment per `(session_id, post_id)` stored in session to defeat reload spam.

**Native social share** (`ViewModel\Post\SocialShare::getLinks()`):

| Network | URL template |
|---|---|
| Facebook | `facebook.com/sharer/sharer.php?u={canonical}` |
| Twitter/X | `twitter.com/intent/tweet?url={canonical}&text={title}` |
| LinkedIn | `linkedin.com/sharing/share-offsite/?url={canonical}` |
| Pinterest | `pinterest.com/pin/create/button/?url={canonical}&media={og_image}&description={title}` |
| Email | `mailto:?subject={title}&body={canonical}` |

Active network list from `mageos_blog/social/networks[]` multi-select. Rendered as `<a rel="noopener noreferrer nofollow">` with inline SVG icons. No third-party JS (no AddThis).

### 3.9 Testing

**Unit (`Test/Unit/`)** — PHPUnit 10, `#[Test]` attributes, `final class`, `snake_case` method names. Constructor-injected mocks. Targets: `Config`, `UrlKeyGenerator`, enums, ViewModels, management services, `RelatedPostsProvider`, resolvers. Coverage target ≥ 80% on `Model/` + `ViewModel/` + `Cron/`.

**Integration (`Test/Integration/`)** — boots real Magento, `#[DataFixture]` attribute fixtures. Covers:

- Repository save → `url_rewrite` row created; `url_key` change → 301 redirect; delete → rows removed.
- Scheduled publishing cron: scheduled past-dated post → Published + FPC tag dispatched.
- Search indexer: post save triggers mview update; `/blog/search?q=...` returns hits.
- GraphQL: public queries succeed without auth; mutations reject without admin token.
- Controller dispatch: correct post loads by slug; 404 on wrong-store / inactive.
- Cron short-circuits when module disabled.

**Mutation (Infection)** — `infection.json5` targets `Model/`, `ViewModel/`, `Cron/`. MSI ≥ 75% on v1 release. Runs on PRs only (not pushes).

**Static (PHPStan level 8)** — `phpstan.neon` with Magento-aware rules. `phpstan-baseline.neon` for any holdouts (expected to be ~empty in a fresh rewrite).

**Code style** — PHP-CS-Fixer (canonical autofix) + `magento/magento-coding-standard` via phpcs (secondary validator).

### 3.10 CI (GitHub Actions, Graycore-based)

`.github/workflows/ci.yml` on push + PR:

| Job | Runner / Action | Matrix |
|---|---|---|
| Unit | `graycoreio/github-actions-magento2/unit-test` | PHP 8.2, 8.3 |
| Integration | `graycoreio/github-actions-magento2/integration-test` | PHP 8.2 × Magento 2.4.6-p7, PHP 8.3 × Magento 2.4.7 |
| Static | `graycoreio/github-actions-magento2/static-test` (phpstan L8) | PHP 8.3 |
| CS | PHP-CS-Fixer + phpcs (Magento2 standard) | PHP 8.3 |
| Infection | `infection/infection-action` | PHP 8.3, PR only |

All jobs run in parallel. PR merges blocked until green.

`.github/workflows/release.yml` on tag `v*`: build changelog from conventional commits, create GitHub release. Packagist auto-syncs via repo webhook.

### 3.11 Localization

`i18n/en_US.csv` generated via `bin/magento i18n:collect-phrases` in the release workflow, committed on tag. No hardcoded strings outside `__()`. Third parties may drop in `de_DE.csv`, `fr_FR.csv`, etc.

---

## 4. Build sequence

Five phases, ~7 weeks solo. Phase 2 and 3 can run in parallel with a second contributor (saves ~1.5 weeks).

| Phase | Scope | Deliverable | Duration |
|---|---|---|---|
| **1 — Foundation** | `composer.json`, `registration.php`, `module.xml`, `LICENSE`, `db_schema.xml` (4 entities + pivots + indexes), `Api/Data` interfaces, repositories, ResourceModels + collections, `UrlKeyGenerator` + reserved-slug validator, CI scaffolding | `v0.1.0` — CRUD via repository API. Unit + integration green. | ~1 week |
| **2 — Admin** | UI components (listings + forms for all 4 entities), data providers, admin controllers, menu/routes/ACL/`system.xml`, related-posts/related-products selection UIs in Post form | `v0.2.0` — merchants can create posts/categories/tags/authors via admin. | ~1.5 weeks |
| **3 — Storefront (Luma)** | `url_rewrite` integration in repositories (with 301 on slug change), 7 controllers, layouts, blocks + ViewModels, Luma templates, sidebar, `PostManagement` (publish / incrementViews / computeReadingTime), `RelatedPostsProvider`, scheduled-publishing cron | `v0.3.0` — full Luma storefront end-to-end. url_rewrite lifecycle + cron integration-tested. | ~2 weeks |
| **4 — Hyvä + SEO + search + widgets + RSS** | Hyvä templates (all 7 pages + sidebar), `TemplateEngine\Php` plugin for path resolution, OG/Twitter/JSON-LD ViewModels, sitemap ItemProviders, search request.xml + indexer + mview (SCHEDULE), 6 widgets + 3 chooser grids, RSS Atom feed, native social share | `v0.4.0` — feature-parity with commercial blog modules. | ~1.5 weeks |
| **5 — GraphQL + polish + docs** | GraphQL schema (types + queries + admin-authed mutations), `UrlRewriteEntityTypeEnum` extension, resolver files, README (install / config / compat / upgrade), `i18n/en_US.csv`, CHANGELOG.md, Infection MSI ≥ 75% achieved | `v1.0.0` — ship | ~1 week |

---

## 5. Out of scope for v1 (deliberately)

These are explicit non-goals. Track for v1.1+ if demand appears:

- **Comments** (native, moderation UI, spam handling, email notifications) — large surface, low payoff for v1.
- **Importers** from Mageplaza / Magefan / Aheadworks / Mirasvit / WordPress — biggest schema-copy risk; migration tooling can ship as a separate `mageos/module-blog-migration` package.
- **PageBuilder** content editing — adds hard dependency and heavy Hyvä compat layer; WYSIWYG covers v1 merchants.
- **AddThis / third-party social scripts** — native share links cover the need; no external JS.
- **Configurable URL prefix** (`/news`, `/insights`) — document: rewrite at the webserver if needed. Keeps url_rewrite lifecycle simple.
- **Custom CSS injection per page** (fork's `CustomCss` block) — merchants style via theme.
- **Preview URL with secret token model** — admin preview iframes the detail page with a secret param.
- **AdminGWS plugin** — Magento Commerce-only feature; not applicable to open-source Mage-OS.
- **Translation UIs, author pages with gravatar autofetch, comment-driven rich snippets, MFTF tests, performance benchmarks.**

---

## 6. Success criteria (Definition of Done for v1.0.0)

- All 5 phases complete, green CI on PHP 8.2 + 8.3 × Magento 2.4.6-p7 + 2.4.7.
- Unit coverage ≥ 80% on `Model/`, `ViewModel/`, `Cron/`.
- Infection MSI ≥ 75%.
- PHPStan level 8, zero errors, empty (or near-empty) baseline.
- README covers install, configuration, compatibility matrix, and upgrade from the forked 0.x.
- LICENSE present (OSL-3.0), CHANGELOG.md follows Keep-a-Changelog.
- Tagged `v1.0.0` on `main`; Packagist sync verified.

---

## 7. Open items (escalate as they arise)

- **Upgrade path from forked 0.x installs**: the forked module's tables are `blog_*` (unprefixed); v1 uses `mageos_blog_*`. A one-shot migration script must ship with v1 if any merchant ran the forked code in production. If no real-world installs exist, document explicitly that v1 is a greenfield install only.
- **Packagist publication**: `mageos/module-blog` namespace must be owned by the Mage-OS org on Packagist before tagging v1.0.0.
- **Attribution to Magefan**: even with a full rewrite, documenting the original inspiration in README is good community hygiene. Phrasing: "Design inspired by Magefan Blog (OSL-3.0); v1 is an independent implementation with no shared code."
