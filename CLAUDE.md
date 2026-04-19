# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository layout

This is a **Magento 2 module** (`MageOS_Blog`, composer package `mageos/module-blog`). The repo root *is* the module directory — `registration.php` registers it via `ComponentRegistrar::MODULE` with `__DIR__`, and the PSR-4 root `MageOS\Blog\\` maps to the repo root. When installed normally, this tree lives at `app/code/MageOS/Blog/` or `vendor/mageos/module-blog/`.

There is no test suite, build system, or linter configuration checked into this repo. It is consumed by a parent Magento 2 application that provides those.

## Common commands (run from the parent Magento app, not this repo)

After changing anything DI-relevant, schema, routes, layout, or UI components:

```bash
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:clean
```

These are the smoke-test commands — if DI or schema wiring is broken, `setup:di:compile` will fail first.

Module toggle:

```bash
bin/magento module:enable MageOS_Blog
bin/magento module:status MageOS_Blog
```

## Architecture — non-obvious pieces

### Routing goes through a custom Router, not standard Magento routes

`etc/{frontend,adminhtml}/routes.xml` declares the `blog` frontName, but almost no URL is dispatched the standard way. `Controller/Router.php` implements `\Magento\Framework\App\RouterInterface` and delegates path resolution to `MageOS\Blog\Model\UrlResolver` (`Api\UrlResolverInterface`).

`UrlResolver::resolve($path)` parses `/blog/...` paths and returns `['type' => controllerName, 'id' => ..., 'params' => ...]`, where `type` is one of `Url::CONTROLLER_*` constants (`blog_index`, `post`, `category`, `tag`, `search`). The router then forwards to that controller. Permalink behavior (suffixes, slash handling, category nesting in post URLs, `/page/N` pagination) lives in `Model/Url.php` + `Model/UrlResolver.php` and is driven by `mageos_blog/permalink/*` config.

**When adding new URL patterns, edit `UrlResolver::resolve`, not routes.xml.** The large commented-out block in `Controller/Router.php::match()` is the legacy pre-resolver logic kept for reference.

### Module is gated by a single enabled flag

`Model\Config::XML_PATH_ENABLED` (`mageos_blog/general/enabled`) defaults to `0`. The Router short-circuits to `null` if disabled, and the Cron no-ops. Any new entry point should respect this.

### Config is centralized

`Model/Config.php` defines every `XML_PATH_*` constant. Do not scatter `scopeConfig->getValue('mageos_blog/...')` calls — add a constant and (if needed) a getter here. Admin-facing definitions live in `etc/adminhtml/system.xml`, defaults in `etc/config.xml`.

### Hyvä theme support is injected into the template engine

`Plugin/Magento/Framework/View/TemplateEngine/Php.php` adds `$hyvaThemeDetection` to every `.phtml` dictionary via a `beforeRender` plugin. Templates under `view/frontend/templates/` can call `$hyvaThemeDetection->execute()` to branch rendering. `Api\HyvaThemeDetectionInterface` → `Model\HyvaThemeDetection` extends `Model\AbstractThemeDetection`, which walks the theme inheritance chain looking for a theme path containing the theme name. The companion Hyvä templates ship in the separate `mageos/module-blog-hyva` package (listed as a `suggest` in `composer.json`).

### PageBuilder compatibility

`etc/di.xml` registers `blog_post`, `blog_category`, `blog_tag` with `Magento\PageBuilder\Model\UpgradableEntitiesPool` so that the `content` / `short_content` fields get PageBuilder upgrade handling. Adding new content-bearing entities requires extending that pool entry.

### Sitemap integration

Three `Model\Sitemap\ItemProvider\*` classes (Index, Post, Category) are wired into `Magento\Sitemap\Model\ItemProvider\Composite` via `etc/di.xml`. Per-entity enable/frequency/priority comes from `mageos_blog/sitemap/*`. `Api\SitemapConfigInterface` → `Model\Sitemap\SitemapConfig` reads those values.

### Image upload is one virtualType, three controllers

`MageOS\Blog\ImageUpload` is a virtualType of `Model\ImageUploader` with `basePath=mageos_blog`, `baseTmpPath=mageos_blog/tmp`. `Controller\Adminhtml\Post\Upload\{FeaturedImg,OgImg,Gallery}` all inject that same virtualType. Adding a new image field = add a controller that takes `imageUploader` as `MageOS\Blog\ImageUpload`.

### Widget links

`Block\Widget\Link` is the shared base; `Block\Widget\{Post,Category,Tag}Link` are virtualTypes in `etc/di.xml` that differ only by injected `modelRepository`. Widget XML lives in `etc/widget.xml`.

### GraphQL

`etc/schema.graphqls` declares `blogPosts`, `blogCategories`, `blogTags` queries. Resolvers in `Model/Resolver/` ({Posts,Categories,Tags}.php).

### Cron

`etc/crontab.xml` runs `Cron\PublishScheduledPosts::execute` every minute. It finds posts with `is_active=1` whose `publish_time` fell within the last 2 minutes and re-saves them to invalidate FPC — this is the mechanism that makes scheduled posts appear under full-page caching.

### Data model

Primary tables (`etc/db_schema.xml`): `blog_post`, `blog_category`, `blog_tag`, `blog_comment`, plus pivots `blog_post_store`, `blog_category_store`, `blog_tag_store`, `blog_post_category`, `blog_post_tag`, `blog_post_relatedproduct`, `blog_post_relatedpost`. Foreign keys cascade on delete. Fulltext indexes exist on `title/meta_keywords/meta_description/identifier/content` for posts and categories — don't remove them when altering those tables.

### Admin UI components

`view/adminhtml/ui_component/*.xml` + data providers in `Ui/DataProvider/{Post,Category,Tag,Comment}/`. Admin grids and forms follow standard Magento UI component conventions.

### Import from other blog modules

`Model/Import/` contains importers for AheadWorks (Aw, Aw2), Magefun, Mageplaza (Mageplaza, Mageplaza1), Mirasvit, and WordPress. All extend `AbstractImport`. The admin entry point is under `Controller/Adminhtml/Import/`.

## Conventions found in this codebase

- `declare(strict_types=1);` at the top of every new PHP file (matches existing files).
- Config constants: `XML_PATH_<SECTION>_<FIELD>` in `Model/Config.php`, built from `MODULE_SYS_KEY` + `SYS_*` constants.
- Controller `type` strings come from `Model\Url::CONTROLLER_*` — don't hardcode `'post'`, `'category'`, etc.
- New `Api/*Interface` → paired `Model/*` implementation wired via `<preference>` in `etc/di.xml`.
- The module depends on (sequence) `Magento_Cms` and `Magento_Catalog` (`etc/module.xml`).
