# Blog Frontend Redesign Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Ship a not-embarrassing blog UI — kill the duplicate `<h1>`, strip the Luma catalog-sidebar bleed, and style the index, post detail, category/tag/author landing, and search pages with a single 250-line stylesheet and a handful of template rewrites.

**Architecture:** All blog layouts switch to `layout="1column"`. `blog_default.xml` stops injecting into `sidebar.additional`, which removes the Luma wishlist / compare / last-added bleed by construction. One new stylesheet `view/frontend/web/css/blog.css` namespaced under `.mageos-blog`. Templates stop emitting their own `<h1>` on listings; on post detail the `<h1>` moves into the article and the page title-wrapper is suppressed. No JS. No Hyvä twin. No schema / PHP / admin changes.

**Tech Stack:** Magento 2 layout XML, PHTML templates, vanilla CSS with custom properties, existing `ViewModel\Post\{Listing,Detail,SocialShare}` + `ViewModel\Category\Detail` + `ViewModel\Tag\Detail` + `ViewModel\Author\Detail` + `Model\Post\PostsByAssignmentProvider`. All data is already in place.

**Design doc:** `docs/plans/2026-04-21-blog-frontend-redesign-design.md`.

---

## Task 1 — Switch blog to 1column + strip Luma sidebar bleed

**Files:**
- Modify: `view/frontend/layout/blog_default.xml` (full rewrite — 14 lines down to ~12 lines).

**Step 1:** Read the current `blog_default.xml` to confirm the before state.

Run: `cat view/frontend/layout/blog_default.xml`

Expected content:
```xml
<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      layout="2columns-right"
      xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <body>
        <referenceContainer name="sidebar.additional">
            <block class="Magento\Framework\View\Element\Template"
                   name="mageos_blog_sidebar_container"
                   template="MageOS_Blog::sidebar/container.phtml"/>
        </referenceContainer>
    </body>
</page>
```

**Step 2:** Replace the file contents with the 1column version, drop the sidebar injection, add the CSS include:

```xml
<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      layout="1column"
      xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <head>
        <css src="MageOS_Blog::css/blog.css"/>
    </head>
</page>
```

**Step 3:** Validate the XML.

Run: `cd /Users/david/Herd/mage-os-typesense && warden env exec -T php-fpm xmllint --noout /var/www/html/app/code/MageOS/Blog/view/frontend/layout/blog_default.xml && echo ok`

Expected: `ok`.

**Step 4:** Flush caches + varnish.

```
cd /Users/david/Herd/mage-os-typesense && warden env exec -T php-fpm bin/magento cache:flush && warden env exec -T varnish varnishadm "ban req.url ~ ."
```

Confirm `/blog/` renders without the wishlist / compare / last-added sidebar blocks.

Run: `curl -sk https://app.mage-os-typesense.test/blog/ | grep -cE 'wishlist|compare-products|My Wish List|Compare Products' && echo "should be 0"`

Expected: `0`.

**Step 5:** Commit.

```
git add view/frontend/layout/blog_default.xml
git commit -m "refactor(frontend): blog layout to 1column; strip Luma sidebar bleed"
```

---

## Task 2 — New stylesheet `view/frontend/web/css/blog.css`

**Files:**
- Create: `view/frontend/web/css/blog.css`.

**Step 1:** Create the stylesheet with exactly this content:

```css
/*
 * MageOS Blog — frontend stylesheet.
 *
 * Scoped under `.mageos-blog`. Vanilla CSS (no Less), no JS, no Hyvä twin.
 * All custom properties declared on the root container so each card / post
 * can be overridden at the component level without global leakage.
 */

.mageos-blog {
    --mageos-blog-font: ui-sans-serif, system-ui, -apple-system, "Segoe UI",
        Roboto, Helvetica, Arial, sans-serif;
    --mageos-blog-font-serif: Charter, "Iowan Old Style", Georgia, serif;
    --mageos-blog-text: 1rem;
    --mageos-blog-h1: clamp(2rem, 3.2vw + 1rem, 3rem);
    --mageos-blog-h2: 1.5rem;
    --mageos-blog-meta-size: 0.875rem;

    --mageos-blog-ink: #1a1a1a;
    --mageos-blog-meta: #666;
    --mageos-blog-border: rgba(0, 0, 0, 0.08);
    --mageos-blog-chip-bg: rgba(0, 0, 0, 0.04);

    color: var(--mageos-blog-ink);
    font-family: var(--mageos-blog-font);
    font-size: var(--mageos-blog-text);
    line-height: 1.7;
}

.mageos-blog a {
    color: inherit;
    text-decoration: underline;
    text-decoration-thickness: 1px;
    text-underline-offset: 3px;
}

.mageos-blog a:hover {
    text-decoration-thickness: 2px;
}

.mageos-blog-container {
    max-width: 760px;
    margin-inline: auto;
    padding-inline: 1.5rem;
    padding-block: 2rem;
}

/* ---------------- Context headers (category/tag/author/search) ---------------- */

.mageos-blog-context {
    margin-bottom: 3rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid var(--mageos-blog-border);
}

.mageos-blog-context__title {
    font-size: var(--mageos-blog-h1);
    line-height: 1.15;
    margin: 0 0 0.5rem;
}

.mageos-blog-context__description {
    color: var(--mageos-blog-meta);
    font-size: 1rem;
}

.mageos-blog-context__avatar {
    width: 96px;
    height: 96px;
    border-radius: 50%;
    object-fit: cover;
    margin-bottom: 1rem;
}

/* ---------------- Listing card ---------------- */

.mageos-blog-card {
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid var(--mageos-blog-border);
}

.mageos-blog-card:last-child {
    border-bottom: 0;
}

.mageos-blog-card__hero {
    display: block;
    margin-bottom: 1rem;
}

.mageos-blog-card__hero img {
    aspect-ratio: 16 / 9;
    width: 100%;
    object-fit: cover;
    border-radius: 0.375rem;
    display: block;
}

.mageos-blog-card__title {
    font-size: var(--mageos-blog-h2);
    line-height: 1.25;
    margin: 0 0 0.5rem;
}

.mageos-blog-card__title a {
    text-decoration: none;
}

.mageos-blog-card__title a:hover {
    text-decoration: underline;
}

.mageos-blog-card__meta,
.mageos-blog-post__meta {
    color: var(--mageos-blog-meta);
    font-size: var(--mageos-blog-meta-size);
    margin: 0 0 1rem;
}

.mageos-blog-card__excerpt {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    margin: 0 0 1rem;
}

.mageos-blog-card__cta {
    font-size: var(--mageos-blog-meta-size);
    font-weight: 600;
}

/* ---------------- Post detail ---------------- */

.mageos-blog-post__hero {
    margin: 0 0 2rem;
}

.mageos-blog-post__hero img {
    aspect-ratio: 16 / 9;
    width: 100%;
    object-fit: cover;
    border-radius: 0.375rem;
    display: block;
}

.mageos-blog-post__title {
    font-size: var(--mageos-blog-h1);
    line-height: 1.15;
    margin: 0 0 0.5rem;
}

.mageos-blog-post__content {
    font-family: var(--mageos-blog-font-serif);
    font-size: 1.0625rem;
    line-height: 1.75;
}

.mageos-blog-post__content > * + * {
    margin-top: 1.25rem;
}

.mageos-blog-post__content h2 {
    font-family: var(--mageos-blog-font);
    font-size: 1.5rem;
    line-height: 1.25;
    margin-top: 2.5rem;
    margin-bottom: 1rem;
}

.mageos-blog-post__content h3 {
    font-family: var(--mageos-blog-font);
    font-size: 1.25rem;
    line-height: 1.3;
    margin-top: 2rem;
    margin-bottom: 0.75rem;
}

.mageos-blog-post__content blockquote {
    border-left: 3px solid var(--mageos-blog-border);
    padding-left: 1.25rem;
    color: var(--mageos-blog-meta);
    margin-inline: 0;
    font-style: italic;
}

.mageos-blog-post__content img {
    max-width: 100%;
    height: auto;
    border-radius: 0.375rem;
    margin-block: 2rem;
    display: block;
}

.mageos-blog-post__content pre {
    background: var(--mageos-blog-chip-bg);
    padding: 1rem;
    border-radius: 0.375rem;
    overflow-x: auto;
    font-size: 0.9375rem;
}

.mageos-blog-post__content code {
    background: var(--mageos-blog-chip-bg);
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    font-size: 0.9375rem;
}

.mageos-blog-post__content pre code {
    background: transparent;
    padding: 0;
}

/* ---------------- Post footer: tags + share + related ---------------- */

.mageos-blog-post__footer {
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 1px solid var(--mageos-blog-border);
    font-family: var(--mageos-blog-font);
}

.mageos-blog-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin: 0 0 2rem;
    padding: 0;
    list-style: none;
}

.mageos-blog-chip {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: var(--mageos-blog-chip-bg);
    border-radius: 1rem;
    font-size: var(--mageos-blog-meta-size);
    text-decoration: none;
}

.mageos-blog-chip:hover {
    background: rgba(0, 0, 0, 0.08);
}

.mageos-blog-share {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: baseline;
    font-size: var(--mageos-blog-meta-size);
    margin: 0 0 2rem;
}

.mageos-blog-share__label {
    color: var(--mageos-blog-meta);
}

.mageos-blog-related {
    margin-top: 2rem;
}

.mageos-blog-related__title {
    font-size: 1.25rem;
    margin: 0 0 1rem;
}

.mageos-blog-related__list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
}

@media (min-width: 640px) {
    .mageos-blog-related__list {
        grid-template-columns: repeat(2, 1fr);
    }
}

.mageos-blog-related__item a {
    text-decoration: none;
}

.mageos-blog-related__item a:hover .mageos-blog-related__item-title {
    text-decoration: underline;
}

.mageos-blog-related__item-thumb {
    aspect-ratio: 16 / 9;
    width: 100%;
    object-fit: cover;
    border-radius: 0.375rem;
    margin-bottom: 0.5rem;
}

.mageos-blog-related__item-title {
    font-size: 1rem;
    margin: 0;
}

/* ---------------- Pagination ---------------- */

.mageos-blog-pagination {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
    align-items: center;
    margin-top: 2rem;
    font-size: var(--mageos-blog-meta-size);
}

.mageos-blog-pagination a,
.mageos-blog-pagination span {
    padding: 0.375rem 0.75rem;
    border-radius: 0.25rem;
    text-decoration: none;
}

.mageos-blog-pagination a:hover {
    background: var(--mageos-blog-chip-bg);
}

.mageos-blog-pagination__current {
    background: var(--mageos-blog-chip-bg);
    font-weight: 600;
}

/* ---------------- Empty state ---------------- */

.mageos-blog-empty {
    color: var(--mageos-blog-meta);
    font-style: italic;
    text-align: center;
    padding: 3rem 0;
}

/* ---------------- Narrow-viewport adjustments ---------------- */

@media (max-width: 640px) {
    .mageos-blog-container { padding-inline: 1rem; padding-block: 1.5rem; }
    .mageos-blog-card__title { font-size: 1.25rem; }
    .mageos-blog-post__content { font-size: 1rem; }
}
```

**Step 2:** Verify the file exists and is served.

```
cd /Users/david/Herd/mage-os-typesense && warden env exec -T php-fpm bin/magento cache:flush
```

Run: `curl -skI "https://app.mage-os-typesense.test/static/version1776716540/frontend/Magento/luma/en_US/MageOS_Blog/css/blog.css" | head -1`

Note: The `version1776716540` segment will differ on your host. Grab the current one from `pub/static/deployed_version.txt`:

```
(cd /Users/david/Herd/mage-os-typesense && warden env exec -T php-fpm cat pub/static/deployed_version.txt)
```

Then re-curl with the correct prefix. Expected: `HTTP/2 200`.

**Step 3:** Commit.

```
git add view/frontend/web/css/blog.css
git commit -m "feat(frontend): blog.css — typography, layout primitives, cards, post detail"
```

---

## Task 3 — Listing template rewrites (index + context headers)

Five pages, one shared card template. Start with the index, then copy the header pattern to category/tag/author/search.

### Task 3.1 — Rewrite `view/frontend/templates/post/listing.phtml`

**Files:**
- Modify: `view/frontend/templates/post/listing.phtml`.
- Create: `view/frontend/templates/post/card.phtml`.
- Delete: `view/frontend/templates/post/item.phtml` (replaced by card.phtml).

**Step 1:** Read the current `listing.phtml` to confirm what we're replacing.

Run: `cat view/frontend/templates/post/listing.phtml`.

Note the inline-`h1` and the `createBlock(...)->setTemplate('MageOS_Blog::post/item.phtml')` pattern we're replacing.

**Step 2:** Replace `listing.phtml` with:

```php
<?php

declare(strict_types=1);

/** @var \MageOS\Blog\Block\Post\Listing $block */
/** @var \Magento\Framework\Escaper $escaper */

use MageOS\Blog\ViewModel\Post\Listing as PostListing;

/** @var PostListing $viewModel */
$viewModel = $block->getData('view_model');
$items = $viewModel->getItems();
?>
<div class="mageos-blog mageos-blog-container">
    <?php if ($items === []): ?>
        <p class="mageos-blog-empty">
            <?= $escaper->escapeHtml(__('No posts yet.')); ?>
        </p>
    <?php else: ?>
        <div class="mageos-blog-cards">
            <?php foreach ($items as $post): ?>
                <?= $block->getLayout()
                    ->createBlock(\MageOS\Blog\Block\Post\Listing::class)
                    ->setTemplate('MageOS_Blog::post/card.phtml')
                    ->setData('post', $post)
                    ->setData('view_model', $viewModel)
                    ->toHtml(); ?>
            <?php endforeach; ?>
        </div>

        <?php $totalPages = $viewModel->getTotalPages(); ?>
        <?php if ($totalPages > 1): ?>
            <nav class="mageos-blog-pagination" aria-label="<?= $escaper->escapeHtmlAttr(__('Pagination')); ?>">
                <?php $current = $viewModel->getCurrentPage(); ?>
                <?php if ($current > 1): ?>
                    <a rel="prev" href="<?= $escaper->escapeUrl($viewModel->getPageUrl($current - 1)); ?>">
                        <?= $escaper->escapeHtml(__('← Previous')); ?>
                    </a>
                <?php endif; ?>
                <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                    <?php if ($page === $current): ?>
                        <span class="mageos-blog-pagination__current"><?= $page; ?></span>
                    <?php else: ?>
                        <a href="<?= $escaper->escapeUrl($viewModel->getPageUrl($page)); ?>"><?= $page; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($current < $totalPages): ?>
                    <a rel="next" href="<?= $escaper->escapeUrl($viewModel->getPageUrl($current + 1)); ?>">
                        <?= $escaper->escapeHtml(__('Next →')); ?>
                    </a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>
```

**Step 3:** Create `view/frontend/templates/post/card.phtml` with:

```php
<?php

declare(strict_types=1);

/** @var \MageOS\Blog\Block\Post\Listing $block */
/** @var \Magento\Framework\Escaper $escaper */

use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\ViewModel\Post\Listing as PostListing;

/** @var PostListing $viewModel */
$viewModel = $block->getData('view_model');
/** @var PostInterface $post */
$post = $block->getData('post');

$postUrl = $viewModel->getPostUrl($post);
$heroPath = (string) $post->getFeaturedImage();
$heroAlt = (string) ($post->getFeaturedImageAlt() ?: $post->getTitle());
$excerpt = (string) $post->getShortContent();
$publishDate = $viewModel->getFormattedPublishDate($post);
$readingTime = $post->getReadingTime();
?>
<article class="mageos-blog-card">
    <?php if ($heroPath !== ''): ?>
        <a class="mageos-blog-card__hero"
           href="<?= $escaper->escapeUrl($postUrl); ?>"
           aria-hidden="true"
           tabindex="-1">
            <img loading="lazy"
                 src="<?= $escaper->escapeUrl($block->getViewFileUrl('images/pixel.gif')); ?>"
                 data-src="<?= $escaper->escapeUrl($this->helper(\Magento\Framework\View\Asset\Repository::class)
                    ? '' : ''); ?><?= $escaper->escapeUrl(
                        $block->getUrl('', ['_direct' => 'media/mageos_blog/' . ltrim($heroPath, '/')])
                    ); ?>"
                 alt="<?= $escaper->escapeHtmlAttr($heroAlt); ?>">
        </a>
    <?php endif; ?>
    <h2 class="mageos-blog-card__title">
        <a href="<?= $escaper->escapeUrl($postUrl); ?>"><?= $escaper->escapeHtml($post->getTitle()); ?></a>
    </h2>
    <p class="mageos-blog-card__meta">
        <?php if ($publishDate !== ''): ?>
            <time datetime="<?= $escaper->escapeHtmlAttr((string) $post->getPublishDate()); ?>">
                <?= $escaper->escapeHtml($publishDate); ?>
            </time>
        <?php endif; ?>
        <?php if ($readingTime !== null && $readingTime > 0): ?>
            <?= $escaper->escapeHtml(__(' · %1 min read', $readingTime)); ?>
        <?php endif; ?>
    </p>
    <?php if ($excerpt !== ''): ?>
        <p class="mageos-blog-card__excerpt"><?= $escaper->escapeHtml($excerpt); ?></p>
    <?php endif; ?>
    <p class="mageos-blog-card__cta">
        <a href="<?= $escaper->escapeUrl($postUrl); ?>"><?= $escaper->escapeHtml(__('Read more →')); ?></a>
    </p>
</article>
```

**IMPORTANT note on the hero image URL:** the card template above uses a noisy hack because Listing viewmodel doesn't expose a featured-image URL helper. Simplify by extending the ViewModel instead:

**Step 3a:** Read `ViewModel/Post/Listing.php`. Confirm it has no `getFeaturedImageUrl(PostInterface $post): ?string` method.

**Step 3b:** Add this method to `ViewModel/Post/Listing.php` (just before `fetchResults`):

```php
public function getFeaturedImageUrl(PostInterface $post): ?string
{
    $path = (string) $post->getFeaturedImage();
    if ($path === '') {
        return null;
    }
    $media = rtrim($this->urlBuilder->getBaseUrl(['_type' => \Magento\Framework\UrlInterface::URL_TYPE_MEDIA]), '/');

    return $media . '/mageos_blog/' . ltrim($path, '/');
}
```

Also add the author name and URL helpers we'll need:

```php
public function getAuthorName(PostInterface $post): ?string
{
    $authorId = $post->getAuthorId();
    if ($authorId === null || $authorId <= 0) {
        return null;
    }
    try {
        $author = $this->authorRepository->getById((int) $authorId);
    } catch (\Magento\Framework\Exception\NoSuchEntityException) {
        return null;
    }
    return (string) $author->getName();
}

public function getAuthorUrl(PostInterface $post): ?string
{
    $authorId = $post->getAuthorId();
    if ($authorId === null || $authorId <= 0) {
        return null;
    }
    try {
        $author = $this->authorRepository->getById((int) $authorId);
    } catch (\Magento\Framework\Exception\NoSuchEntityException) {
        return null;
    }
    $slug = (string) $author->getSlug();
    return $slug === '' ? null : $this->urlBuilder->getUrl('blog/author/' . $slug);
}
```

You'll need to add `MageOS\Blog\Api\AuthorRepositoryInterface $authorRepository` to the Listing constructor. The constructor currently injects `PostRepositoryInterface`, `SearchCriteriaBuilder`, `StoreManagerInterface`, `RequestInterface`, `UrlInterface`, `Config`. Add `AuthorRepositoryInterface` alongside and import at the top.

Also add the import: `use MageOS\Blog\Api\Data\PostInterface;` at the top of `ViewModel/Post/Listing.php` (already imported).

**Step 3c:** Replace `card.phtml` hero image block with the helper call. The full card template (clean version):

```php
<?php

declare(strict_types=1);

/** @var \MageOS\Blog\Block\Post\Listing $block */
/** @var \Magento\Framework\Escaper $escaper */

use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\ViewModel\Post\Listing as PostListing;

/** @var PostListing $viewModel */
$viewModel = $block->getData('view_model');
/** @var PostInterface $post */
$post = $block->getData('post');

$postUrl = $viewModel->getPostUrl($post);
$heroUrl = $viewModel->getFeaturedImageUrl($post);
$heroAlt = (string) ($post->getFeaturedImageAlt() ?: $post->getTitle());
$excerpt = (string) $post->getShortContent();
$publishDate = $viewModel->getFormattedPublishDate($post);
$readingTime = $post->getReadingTime();
$authorName = $viewModel->getAuthorName($post);
$authorUrl = $viewModel->getAuthorUrl($post);
?>
<article class="mageos-blog-card">
    <?php if ($heroUrl !== null): ?>
        <a class="mageos-blog-card__hero" href="<?= $escaper->escapeUrl($postUrl); ?>">
            <img loading="lazy"
                 src="<?= $escaper->escapeUrl($heroUrl); ?>"
                 alt="<?= $escaper->escapeHtmlAttr($heroAlt); ?>">
        </a>
    <?php endif; ?>
    <h2 class="mageos-blog-card__title">
        <a href="<?= $escaper->escapeUrl($postUrl); ?>"><?= $escaper->escapeHtml($post->getTitle()); ?></a>
    </h2>
    <p class="mageos-blog-card__meta">
        <?php if ($authorName !== null): ?>
            <?php if ($authorUrl !== null): ?>
                <a href="<?= $escaper->escapeUrl($authorUrl); ?>"><?= $escaper->escapeHtml($authorName); ?></a>
            <?php else: ?>
                <?= $escaper->escapeHtml($authorName); ?>
            <?php endif; ?>
            <?php if ($publishDate !== '' || ($readingTime !== null && $readingTime > 0)): ?>
                <?= $escaper->escapeHtml(__(' · ')); ?>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($publishDate !== ''): ?>
            <time datetime="<?= $escaper->escapeHtmlAttr((string) $post->getPublishDate()); ?>">
                <?= $escaper->escapeHtml($publishDate); ?>
            </time>
        <?php endif; ?>
        <?php if ($readingTime !== null && $readingTime > 0): ?>
            <?= $escaper->escapeHtml(__(' · %1 min read', $readingTime)); ?>
        <?php endif; ?>
    </p>
    <?php if ($excerpt !== ''): ?>
        <p class="mageos-blog-card__excerpt"><?= $escaper->escapeHtml($excerpt); ?></p>
    <?php endif; ?>
    <p class="mageos-blog-card__cta">
        <a href="<?= $escaper->escapeUrl($postUrl); ?>"><?= $escaper->escapeHtml(__('Read more →')); ?></a>
    </p>
</article>
```

**Step 4:** Delete the obsolete `view/frontend/templates/post/item.phtml`.

```
git rm view/frontend/templates/post/item.phtml
```

**Step 5:** Verify gates.

```
vendor/bin/phpstan analyse --memory-limit=1G --no-progress ViewModel/Post/Listing.php
vendor/bin/phpunit --testsuite unit
vendor/bin/phpcs --standard=phpcs.xml.dist --error-severity=1 --warning-severity=0 ViewModel/Post/Listing.php
```

All clean. 59 tests pass.

**Step 6:** Commit.

```
git add view/frontend/templates/post/listing.phtml \
        view/frontend/templates/post/card.phtml \
        ViewModel/Post/Listing.php \
        view/frontend/templates/post/item.phtml
git commit -m "feat(frontend): post listing — hero-on-top cards + pagination nav"
```

### Task 3.2 — Category / Tag / Author / Search context headers

**Files:**
- Modify: `view/frontend/templates/category/view.phtml`, `view/frontend/templates/tag/view.phtml`, `view/frontend/templates/author/view.phtml`, `view/frontend/templates/search/results.phtml`.

**Step 1:** Rewrite `view/frontend/templates/category/view.phtml` to render a context header + shared card loop. Replace the current posts-listing section with card reuse via `$block->getLayout()->createBlock(...)->setTemplate('MageOS_Blog::post/card.phtml')` just like `listing.phtml` — but with a Listing ViewModel wrapper so `getPostUrl/getFeaturedImageUrl/...` work. Since `Category\Detail` ViewModel already returns posts and has `getPostUrl`/`getFormattedPublishDate`, we can either:

  a. Add `getFeaturedImageUrl/getAuthorName/getAuthorUrl` to `Category\Detail` (and `Tag\Detail`, `Author\Detail`), OR
  b. Extract a shared trait / abstract ViewModel with the helpers.

Pick (b) — create `ViewModel/Post/CardHelpers.php` as a trait:

```php
<?php

declare(strict_types=1);

namespace MageOS\Blog\ViewModel\Post;

use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\Data\PostInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;

/**
 * Shared helpers used by listing + category/tag/author/search view models so
 * `post/card.phtml` can render against any of them with the same API surface.
 *
 * @phpstan-require-method \Magento\Framework\UrlInterface getUrlBuilder()
 * @phpstan-require-method \MageOS\Blog\Api\AuthorRepositoryInterface getAuthorRepository()
 */
trait CardHelpers
{
    public function getPostUrl(PostInterface $post): string
    {
        return $this->getUrlBuilder()->getUrl('blog/' . $post->getUrlKey());
    }

    public function getFormattedPublishDate(PostInterface $post): string
    {
        $date = $post->getPublishDate();
        if ($date === null || $date === '') {
            return '';
        }
        try {
            return (new \DateTimeImmutable($date))->format('F j, Y');
        } catch (\Throwable) {
            return '';
        }
    }

    public function getFeaturedImageUrl(PostInterface $post): ?string
    {
        $path = (string) $post->getFeaturedImage();
        if ($path === '') {
            return null;
        }
        $media = rtrim($this->getUrlBuilder()->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA]), '/');
        return $media . '/mageos_blog/' . ltrim($path, '/');
    }

    public function getAuthorName(PostInterface $post): ?string
    {
        $id = $post->getAuthorId();
        if ($id === null || $id <= 0) {
            return null;
        }
        try {
            $author = $this->getAuthorRepository()->getById((int) $id);
        } catch (NoSuchEntityException) {
            return null;
        }
        return (string) $author->getName();
    }

    public function getAuthorUrl(PostInterface $post): ?string
    {
        $id = $post->getAuthorId();
        if ($id === null || $id <= 0) {
            return null;
        }
        try {
            $author = $this->getAuthorRepository()->getById((int) $id);
        } catch (NoSuchEntityException) {
            return null;
        }
        $slug = (string) $author->getSlug();
        return $slug === '' ? null : $this->getUrlBuilder()->getUrl('blog/author/' . $slug);
    }
}
```

Then each consuming ViewModel uses the trait and exposes `getUrlBuilder()` + `getAuthorRepository()` as private getters returning the injected deps.

Alternative (even simpler for v1.0.2): skip the trait, duplicate the helper methods. Three duplications is acceptable per the "three similar lines is better than a premature abstraction" rule in the project's CLAUDE.md. **Pick this path — duplicate the 4 helper methods into each of Listing, Category\Detail, Tag\Detail, Author\Detail, Search\Results.**

**Step 2:** Add the 4 helper methods (`getFeaturedImageUrl`, `getAuthorName`, `getAuthorUrl`, and — if missing — `getPostUrl` / `getFormattedPublishDate` already exist) to `ViewModel/Category/Detail.php`, `ViewModel/Tag/Detail.php`, `ViewModel/Author/Detail.php`, `ViewModel/Search/Results.php`. Inject `AuthorRepositoryInterface` and `UrlInterface` into each constructor (UrlInterface is usually already injected — check first).

**Step 3:** Rewrite `view/frontend/templates/category/view.phtml`:

```php
<?php

declare(strict_types=1);

/** @var \MageOS\Blog\Block\Category\View $block */
/** @var \Magento\Framework\Escaper $escaper */

use MageOS\Blog\ViewModel\Category\Detail as CategoryDetail;

/** @var CategoryDetail $viewModel */
$viewModel = $block->getData('view_model');
$category = $viewModel->getCategory();
if ($category === null) {
    return;
}
$posts = $viewModel->getPosts();
$description = $viewModel->getDescription();
?>
<div class="mageos-blog mageos-blog-container">
    <header class="mageos-blog-context">
        <h1 class="mageos-blog-context__title"><?= $escaper->escapeHtml($viewModel->getTitle()); ?></h1>
        <?php if ($description !== null && $description !== ''): ?>
            <div class="mageos-blog-context__description"><?= /** @noEscape */ $description; ?></div>
        <?php endif; ?>
    </header>

    <?php if ($posts === []): ?>
        <p class="mageos-blog-empty"><?= $escaper->escapeHtml(__('No posts in this category yet.')); ?></p>
    <?php else: ?>
        <div class="mageos-blog-cards">
            <?php foreach ($posts as $post): ?>
                <?= $block->getLayout()
                    ->createBlock(\Magento\Framework\View\Element\Template::class)
                    ->setTemplate('MageOS_Blog::post/card.phtml')
                    ->setData('post', $post)
                    ->setData('view_model', $viewModel)
                    ->toHtml(); ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
```

**Step 4:** Rewrite `tag/view.phtml` identically but with `TagDetail` + `"No posts tagged here yet."`.

**Step 5:** Rewrite `author/view.phtml` identically but:
- Context header shows avatar (`$viewModel->getAvatarUrl()`) above title via `<img class="mageos-blog-context__avatar">` if non-null.
- Bio in the description position.
- Social links (email / twitter / linkedin / website) as a row under the bio, styled via `.mageos-blog-share` class.
- Empty-state label: `"No posts by this author yet."`.

**Step 6:** Rewrite `search/results.phtml` with the same context-header-plus-cards pattern. Context header shows `__('Search results for "%1"', $query)`. Empty state: `"No posts matched your search."`.

**Step 7:** Gates.

```
vendor/bin/phpstan analyse --memory-limit=1G --no-progress ViewModel
vendor/bin/phpunit --testsuite unit
vendor/bin/phpcs --standard=phpcs.xml.dist --error-severity=1 --warning-severity=0 ViewModel view/frontend/templates
```

All clean. 59 tests pass.

**Step 8:** Commit.

```
git add ViewModel view/frontend/templates
git commit -m "feat(frontend): category/tag/author/search context headers + shared cards"
```

---

## Task 4 — Post detail rewrite

**Files:**
- Modify: `view/frontend/templates/post/view.phtml`.
- Modify: `view/frontend/layout/blog_post_view.xml` (suppress page-title-wrapper).

**Step 1:** Suppress the Luma page-title block on post detail only, so the article's `<h1>` is the sole heading. In `view/frontend/layout/blog_post_view.xml`, replace the file with:

```xml
<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <update handle="blog_default"/>
    <body>
        <referenceBlock name="page.main.title" remove="true"/>
        <referenceContainer name="head.additional">
            <block class="MageOS\Blog\Block\Post\JsonLd"
                   name="mageos_blog.post.jsonld"
                   template="MageOS_Blog::post/jsonld.phtml">
                <arguments>
                    <argument name="view_model" xsi:type="object">MageOS\Blog\ViewModel\Post\Detail</argument>
                </arguments>
            </block>
            <block class="MageOS\Blog\Block\Post\HeadMeta"
                   name="mageos_blog.post.head_meta"
                   template="MageOS_Blog::post/head-meta.phtml">
                <arguments>
                    <argument name="view_model" xsi:type="object">MageOS\Blog\ViewModel\Post\Detail</argument>
                </arguments>
            </block>
        </referenceContainer>
        <referenceContainer name="content">
            <block class="MageOS\Blog\Block\Post\View"
                   name="mageos_blog.post.view"
                   template="MageOS_Blog::post/view.phtml">
                <arguments>
                    <argument name="view_model" xsi:type="object">MageOS\Blog\ViewModel\Post\Detail</argument>
                </arguments>
            </block>
        </referenceContainer>
    </body>
</page>
```

**Step 2:** Check for any helper the Detail ViewModel is missing. It needs `getFeaturedImageUrl`, `getAuthorName`, `getAuthorUrl`, `getTagList()` (array of `['title' => x, 'url' => y]`), and access to `getPost()`. Add the helpers as in Task 3. Also add:

```php
/**
 * @return array<int, array{title: string, url: string}>
 */
public function getTags(): array
{
    $post = $this->getPost();
    if ($post === null) {
        return [];
    }
    $tagIds = $post->getTagIds();
    if ($tagIds === []) {
        return [];
    }
    $criteria = $this->searchCriteriaBuilder
        ->addFilter('tag_id', $tagIds, 'in')
        ->create();
    $tags = $this->tagRepository->getList($criteria)->getItems();
    $out = [];
    foreach ($tags as $tag) {
        $out[] = [
            'title' => (string) $tag->getTitle(),
            'url' => $this->urlBuilder->getUrl('blog/tag/' . $tag->getUrlKey()),
        ];
    }
    return $out;
}

/**
 * @return \MageOS\Blog\Api\Data\PostInterface[]
 */
public function getRelatedPosts(int $limit = 3): array
{
    $post = $this->getPost();
    if ($post === null) {
        return [];
    }
    return $this->relatedPostsProvider->forPost($post, $limit);
}
```

Inject `TagRepositoryInterface` + `SearchCriteriaBuilder` + `RelatedPostsProviderInterface` into the constructor if not already present.

**Step 3:** Rewrite `view/frontend/templates/post/view.phtml`:

```php
<?php

declare(strict_types=1);

/** @var \MageOS\Blog\Block\Post\View $block */
/** @var \Magento\Framework\Escaper $escaper */

use MageOS\Blog\ViewModel\Post\Detail as PostDetail;

/** @var PostDetail $viewModel */
$viewModel = $block->getData('view_model');
$post = $viewModel->getPost();
if ($post === null) {
    return;
}
$heroUrl = $viewModel->getFeaturedImageUrl($post);
$heroAlt = (string) ($post->getFeaturedImageAlt() ?: $post->getTitle());
$publishDate = $viewModel->getFormattedPublishDate($post);
$readingTime = $post->getReadingTime();
$authorName = $viewModel->getAuthorName($post);
$authorUrl = $viewModel->getAuthorUrl($post);
$tags = $viewModel->getTags();
$relatedPosts = $viewModel->getRelatedPosts();
?>
<article class="mageos-blog mageos-blog-container mageos-blog-post">
    <?php if ($heroUrl !== null): ?>
        <figure class="mageos-blog-post__hero">
            <img src="<?= $escaper->escapeUrl($heroUrl); ?>"
                 alt="<?= $escaper->escapeHtmlAttr($heroAlt); ?>">
        </figure>
    <?php endif; ?>

    <h1 class="mageos-blog-post__title"><?= $escaper->escapeHtml($post->getTitle()); ?></h1>

    <p class="mageos-blog-post__meta">
        <?php if ($authorName !== null): ?>
            <?= $escaper->escapeHtml(__('by ')); ?>
            <?php if ($authorUrl !== null): ?>
                <a href="<?= $escaper->escapeUrl($authorUrl); ?>"><?= $escaper->escapeHtml($authorName); ?></a>
            <?php else: ?>
                <?= $escaper->escapeHtml($authorName); ?>
            <?php endif; ?>
            <?= $escaper->escapeHtml(__(' · ')); ?>
        <?php endif; ?>
        <?php if ($publishDate !== ''): ?>
            <time datetime="<?= $escaper->escapeHtmlAttr((string) $post->getPublishDate()); ?>">
                <?= $escaper->escapeHtml($publishDate); ?>
            </time>
        <?php endif; ?>
        <?php if ($readingTime !== null && $readingTime > 0): ?>
            <?= $escaper->escapeHtml(__(' · %1 min read', $readingTime)); ?>
        <?php endif; ?>
    </p>

    <div class="mageos-blog-post__content">
        <?= /** @noEscape */ (string) $post->getContent(); ?>
    </div>

    <footer class="mageos-blog-post__footer">
        <?php if ($tags !== []): ?>
            <ul class="mageos-blog-tags">
                <?php foreach ($tags as $tag): ?>
                    <li>
                        <a class="mageos-blog-chip"
                           href="<?= $escaper->escapeUrl($tag['url']); ?>">
                            <?= $escaper->escapeHtml($tag['title']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?= $block->getChildHtml('mageos_blog.post.share'); ?>

        <?php if ($relatedPosts !== []): ?>
            <section class="mageos-blog-related">
                <h2 class="mageos-blog-related__title"><?= $escaper->escapeHtml(__('Related posts')); ?></h2>
                <ul class="mageos-blog-related__list">
                    <?php foreach ($relatedPosts as $related): ?>
                        <?php $relUrl = $viewModel->getPostUrl($related); ?>
                        <?php $relHero = $viewModel->getFeaturedImageUrl($related); ?>
                        <li class="mageos-blog-related__item">
                            <a href="<?= $escaper->escapeUrl($relUrl); ?>">
                                <?php if ($relHero !== null): ?>
                                    <img class="mageos-blog-related__item-thumb"
                                         loading="lazy"
                                         src="<?= $escaper->escapeUrl($relHero); ?>"
                                         alt="">
                                <?php endif; ?>
                                <h3 class="mageos-blog-related__item-title">
                                    <?= $escaper->escapeHtml($related->getTitle()); ?>
                                </h3>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
    </footer>
</article>
```

**Step 4:** Wire the share block into the layout XML via `<referenceContainer>` on the post-view block. If there's already a SocialShare block configured, ensure it renders under `mageos_blog.post.share` with the styled wrapper. If not, skip for v1.0.2 and remove the `getChildHtml('mageos_blog.post.share')` call.

**Step 5:** Gates.

```
vendor/bin/phpstan analyse --memory-limit=1G --no-progress
vendor/bin/phpunit --testsuite unit
vendor/bin/phpcs --standard=phpcs.xml.dist --error-severity=1 --warning-severity=0
```

Clean, 59 tests.

**Step 6:** Smoke — live check.

```
cd /Users/david/Herd/mage-os-typesense && warden env exec -T php-fpm bin/magento cache:flush && warden env exec -T varnish varnishadm "ban req.url ~ ."
```

Open `https://app.mage-os-typesense.test/blog/hello-world`. Verify:
- No double `<h1>` (the article's is the only `<h1>`).
- No wishlist / compare / last-added sidebar.
- Tag chips render below content.
- Related posts section renders if there are any.

**Step 7:** Commit.

```
git add view/frontend/templates/post/view.phtml \
        view/frontend/layout/blog_post_view.xml \
        ViewModel/Post/Detail.php
git commit -m "feat(frontend): post detail — hero, byline, typography, tag chips, related posts"
```

---

## Task 5 — Final verification + merge

**Step 1:** Full gate run from module root.

```
vendor/bin/phpstan analyse --memory-limit=1G --no-progress
vendor/bin/phpunit --testsuite unit
vendor/bin/phpcs --standard=phpcs.xml.dist --error-severity=1 --warning-severity=0
vendor/bin/php-cs-fixer fix --dry-run --diff --allow-risky=yes
```

All four gates clean. 59 tests pass.

**Step 2:** Playwright visual smoke. Take full-page screenshots of:

- `/blog/`
- `/blog/hello-world`
- `/blog/category/news`
- `/blog/tag/magento`
- `/blog/author/jane-doe`
- `/blog/search?q=hyva`

Eyeball each:
- No sidebar.
- No duplicate `<h1>`.
- Hero images render when present.
- Byline meta row visible.
- Pagination links clickable.
- Cards stack cleanly with `2rem` gaps.

**Step 3:** Push.

```
git push origin main
```

**Step 4:** Update memory. Append to `/Users/david/.claude/projects/-Users-david-Herd-module-blog/memory/phase-5-handoff.md` a short note under the v1.0.1 section:

> v1.0.2 (2026-04-21) — blog frontend redesign. 1column layout, no Luma sidebar bleed, single `blog.css` (~250 lines), hero-on-top card listing, typography-first post detail with tag chips + related-posts strip.

Done.
