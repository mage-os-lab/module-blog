# MageOS_Blog v1 Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Rewrite `MageOS_Blog` from scratch — four entities (Post / Category / Tag / Author), Luma + Hyvä storefront, full GraphQL, `Magento_Search` integration, 6 widgets, native social share, RSS, cron-driven scheduled publishing, and SEO polish (OG / Twitter / JSON-LD / sitemap). Zero code reuse from the existing forked codebase.

**Architecture:** Standard Magento 2 module using `url_rewrite` for URL resolution (no custom Router), thin Blocks paired with ViewModels, service-contract layer in `Api/`, repositories persist via ResourceModels, `Magento_Search` (OpenSearch) backs the blog search, GraphQL resolvers delegate to repositories with admin-auth via resolver-side context check. Hyvä bundling via a `TemplateEngine\Php` plugin that transparently resolves `MageOS_Blog::hyva/...` variants when a Hyvä theme is active.

**Tech Stack:** PHP 8.2+, Magento 2.4.6+ / Mage-OS 1.1+, MySQL 8 / MariaDB 10.6, OpenSearch 2.x (or Elasticsearch 8.x), PHPUnit 10, Infection, PHPStan 1.11+ with Magento-aware rules, PHP-CS-Fixer, `magento/magento-coding-standard`, GitHub Actions via `graycoreio/github-actions-magento2`.

**Design reference:** `docs/plans/2026-04-19-mageos-blog-v1-rewrite-design.md` — read before starting any phase.

**License:** OSL-3.0. Every new PHP file gets `declare(strict_types=1);` at the top. No copyright headers (Magento convention).

**Branch:** Work on `feat/v1-rewrite` (already created). Never push to `main` until `v1.0.0`.

---

## How to use this plan

**Depth differential by phase.** This plan decays; don't pretend otherwise.

- **Phase 1 is fully bite-sized** — read-write-test-commit granularity. Execute it as written.
- **Phases 2–5 are task-level** — scope, file list, patterns, acceptance criteria. Before each phase starts, refine it into bite-sized tasks using the same TDD cadence (test first, minimal impl, verify, commit). Re-run the writing-plans skill if helpful.
- **Never batch commits across tasks.** One task = one commit (or a short chain if strict TDD red/green steps demand it).
- **Every task has tests.** If a task has no tests, it shouldn't exist.

**Commit message convention:** Conventional Commits (`feat:`, `fix:`, `test:`, `refactor:`, `chore:`, `docs:`). Prefix phase number in bodies when useful (`Phase 1 / Task 1.5`).

**Running tests:**

```bash
# unit tests (inside module)
vendor/bin/phpunit -c phpunit.xml.dist Test/Unit

# integration tests (require host Magento)
cd <magento-root> && vendor/bin/phpunit -c dev/tests/integration/phpunit.xml.dist \
    --filter 'MageOS\\Blog' dev/tests/integration/testsuite

# phpstan
vendor/bin/phpstan analyse --memory-limit=1G

# cs-fixer
vendor/bin/php-cs-fixer fix --dry-run --diff

# phpcs
vendor/bin/phpcs --standard=Magento2 Api Block Controller Cron Model Plugin Ui ViewModel

# infection
vendor/bin/infection --min-msi=75 --threads=4
```

---

## Phase 0 — Clear the decks

### Task 0.1: Delete forked code on `feat/v1-rewrite`

**Why:** The existing PHP/XML is Magefan-derived with no attribution. Legal risk. We keep only `docs/`, `CLAUDE.md`, `LICENSE` (once added), and `.git/`.

**Step 1:** Verify you're on the right branch.

```bash
git branch --show-current
# expected: feat/v1-rewrite
```

**Step 2:** Delete everything except keepers.

```bash
find . -mindepth 1 -maxdepth 1 \
    ! -name '.git' ! -name 'docs' ! -name 'CLAUDE.md' ! -name '.' \
    -exec rm -rf {} +
```

**Step 3:** Verify.

```bash
ls -la
# expected: .git/  docs/  CLAUDE.md
```

**Step 4:** Commit.

```bash
git add -A
git commit -m "chore: remove forked code ahead of v1 rewrite

Removes all PHP/XML from the v0 fork (traced to Magefan Blog in the
v1-rewrite design doc). Clean slate for the rewrite."
```

---

## Phase 1 — Foundation (~1 week, fully bite-sized)

**Deliverable:** `v0.1.0` tag — CRUD via repository API, no UI. Green unit + integration tests on repositories, slug generation, reserved-slug validation. CI pipeline running on PRs.

### Task 1.1: composer.json + registration.php + LICENSE

**Files:**
- Create: `composer.json`
- Create: `registration.php`
- Create: `LICENSE`

**Step 1:** Write `composer.json`:

```json
{
    "name": "mageos/module-blog",
    "description": "Blog module for Mage-OS / Magento 2 — posts, categories, tags, authors, SEO, GraphQL.",
    "type": "magento2-module",
    "license": "OSL-3.0",
    "require": {
        "php": "^8.2",
        "magento/framework": "^103.0.7",
        "magento/module-store": "*",
        "magento/module-backend": "*",
        "magento/module-cms": "*",
        "magento/module-catalog": "*",
        "magento/module-customer": "*",
        "magento/module-ui": "*",
        "magento/module-url-rewrite": "*",
        "magento/module-search": "*",
        "magento/module-sitemap": "*",
        "magento/module-widget": "*",
        "magento/module-graph-ql": "*",
        "magento/module-media-storage": "*"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.5",
        "phpstan/phpstan": "^1.11",
        "bitexpert/phpstan-magento": "^0.40",
        "friendsofphp/php-cs-fixer": "^3.50",
        "magento/magento-coding-standard": "^32",
        "infection/infection": "^0.29"
    },
    "autoload": {
        "files": ["registration.php"],
        "psr-4": {
            "MageOS\\Blog\\": ""
        }
    },
    "autoload-dev": {
        "psr-4": {
            "MageOS\\Blog\\Test\\": "Test/"
        }
    }
}
```

**Step 2:** Write `registration.php`:

```php
<?php
declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'MageOS_Blog', __DIR__);
```

**Step 3:** Download OSL-3.0 license text into `LICENSE` (verbatim from https://opensource.org/license/osl-3-0-php).

**Step 4:** Validate composer.

```bash
composer validate
# expected: ./composer.json is valid
```

**Step 5:** Commit.

```bash
git add composer.json registration.php LICENSE
git commit -m "feat: composer manifest, registration, LICENSE (OSL-3.0)"
```

### Task 1.2: etc/module.xml

**Files:**
- Create: `etc/module.xml`

**Step 1:** Write:

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:Module/etc/module.xsd">
    <module name="MageOS_Blog">
        <sequence>
            <module name="Magento_UrlRewrite"/>
            <module name="Magento_Cms"/>
            <module name="Magento_Catalog"/>
            <module name="Magento_Search"/>
            <module name="Magento_Sitemap"/>
            <module name="Magento_Widget"/>
        </sequence>
    </module>
</config>
```

**Step 2:** Commit.

```bash
git add etc/module.xml
git commit -m "feat: module declaration with dependency sequence"
```

### Task 1.3: CI scaffolding (skeleton workflow + tool configs)

**Why write these before any tests:** lets us fail CI loudly from Task 1.4 onwards if tests don't run — prevents "works on my machine" drift.

**Files:**
- Create: `phpunit.xml.dist`
- Create: `phpstan.neon`
- Create: `.php-cs-fixer.dist.php`
- Create: `phpcs.xml.dist`
- Create: `infection.json5`
- Create: `.github/workflows/ci.yml`
- Create: `.gitignore`

**Step 1:** `phpunit.xml.dist`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/10.5/phpunit.xsd"
         colors="true"
         bootstrap="vendor/autoload.php"
         cacheDirectory=".phpunit.cache"
         executionOrder="random"
         failOnWarning="true"
         failOnRisky="true">
    <testsuites>
        <testsuite name="unit">
            <directory>Test/Unit</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>Api</directory>
            <directory>Block</directory>
            <directory>Controller</directory>
            <directory>Cron</directory>
            <directory>Model</directory>
            <directory>Plugin</directory>
            <directory>Ui</directory>
            <directory>ViewModel</directory>
        </include>
    </source>
</phpunit>
```

**Step 2:** `phpstan.neon`:

```neon
includes:
    - vendor/bitexpert/phpstan-magento/extension.neon
parameters:
    level: 8
    paths:
        - Api
        - Block
        - Controller
        - Cron
        - Model
        - Plugin
        - Ui
        - ViewModel
    treatPhpDocTypesAsCertain: false
```

**Step 3:** `.php-cs-fixer.dist.php`:

```php
<?php
return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        '@PHP82Migration' => true,
        'declare_strict_types' => true,
        'native_function_invocation' => ['include' => ['@compiler_optimized']],
        'ordered_imports' => true,
        'no_unused_imports' => true,
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_order' => true,
        'return_type_declaration' => ['space_before' => 'none'],
    ])
    ->setFinder(
        (new PhpCsFixer\Finder())
            ->in(['Api', 'Block', 'Controller', 'Cron', 'Model', 'Plugin', 'Ui', 'ViewModel', 'Test'])
            ->notPath('#/registration\.php$#')
    );
```

**Step 4:** `phpcs.xml.dist`:

```xml
<?xml version="1.0"?>
<ruleset name="MageOS_Blog">
    <rule ref="Magento2"/>
    <file>Api</file>
    <file>Block</file>
    <file>Controller</file>
    <file>Cron</file>
    <file>Model</file>
    <file>Plugin</file>
    <file>Ui</file>
    <file>ViewModel</file>
</ruleset>
```

**Step 5:** `infection.json5`:

```json5
{
    "$schema": "vendor/infection/infection/resources/schema.json",
    "source": {
        "directories": ["Model", "ViewModel", "Cron"]
    },
    "mutators": { "@default": true },
    "phpUnit": { "configDir": "." },
    "minMsi": 75,
    "minCoveredMsi": 80,
    "logs": {
        "text": "infection.log",
        "github": true
    }
}
```

**Step 6:** `.github/workflows/ci.yml`:

```yaml
name: CI
on:
  push:
    branches: [main]
  pull_request:

jobs:
  unit:
    name: Unit tests
    runs-on: ubuntu-22.04
    strategy:
      matrix:
        php: ['8.2', '8.3']
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          coverage: pcov
      - run: composer install --no-interaction --prefer-dist
      - run: vendor/bin/phpunit --testsuite unit

  static:
    name: PHPStan
    runs-on: ubuntu-22.04
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.3' }
      - run: composer install --no-interaction --prefer-dist
      - run: vendor/bin/phpstan analyse --memory-limit=1G --no-progress

  cs:
    name: Code style
    runs-on: ubuntu-22.04
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.3' }
      - run: composer install --no-interaction --prefer-dist
      - run: vendor/bin/php-cs-fixer fix --dry-run --diff
      - run: vendor/bin/phpcs --standard=phpcs.xml.dist

  integration:
    name: Integration tests
    runs-on: ubuntu-22.04
    steps:
      - uses: actions/checkout@v4
      - uses: graycoreio/github-actions-magento2/integration-test@main
        with:
          php_version: '8.2'
          magento_version: '2.4.6-p7'
          module_path: MageOS/Blog
          test_filter: 'MageOS\\Blog'

  infection:
    name: Mutation testing
    if: github.event_name == 'pull_request'
    runs-on: ubuntu-22.04
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.3', coverage: pcov }
      - run: composer install --no-interaction --prefer-dist
      - run: vendor/bin/infection --min-msi=75 --threads=4 --no-progress
```

**Step 7:** `.gitignore`:

```
vendor/
composer.lock
.phpunit.cache/
.php-cs-fixer.cache
infection.log
*.log
```

**Step 8:** Commit.

```bash
git add phpunit.xml.dist phpstan.neon .php-cs-fixer.dist.php phpcs.xml.dist \
        infection.json5 .github/workflows/ci.yml .gitignore
git commit -m "chore: CI scaffolding — phpunit, phpstan L8, cs-fixer, phpcs, infection, GH Actions"
```

### Task 1.4: db_schema.xml — entity tables

**Files:**
- Create: `etc/db_schema.xml`

**Step 1:** Write the four entity tables and pivots. Start with the entities:

```xml
<?xml version="1.0"?>
<schema xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:Setup/Declaration/Schema/etc/schema.xsd">

    <table name="mageos_blog_post" resource="default" engine="innodb" comment="Blog Post">
        <column xsi:type="int" name="post_id" nullable="false" identity="true" unsigned="true"/>
        <column xsi:type="varchar" name="url_key" nullable="false" length="255"/>
        <column xsi:type="varchar" name="title" nullable="false" length="255"/>
        <column xsi:type="text" name="content" nullable="true"/>
        <column xsi:type="text" name="short_content" nullable="true"/>
        <column xsi:type="varchar" name="featured_image" nullable="true" length="255"/>
        <column xsi:type="varchar" name="featured_image_alt" nullable="true" length="255"/>
        <column xsi:type="int" name="author_id" nullable="true" unsigned="true"/>
        <column xsi:type="timestamp" name="publish_date" nullable="true"/>
        <column xsi:type="smallint" name="reading_time" nullable="true" unsigned="true"/>
        <column xsi:type="int" name="views_count" nullable="false" default="0" unsigned="true"/>
        <column xsi:type="smallint" name="status" nullable="false" default="0" unsigned="true"
                comment="0=Draft 1=Scheduled 2=Published 3=Archived"/>
        <column xsi:type="varchar" name="meta_title" nullable="true" length="255"/>
        <column xsi:type="text" name="meta_description" nullable="true"/>
        <column xsi:type="varchar" name="meta_keywords" nullable="true" length="255"/>
        <column xsi:type="varchar" name="meta_robots" nullable="true" length="64"/>
        <column xsi:type="varchar" name="og_title" nullable="true" length="255"/>
        <column xsi:type="text" name="og_description" nullable="true"/>
        <column xsi:type="varchar" name="og_image" nullable="true" length="255"/>
        <column xsi:type="varchar" name="og_type" nullable="true" length="64"/>
        <column xsi:type="timestamp" name="creation_time" nullable="false" default="CURRENT_TIMESTAMP"/>
        <column xsi:type="timestamp" name="update_time" nullable="false" on_update="true"
                default="CURRENT_TIMESTAMP"/>
        <constraint xsi:type="primary" referenceId="PRIMARY">
            <column name="post_id"/>
        </constraint>
        <constraint xsi:type="foreign" referenceId="MAGEOS_BLOG_POST_AUTHOR_ID"
                    table="mageos_blog_post" column="author_id"
                    referenceTable="mageos_blog_author" referenceColumn="author_id"
                    onDelete="SET NULL"/>
        <index referenceId="MAGEOS_BLOG_POST_STATUS" indexType="btree">
            <column name="status"/>
        </index>
        <index referenceId="MAGEOS_BLOG_POST_PUBLISH_DATE" indexType="btree">
            <column name="publish_date"/>
        </index>
        <index referenceId="MAGEOS_BLOG_POST_VIEWS_COUNT" indexType="btree">
            <column name="views_count"/>
        </index>
        <index referenceId="MAGEOS_BLOG_POST_AUTHOR_ID_IDX" indexType="btree">
            <column name="author_id"/>
        </index>
        <index referenceId="MAGEOS_BLOG_POST_FULLTEXT" indexType="fulltext">
            <column name="title"/>
            <column name="short_content"/>
            <column name="content"/>
            <column name="meta_description"/>
        </index>
    </table>

    <table name="mageos_blog_category" resource="default" engine="innodb" comment="Blog Category">
        <column xsi:type="int" name="category_id" nullable="false" identity="true" unsigned="true"/>
        <column xsi:type="varchar" name="url_key" nullable="false" length="255"/>
        <column xsi:type="varchar" name="title" nullable="false" length="255"/>
        <column xsi:type="text" name="description" nullable="true"/>
        <column xsi:type="int" name="parent_id" nullable="true" unsigned="true"/>
        <column xsi:type="smallint" name="position" nullable="false" default="0"/>
        <column xsi:type="varchar" name="meta_title" nullable="true" length="255"/>
        <column xsi:type="text" name="meta_description" nullable="true"/>
        <column xsi:type="varchar" name="meta_keywords" nullable="true" length="255"/>
        <column xsi:type="smallint" name="include_in_menu" nullable="false" default="0" unsigned="true"/>
        <column xsi:type="smallint" name="include_in_sidebar" nullable="false" default="1" unsigned="true"/>
        <column xsi:type="smallint" name="is_active" nullable="false" default="1" unsigned="true"/>
        <column xsi:type="timestamp" name="creation_time" nullable="false" default="CURRENT_TIMESTAMP"/>
        <column xsi:type="timestamp" name="update_time" nullable="false" on_update="true"
                default="CURRENT_TIMESTAMP"/>
        <constraint xsi:type="primary" referenceId="PRIMARY">
            <column name="category_id"/>
        </constraint>
        <constraint xsi:type="foreign" referenceId="MAGEOS_BLOG_CATEGORY_PARENT_ID"
                    table="mageos_blog_category" column="parent_id"
                    referenceTable="mageos_blog_category" referenceColumn="category_id"
                    onDelete="SET NULL"/>
        <index referenceId="MAGEOS_BLOG_CATEGORY_PARENT_ID_IDX" indexType="btree">
            <column name="parent_id"/>
        </index>
        <index referenceId="MAGEOS_BLOG_CATEGORY_IS_ACTIVE" indexType="btree">
            <column name="is_active"/>
        </index>
    </table>

    <table name="mageos_blog_tag" resource="default" engine="innodb" comment="Blog Tag">
        <column xsi:type="int" name="tag_id" nullable="false" identity="true" unsigned="true"/>
        <column xsi:type="varchar" name="url_key" nullable="false" length="255"/>
        <column xsi:type="varchar" name="title" nullable="false" length="255"/>
        <column xsi:type="text" name="description" nullable="true"/>
        <column xsi:type="varchar" name="meta_title" nullable="true" length="255"/>
        <column xsi:type="text" name="meta_description" nullable="true"/>
        <column xsi:type="smallint" name="is_active" nullable="false" default="1" unsigned="true"/>
        <column xsi:type="timestamp" name="creation_time" nullable="false" default="CURRENT_TIMESTAMP"/>
        <column xsi:type="timestamp" name="update_time" nullable="false" on_update="true"
                default="CURRENT_TIMESTAMP"/>
        <constraint xsi:type="primary" referenceId="PRIMARY">
            <column name="tag_id"/>
        </constraint>
        <index referenceId="MAGEOS_BLOG_TAG_IS_ACTIVE" indexType="btree">
            <column name="is_active"/>
        </index>
    </table>

    <table name="mageos_blog_author" resource="default" engine="innodb" comment="Blog Author">
        <column xsi:type="int" name="author_id" nullable="false" identity="true" unsigned="true"/>
        <column xsi:type="varchar" name="slug" nullable="false" length="255"/>
        <column xsi:type="varchar" name="name" nullable="false" length="255"/>
        <column xsi:type="text" name="bio" nullable="true"/>
        <column xsi:type="varchar" name="avatar" nullable="true" length="255"/>
        <column xsi:type="varchar" name="email" nullable="true" length="255"/>
        <column xsi:type="varchar" name="twitter" nullable="true" length="255"/>
        <column xsi:type="varchar" name="linkedin" nullable="true" length="255"/>
        <column xsi:type="varchar" name="website" nullable="true" length="255"/>
        <column xsi:type="smallint" name="is_active" nullable="false" default="1" unsigned="true"/>
        <column xsi:type="timestamp" name="creation_time" nullable="false" default="CURRENT_TIMESTAMP"/>
        <column xsi:type="timestamp" name="update_time" nullable="false" on_update="true"
                default="CURRENT_TIMESTAMP"/>
        <constraint xsi:type="primary" referenceId="PRIMARY">
            <column name="author_id"/>
        </constraint>
        <index referenceId="MAGEOS_BLOG_AUTHOR_IS_ACTIVE" indexType="btree">
            <column name="is_active"/>
        </index>
    </table>
</schema>
```

**Step 2:** Commit.

```bash
git add etc/db_schema.xml
git commit -m "feat(schema): post, category, tag, author entity tables"
```

### Task 1.5: db_schema.xml — pivots

**Files:**
- Modify: `etc/db_schema.xml` (append pivots before `</schema>`)

**Step 1:** Append these tables:

```xml
    <table name="mageos_blog_post_store" resource="default" engine="innodb" comment="Post ↔ Store">
        <column xsi:type="int" name="post_id" nullable="false" unsigned="true"/>
        <column xsi:type="smallint" name="store_id" nullable="false" unsigned="true"/>
        <constraint xsi:type="primary" referenceId="PRIMARY">
            <column name="post_id"/><column name="store_id"/>
        </constraint>
        <constraint xsi:type="foreign" referenceId="MAGEOS_BLOG_POST_STORE_POST_ID"
                    table="mageos_blog_post_store" column="post_id"
                    referenceTable="mageos_blog_post" referenceColumn="post_id" onDelete="CASCADE"/>
        <constraint xsi:type="foreign" referenceId="MAGEOS_BLOG_POST_STORE_STORE_ID"
                    table="mageos_blog_post_store" column="store_id"
                    referenceTable="store" referenceColumn="store_id" onDelete="CASCADE"/>
        <index referenceId="MAGEOS_BLOG_POST_STORE_STORE_ID_IDX" indexType="btree">
            <column name="store_id"/>
        </index>
    </table>

    <!-- repeat same shape for: mageos_blog_category_store, mageos_blog_tag_store -->

    <table name="mageos_blog_post_category" resource="default" engine="innodb" comment="Post ↔ Category">
        <column xsi:type="int" name="post_id" nullable="false" unsigned="true"/>
        <column xsi:type="int" name="category_id" nullable="false" unsigned="true"/>
        <constraint xsi:type="primary" referenceId="PRIMARY">
            <column name="post_id"/><column name="category_id"/>
        </constraint>
        <constraint xsi:type="foreign" referenceId="MAGEOS_BLOG_POST_CATEGORY_POST_ID"
                    table="mageos_blog_post_category" column="post_id"
                    referenceTable="mageos_blog_post" referenceColumn="post_id" onDelete="CASCADE"/>
        <constraint xsi:type="foreign" referenceId="MAGEOS_BLOG_POST_CATEGORY_CATEGORY_ID"
                    table="mageos_blog_post_category" column="category_id"
                    referenceTable="mageos_blog_category" referenceColumn="category_id" onDelete="CASCADE"/>
        <index referenceId="MAGEOS_BLOG_POST_CATEGORY_CATEGORY_ID_IDX" indexType="btree">
            <column name="category_id"/>
        </index>
    </table>

    <!-- repeat same shape for: mageos_blog_post_tag -->

    <table name="mageos_blog_post_related_product" resource="default" engine="innodb">
        <column xsi:type="int" name="post_id" nullable="false" unsigned="true"/>
        <column xsi:type="int" name="product_id" nullable="false" unsigned="true"/>
        <column xsi:type="smallint" name="position" nullable="false" default="0"/>
        <constraint xsi:type="primary" referenceId="PRIMARY">
            <column name="post_id"/><column name="product_id"/>
        </constraint>
        <constraint xsi:type="foreign" referenceId="MAGEOS_BLOG_POST_RP_POST_ID"
                    table="mageos_blog_post_related_product" column="post_id"
                    referenceTable="mageos_blog_post" referenceColumn="post_id" onDelete="CASCADE"/>
        <constraint xsi:type="foreign" referenceId="MAGEOS_BLOG_POST_RP_PRODUCT_ID"
                    table="mageos_blog_post_related_product" column="product_id"
                    referenceTable="catalog_product_entity" referenceColumn="entity_id"
                    onDelete="CASCADE"/>
    </table>

    <table name="mageos_blog_post_related_post" resource="default" engine="innodb">
        <column xsi:type="int" name="post_id" nullable="false" unsigned="true"/>
        <column xsi:type="int" name="related_post_id" nullable="false" unsigned="true"/>
        <column xsi:type="smallint" name="position" nullable="false" default="0"/>
        <constraint xsi:type="primary" referenceId="PRIMARY">
            <column name="post_id"/><column name="related_post_id"/>
        </constraint>
        <constraint xsi:type="foreign" referenceId="MAGEOS_BLOG_POST_RELATED_POST_ID"
                    table="mageos_blog_post_related_post" column="post_id"
                    referenceTable="mageos_blog_post" referenceColumn="post_id" onDelete="CASCADE"/>
        <constraint xsi:type="foreign" referenceId="MAGEOS_BLOG_POST_RELATED_RELATED_POST_ID"
                    table="mageos_blog_post_related_post" column="related_post_id"
                    referenceTable="mageos_blog_post" referenceColumn="post_id" onDelete="CASCADE"/>
    </table>
```

Write out the full XML — the `<!-- repeat -->` comments above are shorthand for you, not the actual file.

**Step 2:** Commit.

```bash
git add etc/db_schema.xml
git commit -m "feat(schema): pivots — post-store, post-category, post-tag, related products/posts"
```

### Task 1.6: `Api/Data/PostInterface`

**Files:**
- Create: `Api/Data/PostInterface.php`

**Step 1:** Write the interface with all getters/setters that mirror the `mageos_blog_post` columns and extension-attribute support. Full signatures (not stubs):

```php
<?php
declare(strict_types=1);

namespace MageOS\Blog\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface PostInterface extends ExtensibleDataInterface
{
    public const POST_ID = 'post_id';
    public const URL_KEY = 'url_key';
    public const TITLE = 'title';
    public const CONTENT = 'content';
    public const SHORT_CONTENT = 'short_content';
    public const FEATURED_IMAGE = 'featured_image';
    public const FEATURED_IMAGE_ALT = 'featured_image_alt';
    public const AUTHOR_ID = 'author_id';
    public const PUBLISH_DATE = 'publish_date';
    public const READING_TIME = 'reading_time';
    public const VIEWS_COUNT = 'views_count';
    public const STATUS = 'status';
    public const META_TITLE = 'meta_title';
    public const META_DESCRIPTION = 'meta_description';
    public const META_KEYWORDS = 'meta_keywords';
    public const META_ROBOTS = 'meta_robots';
    public const OG_TITLE = 'og_title';
    public const OG_DESCRIPTION = 'og_description';
    public const OG_IMAGE = 'og_image';
    public const OG_TYPE = 'og_type';
    public const STORE_IDS = 'store_ids';
    public const CATEGORY_IDS = 'category_ids';
    public const TAG_IDS = 'tag_ids';

    public function getPostId(): ?int;
    public function setPostId(int $id): self;
    public function getUrlKey(): string;
    public function setUrlKey(string $urlKey): self;
    public function getTitle(): string;
    public function setTitle(string $title): self;
    public function getContent(): ?string;
    public function setContent(?string $content): self;
    public function getShortContent(): ?string;
    public function setShortContent(?string $content): self;
    public function getFeaturedImage(): ?string;
    public function setFeaturedImage(?string $path): self;
    public function getFeaturedImageAlt(): ?string;
    public function setFeaturedImageAlt(?string $alt): self;
    public function getAuthorId(): ?int;
    public function setAuthorId(?int $id): self;
    public function getPublishDate(): ?string;
    public function setPublishDate(?string $date): self;
    public function getReadingTime(): ?int;
    public function setReadingTime(?int $minutes): self;
    public function getViewsCount(): int;
    public function setViewsCount(int $count): self;
    public function getStatus(): int;
    public function setStatus(int $status): self;
    public function getMetaTitle(): ?string;
    public function setMetaTitle(?string $title): self;
    public function getMetaDescription(): ?string;
    public function setMetaDescription(?string $desc): self;
    public function getMetaKeywords(): ?string;
    public function setMetaKeywords(?string $keywords): self;
    public function getMetaRobots(): ?string;
    public function setMetaRobots(?string $robots): self;
    public function getOgTitle(): ?string;
    public function setOgTitle(?string $title): self;
    public function getOgDescription(): ?string;
    public function setOgDescription(?string $desc): self;
    public function getOgImage(): ?string;
    public function setOgImage(?string $path): self;
    public function getOgType(): ?string;
    public function setOgType(?string $type): self;
    /** @return int[] */
    public function getStoreIds(): array;
    /** @param int[] $storeIds */
    public function setStoreIds(array $storeIds): self;
    /** @return int[] */
    public function getCategoryIds(): array;
    /** @param int[] $ids */
    public function setCategoryIds(array $ids): self;
    /** @return int[] */
    public function getTagIds(): array;
    /** @param int[] $ids */
    public function setTagIds(array $ids): self;

    public function getExtensionAttributes(): ?PostExtensionInterface;
    public function setExtensionAttributes(PostExtensionInterface $extensionAttributes): self;
}
```

**Step 2:** Commit.

```bash
git add Api/Data/PostInterface.php
git commit -m "feat(api): PostInterface service contract"
```

### Tasks 1.7 – 1.9: `Api/Data/{Category,Tag,Author}Interface`

Same pattern as Task 1.6. Getters/setters mirroring columns + extension attributes. Commit each as a separate task.

### Task 1.10: `Api/Data/*SearchResultsInterface`

**Files:**
- Create: `Api/Data/PostSearchResultsInterface.php`
- Create: `Api/Data/CategorySearchResultsInterface.php`
- Create: `Api/Data/TagSearchResultsInterface.php`
- Create: `Api/Data/AuthorSearchResultsInterface.php`

Each extends `Magento\Framework\Api\SearchResultsInterface` with `getItems(): <Entity>Interface[]` and `setItems(array): self`. Standard pattern.

Commit all four in one:

```bash
git add Api/Data/*SearchResultsInterface.php
git commit -m "feat(api): search results interfaces for all four entities"
```

### Task 1.11: `Api/PostRepositoryInterface`

**Files:**
- Create: `Api/PostRepositoryInterface.php`

**Step 1:** Write:

```php
<?php
declare(strict_types=1);

namespace MageOS\Blog\Api;

use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\Data\PostSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

interface PostRepositoryInterface
{
    public function save(PostInterface $post): PostInterface;

    /** @throws NoSuchEntityException */
    public function getById(int $id): PostInterface;

    /** @throws NoSuchEntityException */
    public function getByUrlKey(string $urlKey, int $storeId): PostInterface;

    public function getList(SearchCriteriaInterface $criteria): PostSearchResultsInterface;

    public function delete(PostInterface $post): bool;

    /** @throws NoSuchEntityException */
    public function deleteById(int $id): bool;
}
```

**Step 2:** Commit.

### Tasks 1.12 – 1.14: `Api/{Category,Tag,Author}RepositoryInterface`

Same shape. `getByUrlKey` for categories/tags, `getBySlug` for authors (since the author column is `slug`, not `url_key`). Commit each separately.

### Task 1.15: Management + Provider contracts

**Files:**
- Create: `Api/PostManagementInterface.php`
- Create: `Api/UrlKeyGeneratorInterface.php`
- Create: `Api/RelatedPostsProviderInterface.php`

**Step 1:** `PostManagementInterface`:

```php
<?php
declare(strict_types=1);

namespace MageOS\Blog\Api;

interface PostManagementInterface
{
    public function publish(int $postId): void;
    public function incrementViews(int $postId): void;
    public function computeReadingTime(string $content): int;
}
```

**Step 2:** `UrlKeyGeneratorInterface`:

```php
<?php
declare(strict_types=1);

namespace MageOS\Blog\Api;

interface UrlKeyGeneratorInterface
{
    public const ENTITY_POST = 'post';
    public const ENTITY_CATEGORY = 'category';
    public const ENTITY_TAG = 'tag';
    public const ENTITY_AUTHOR = 'author';

    /**
     * Reserved path segments that cannot be used as a url_key.
     * @return string[]
     */
    public const RESERVED = ['category', 'tag', 'author', 'search', 'rss', 'page', 'feed'];

    /**
     * @throws \InvalidArgumentException when title produces a reserved slug and cannot be suffixed.
     */
    public function generate(string $title, string $entityType, ?int $storeId = null): string;

    /**
     * @throws \InvalidArgumentException when the slug is reserved or already in use.
     */
    public function validate(string $urlKey, string $entityType, ?int $storeId, ?int $excludingEntityId = null): void;
}
```

**Step 3:** `RelatedPostsProviderInterface`:

```php
<?php
declare(strict_types=1);

namespace MageOS\Blog\Api;

use MageOS\Blog\Api\Data\PostInterface;

interface RelatedPostsProviderInterface
{
    /** @return PostInterface[] */
    public function forPost(PostInterface $post, int $limit = 5): array;
}
```

**Step 4:** Commit each contract separately.

### Task 1.16: `BlogPostStatus` enum + unit tests

**Files:**
- Create: `Model/BlogPostStatus.php`
- Create: `Test/Unit/Model/BlogPostStatusTest.php`

**Step 1:** Write the failing test:

```php
<?php
declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model;

use MageOS\Blog\Model\BlogPostStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BlogPostStatusTest extends TestCase
{
    #[Test]
    public function draft_is_zero(): void
    {
        self::assertSame(0, BlogPostStatus::Draft->value);
    }

    #[Test]
    public function scheduled_is_one(): void
    {
        self::assertSame(1, BlogPostStatus::Scheduled->value);
    }

    #[Test]
    public function published_is_two(): void
    {
        self::assertSame(2, BlogPostStatus::Published->value);
    }

    #[Test]
    public function archived_is_three(): void
    {
        self::assertSame(3, BlogPostStatus::Archived->value);
    }

    #[Test]
    public function can_build_from_int(): void
    {
        self::assertSame(BlogPostStatus::Scheduled, BlogPostStatus::from(1));
    }
}
```

**Step 2:** Run — expect failure ("BlogPostStatus not found"):

```bash
vendor/bin/phpunit --testsuite unit --filter BlogPostStatusTest
```

**Step 3:** Implement:

```php
<?php
declare(strict_types=1);

namespace MageOS\Blog\Model;

enum BlogPostStatus: int
{
    case Draft = 0;
    case Scheduled = 1;
    case Published = 2;
    case Archived = 3;
}
```

**Step 4:** Run — expect green.

**Step 5:** Commit.

```bash
git add Model/BlogPostStatus.php Test/Unit/Model/BlogPostStatusTest.php
git commit -m "feat(model): BlogPostStatus enum with unit tests"
```

### Task 1.17: `UrlKeyGenerator` — slug normalization (TDD)

**Files:**
- Create: `Test/Unit/Model/UrlKeyGeneratorTest.php`
- Create: `Model/UrlKeyGenerator.php`

**Step 1:** Write the failing tests (test behavior, not implementation — focus on the contract):

```php
<?php
declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model;

use MageOS\Blog\Api\UrlKeyGeneratorInterface;
use MageOS\Blog\Model\UrlKeyGenerator;
use MageOS\Blog\Model\UrlKeyGenerator\CollisionChecker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UrlKeyGeneratorTest extends TestCase
{
    private CollisionChecker $checker;
    private UrlKeyGenerator $generator;

    protected function setUp(): void
    {
        $this->checker = $this->createMock(CollisionChecker::class);
        $this->checker->method('isTaken')->willReturn(false);
        $this->generator = new UrlKeyGenerator($this->checker);
    }

    #[Test]
    #[DataProvider('normalizationCases')]
    public function normalizes_title_to_slug(string $title, string $expected): void
    {
        self::assertSame(
            $expected,
            $this->generator->generate($title, UrlKeyGeneratorInterface::ENTITY_POST)
        );
    }

    public static function normalizationCases(): array
    {
        return [
            'simple'              => ['Hello World', 'hello-world'],
            'trailing punctuation' => ['Hello, World!', 'hello-world'],
            'unicode accents'     => ['Café naïve', 'cafe-naive'],
            'multiple spaces'     => ['a   b   c', 'a-b-c'],
            'leading slashes'     => ['/hello/world/', 'hello-world'],
            'emoji'               => ['Ship 🚀 it', 'ship-it'],
            'numbers'             => ['Top 10 Tips', 'top-10-tips'],
        ];
    }

    #[Test]
    public function rejects_reserved_slug(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->generator->validate('category', UrlKeyGeneratorInterface::ENTITY_POST, 1);
    }

    #[Test]
    public function appends_suffix_on_collision(): void
    {
        $checker = $this->createMock(CollisionChecker::class);
        $checker->expects(self::exactly(3))
            ->method('isTaken')
            ->willReturnOnConsecutiveCalls(true, true, false);
        $generator = new UrlKeyGenerator($checker);

        self::assertSame('hello-3', $generator->generate('hello', UrlKeyGeneratorInterface::ENTITY_POST));
    }
}
```

**Step 2:** Run — expect failure.

**Step 3:** Implement `Model/UrlKeyGenerator/CollisionChecker.php`:

```php
<?php
declare(strict_types=1);

namespace MageOS\Blog\Model\UrlKeyGenerator;

interface CollisionChecker
{
    public function isTaken(string $urlKey, string $entityType, ?int $storeId, ?int $excludingEntityId = null): bool;
}
```

**Step 4:** Implement `Model/UrlKeyGenerator.php`:

```php
<?php
declare(strict_types=1);

namespace MageOS\Blog\Model;

use MageOS\Blog\Api\UrlKeyGeneratorInterface;
use MageOS\Blog\Model\UrlKeyGenerator\CollisionChecker;

final class UrlKeyGenerator implements UrlKeyGeneratorInterface
{
    public function __construct(private readonly CollisionChecker $checker)
    {
    }

    public function generate(string $title, string $entityType, ?int $storeId = null): string
    {
        $base = $this->normalize($title);
        if ($base === '' || \in_array($base, self::RESERVED, true)) {
            throw new \InvalidArgumentException("Cannot generate a URL key from '{$title}'.");
        }

        $candidate = $base;
        $suffix = 1;
        while ($this->checker->isTaken($candidate, $entityType, $storeId)) {
            $suffix++;
            $candidate = "{$base}-{$suffix}";
        }

        return $candidate;
    }

    public function validate(string $urlKey, string $entityType, ?int $storeId, ?int $excludingEntityId = null): void
    {
        if (\in_array($urlKey, self::RESERVED, true)) {
            throw new \InvalidArgumentException("URL key '{$urlKey}' is reserved.");
        }
        if ($this->checker->isTaken($urlKey, $entityType, $storeId, $excludingEntityId)) {
            throw new \InvalidArgumentException("URL key '{$urlKey}' is already in use.");
        }
    }

    private function normalize(string $title): string
    {
        $ascii = \iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title) ?: $title;
        $lower = \strtolower($ascii);
        $slug = \preg_replace('/[^a-z0-9]+/', '-', $lower) ?? '';
        return \trim($slug, '-');
    }
}
```

**Step 5:** Run — expect green.

**Step 6:** Commit.

```bash
git add Model/UrlKeyGenerator.php Model/UrlKeyGenerator/CollisionChecker.php Test/Unit/Model/UrlKeyGeneratorTest.php
git commit -m "feat(model): UrlKeyGenerator with normalization, collision retry, reserved slugs"
```

### Task 1.18: `CollisionChecker` DB-backed implementation (integration-tested)

**Files:**
- Create: `Model/UrlKeyGenerator/DbCollisionChecker.php`
- Create: `Test/Integration/Model/UrlKeyGenerator/DbCollisionCheckerTest.php`
- Modify: `etc/di.xml` — preference `CollisionChecker` → `DbCollisionChecker`

**Step 1:** Integration test uses fixtures to insert existing rows and asserts `isTaken()` behavior across entity types and stores. Standard Magento integration test with `#[DataFixture]` attribute.

**Step 2:** Implement `DbCollisionChecker` querying each entity table via a map: `entityType → table → column`. Use direct `ResourceConnection` SELECT (not repositories, to avoid circular dependency).

**Step 3:** Wire in `etc/di.xml`.

**Step 4:** Commit.

### Task 1.19: `Model/Post` entity

**Files:**
- Create: `Model/Post.php`
- Create: `Model/ResourceModel/Post.php`
- Create: `Model/ResourceModel/Post/Collection.php`

**Step 1:** `Model/Post.php` extends `Magento\Framework\Model\AbstractExtensibleModel` and implements `PostInterface`, `IdentityInterface`. Getters/setters map to data keys using `$this->getData()` / `$this->setData()`.

**Step 2:** `Model/ResourceModel/Post.php` extends `AbstractDb`, initializes with `$this->_init('mageos_blog_post', 'post_id')`.

**Step 3:** `Model/ResourceModel/Post/Collection.php` extends `AbstractCollection`, initializes with `$this->_init(Post::class, PostResource::class)`.

**Step 4:** Commit.

### Tasks 1.20 – 1.22: Model/ResourceModel for Category, Tag, Author

Same pattern. Separate commits.

### Task 1.23: `PostRepository` (integration-tested)

**Files:**
- Create: `Model/PostRepository.php`
- Create: `Test/Integration/Model/PostRepositoryTest.php`

**Step 1:** Integration test covers save/load/delete round-trip, `getByUrlKey` scoping, soft-save vs hard-delete, `getList` with `SearchCriteria`.

**Step 2:** Implement `PostRepository` using `PostResource`, `PostFactory`, `CollectionProcessorInterface`, `SearchResultsFactory`. Handles store assignment via `PostResource::getLinkedStoreIds($postId)` + save-time `saveStoreRelation`. Store/category/tag linkage via separate `LinkManager` classes to keep the repo lean.

**Step 3:** Wire `<preference>` in `etc/di.xml`.

**Step 4:** Commit.

### Tasks 1.24 – 1.26: {Category,Tag,Author}Repository

Same pattern. Separate commits.

### Task 1.27: `etc/di.xml` — preferences + plugins + virtual types

**Files:**
- Modify: `etc/di.xml` (consolidate all wiring)

Wire all `<preference for="Api\Data\*Interface" type="Model\*" />` + `<preference for="Api\*RepositoryInterface" type="Model\*Repository" />` + `<preference for="Api\UrlKeyGeneratorInterface" type="Model\UrlKeyGenerator" />` + `<preference for="Model\UrlKeyGenerator\CollisionChecker" type="Model\UrlKeyGenerator\DbCollisionChecker" />`.

Commit.

### Task 1.28: PHPStan + CS passes on phase 1 code

Run:

```bash
vendor/bin/phpstan analyse --memory-limit=1G
vendor/bin/php-cs-fixer fix --dry-run --diff
vendor/bin/phpcs --standard=phpcs.xml.dist
```

Fix any findings. Commit fixes as separate commit(s) tagged `chore(phpstan):` / `style:`.

### Task 1.29: Tag v0.1.0

**Step 1:** Verify green CI on the feature branch.

**Step 2:** Tag:

```bash
git tag -a v0.1.0 -m "v0.1.0 — Foundation (phase 1): entities, repositories, URL key generation"
```

**Do not push the tag until Phase 5** — the tag is a local checkpoint until v1.0.0 ships.

---

## Phase 2 — Admin (~1.5 weeks, task-level)

**Deliverable:** `v0.2.0` — merchants can CRUD posts/categories/tags/authors via admin. Integration tests exercise the save/delete flows.

Before starting Phase 2, refine each task below into bite-sized TDD steps following the Phase 1 cadence.

### Task 2.1: Admin scaffolding

**Files to create:**
- `etc/adminhtml/routes.xml` (frontName `mageos_blog`)
- `etc/adminhtml/menu.xml` (top-level Blog under Content — Posts, Categories, Tags, Authors, Configuration)
- `etc/acl.xml` (`MageOS_Blog::{post,category,tag,author,config}`)
- `etc/adminhtml/system.xml` (`mageos_blog/*` fields — general/enabled, post/*, sidebar/*, seo/*, sitemap/*, social/*, rss/*)
- `etc/config.xml` (defaults matching system.xml)
- `Model/Config.php` (constants for every XML path, typed getters)

Unit-test `Model/Config` getters with mocked `ScopeConfigInterface`.

### Task 2.2: Post listing UI component

**Files:**
- `view/adminhtml/ui_component/mageos_blog_post_listing.xml`
- `view/adminhtml/layout/mageos_blog_post_index.xml`
- `Ui/DataProvider/Post/Grid/Collection.php` (extends `SearchResult`, returns collection-as-search-result)
- `Controller/Adminhtml/Post/Index.php` (renders listing)
- `Controller/Adminhtml/Post/MassDelete.php`, `MassEnable.php`, `MassDisable.php`, `InlineEdit.php`

Columns: post_id, title, url_key, status, publish_date, author, categories (multi), store_ids (multi). Mass actions: enable / disable / delete.

Integration test: GET `/admin/mageos_blog/post/index` returns 200 with ACL allowed; 403 without.

### Task 2.3: Post form UI component

**Files:**
- `view/adminhtml/ui_component/mageos_blog_post_form.xml`
- `Ui/DataProvider/Post/Form/DataProvider.php`
- `Controller/Adminhtml/Post/NewAction.php`, `Edit.php`, `Save.php`, `Delete.php`
- `Controller/Adminhtml/Post/UploadImage.php` (with image uploader virtual type in `etc/di.xml`)

Form tabs (fieldsets in UI component XML):
1. **General** — title, url_key (with inline regenerate button), status, publish_date, author_id (grid chooser dropdown)
2. **Content** — short_content (WYSIWYG), content (WYSIWYG)
3. **Images** — featured_image (file uploader), featured_image_alt, og_image (file uploader)
4. **SEO** — meta_title, meta_description, meta_keywords, meta_robots (select), og_title, og_description, og_type (select), twitter-related fields read from store config
5. **Taxonomy** — category_ids (multi-select with tree), tag_ids (multi-select with create-new)
6. **Related** — related_post_ids (nested listing), related_product_ids (nested listing)
7. **Stores** — store_ids (multi-select)

Integration test: POST to `Save.php` with valid payload persists the post + store links + category links + tag links.

### Task 2.4: Related-posts and related-products nested listings

**Files:**
- `view/adminhtml/ui_component/mageos_blog_post_related_posts_listing.xml`
- `view/adminhtml/ui_component/mageos_blog_post_related_products_listing.xml`
- `Ui/DataProvider/Post/Related/PostDataProvider.php`
- `Ui/DataProvider/Post/Related/ProductDataProvider.php`

Nested listings follow Magento's standard related-entities-on-form pattern (see `Magento_Catalog`'s up-sell listing on product form for reference).

### Tasks 2.5 – 2.7: Category, Tag, Author admin

Same shape as 2.2/2.3 for each entity. Author form includes avatar upload; Category form includes parent_id (self-reference) and position.

### Task 2.8: Admin menu + integration tests

Integration test loops through each listing route + each NewAction/Edit/Save/Delete for each entity. Confirm ACL enforcement with a non-blog admin user.

### Task 2.9: Tag v0.2.0

Same process as 1.29.

---

## Phase 3 — Storefront (Luma) (~2 weeks, task-level)

**Deliverable:** `v0.3.0` — full Luma storefront end-to-end. `url_rewrite` lifecycle + cron integration-tested.

### Task 3.1: `url_rewrite` integration in repositories

**Approach:** a dedicated `Model\Url\UrlRewriteBuilder` service produces `UrlRewrite` objects (via `UrlRewriteFactory`) from a `PostInterface` / `CategoryInterface` / `TagInterface` / `AuthorInterface`. The respective repository's `save()` method calls `UrlPersistInterface::replace()` with the freshly built rewrites after persisting the entity; the 301 redirect for slug changes is built by comparing old/new `url_key` on an existing entity.

**Files:**
- `Model/Url/UrlRewriteBuilder.php`
- `Plugin/Repository/Post/UrlRewritePlugin.php` (afterSave + afterDelete)
- Same plugins for Category, Tag, Author

**Integration tests:**
- Save new post → one `url_rewrite` row (`entity_type=mageos_blog_post`, correct `request_path`, correct `target_path`).
- Change `url_key` → original row's `redirect_type` set to 301, new row with new `request_path`.
- Delete post → all `url_rewrite` rows with matching `entity_type` + `entity_id` removed.
- Multi-store save → one row per store.

### Task 3.2: Storefront controllers

**Files:**
- `Controller/Index/Index.php`
- `Controller/Post/View.php`
- `Controller/Category/View.php`
- `Controller/Tag/View.php`
- `Controller/Author/View.php`
- `Controller/Search/Index.php`
- `Controller/Rss/Index.php` (stub — implement fully in Phase 4)
- `Controller/Post/IncrementViews.php`

Each controller: implements `HttpGetActionInterface` (except IncrementViews which is `HttpPostActionInterface`), uses `PageFactory` to render, resolves entity via repository, forwards to 404 on missing/inactive/wrong-store. ~30–60 lines each.

Integration test per controller: valid slug → 200 + expected block; invalid → 404 forward; inactive in current store → 404 forward.

### Task 3.3: Layout XMLs

**Files:**
- `view/frontend/layout/blog_default.xml` (common head, sidebar container)
- `view/frontend/layout/blog_index_index.xml`
- `view/frontend/layout/blog_post_view.xml`
- `view/frontend/layout/blog_category_view.xml`
- `view/frontend/layout/blog_tag_view.xml`
- `view/frontend/layout/blog_author_view.xml`
- `view/frontend/layout/blog_search_index.xml`
- `view/frontend/layout/default.xml` (adds blog menu link if enabled)
- `view/frontend/layout/catalog_product_view.xml` (injects "Related Posts" block on product page footer)

### Task 3.4: ViewModels

**Files:**
- `ViewModel/Post/Listing.php` — takes a `SearchCriteria` builder + pagination state, returns `PostInterface[]` + pagination metadata
- `ViewModel/Post/Detail.php` — all the methods listed in the design doc
- `ViewModel/Category/Detail.php`, `ViewModel/Tag/Detail.php`, `ViewModel/Author/Detail.php`
- `ViewModel/Sidebar/{RecentPosts,CategoryList,TagCloud,Archive,SearchBox}.php`
- `ViewModel/Search/Results.php`

All implement `Magento\Framework\View\Element\Block\ArgumentInterface`. Zero Magento framework calls outside injection.

### Task 3.5: Blocks (thin)

**Files:**
- `Block/Post/Listing.php`, `Block/Post/View.php`, `Block/Category/View.php`, etc.

Each block subclasses `Magento\Framework\View\Element\Template` and just carries the ViewModel passed via layout XML `<arguments>`. No domain logic in blocks.

### Task 3.6: Luma templates

**Files:**
- `view/frontend/templates/post/listing.phtml`
- `view/frontend/templates/post/view.phtml`
- `view/frontend/templates/post/item.phtml` (used inside listing)
- `view/frontend/templates/category/view.phtml`
- `view/frontend/templates/tag/view.phtml`
- `view/frontend/templates/author/view.phtml`
- `view/frontend/templates/search/results.phtml`
- `view/frontend/templates/sidebar/container.phtml`
- `view/frontend/templates/sidebar/{search-box,recent-posts,category-list,tag-cloud,archive}.phtml`
- `view/frontend/templates/product/related-posts.phtml`

Luma-style: plain PHP with `$escaper->escapeHtml(...)`, `$block->getViewModel()->getXxx()`. Use `$this->helper(...)` calls only where absolutely necessary (they shouldn't be necessary).

### Task 3.7: `PostManagement` implementation + tests

**Files:**
- `Model/PostManagement.php` — `publish`, `incrementViews`, `computeReadingTime`

Unit-test `computeReadingTime` with fixtures (including HTML stripping). Integration-test `incrementViews` under concurrency (atomic UPDATE).

### Task 3.8: Scheduled publishing cron

**Files:**
- `etc/crontab.xml`
- `Cron/PublishScheduledPosts.php`

Integration test: insert a post with `status=Scheduled` and `publish_date` 10 minutes ago → run cron → status now `Published` + cache tag dispatched (verify by capturing events).

### Task 3.9: `RelatedPostsProvider` implementation + tests

**Files:**
- `Model/RelatedPostsProvider.php`
- `Model/RelatedPostsProvider/ManualRelationLoader.php`
- `Model/RelatedPostsProvider/AlgorithmicLoader.php`

Unit-test the algorithm in isolation (feed synthetic post data, assert ordering). Integration-test the cache invalidation.

### Task 3.10: Reserved-slug enforcement in repository save

Update `PostRepository::save` to call `UrlKeyGenerator::validate` before persistence. Integration test: saving with `url_key=category` → `LocalizedException` with the right message.

### Task 3.11: Tag v0.3.0

---

## Phase 4 — Hyvä + SEO + search + widgets + RSS (~1.5 weeks, task-level)

**Deliverable:** `v0.4.0` — feature-parity with commercial blog modules for end users.

### Task 4.1: Hyvä detection + template path resolution

**Files:**
- `Api/HyvaThemeDetectionInterface.php`
- `Model/HyvaThemeDetection.php` (walks theme inheritance chain looking for `hyva` in path)
- `Plugin/Magento/Framework/View/TemplateEngine/Php.php` (beforeRender — swap template path to `hyva/<path>` when active)
- `etc/di.xml` — register the plugin

Unit test the detection (mock `ThemeProviderInterface` + a chain of themes). Integration test the plugin (assert template path swap happens only on Hyvä).

### Task 4.2: Hyvä templates

**Files:**
- `view/frontend/templates/hyva/post/listing.phtml`
- `view/frontend/templates/hyva/post/view.phtml`
- `view/frontend/templates/hyva/post/item.phtml`
- `view/frontend/templates/hyva/{category,tag,author,search}/view.phtml`
- `view/frontend/templates/hyva/sidebar/*.phtml`

Pure Alpine + Tailwind. No Magento helper calls. Use Hyvä's `ViewModel\Heroicons` or inline SVG for icons. Reference: `hyva-themes/magento2-default-theme` for class naming and Alpine patterns.

### Task 4.3: OG / Twitter / JSON-LD ViewModels

**Files:**
- Extend `ViewModel/Post/Detail.php`: `getOgTags(): array`, `getTwitterTags(): array`, `getJsonLd(): string`
- New `ViewModel/Post/SocialShare.php` (per design section 3.8)
- `Block/Post/JsonLd.php` + `view/frontend/templates/post/jsonld.phtml` (+ Hyvä variant)
- Update `view/frontend/layout/blog_post_view.xml` to inject JsonLd block
- Update `view/frontend/layout/default.xml` to contribute OG/Twitter meta via `PageConfig`

Unit-test each ViewModel method with fixtures.

### Task 4.4: Sitemap ItemProviders

**Files:**
- `Model/Sitemap/ItemProvider/Post.php`
- `Model/Sitemap/ItemProvider/Category.php`
- `Model/Sitemap/ItemProvider/Tag.php`
- `etc/di.xml` — register with `Magento\Sitemap\Model\ItemProvider\Composite`
- `etc/config.xml` — defaults for `mageos_blog/sitemap/<entity>/{enabled,frequency,priority}`

Integration test: generate sitemap.xml, assert blog URLs present with correct frequency/priority.

### Task 4.5: Search integration — indexer + mview + request.xml

**Files:**
- `etc/search_request.xml`
- `etc/indexer.xml`
- `etc/mview.xml`
- `Model/Indexer/Post/Fulltext/Action/Full.php`
- `Model/Indexer/Post/Fulltext/Action/Rows.php`
- `Model/Indexer/Post/Fulltext/IndexerHandler.php`
- `Model/Indexer/Post/Fulltext.php` (the indexer class itself)

Integration test: insert post → run `bin/magento indexer:reindex mageos_blog_post_fulltext` → search via `SearchInterface` with request name → hit found. Test delete → hit gone.

### Task 4.6: Search controller + template

**Files:**
- `Controller/Search/Index.php` (upgrade from Phase 3 stub)
- `ViewModel/Search/Results.php` (uses `SearchInterface`)
- `view/frontend/templates/search/results.phtml` (+ Hyvä variant)

Integration test: query with hits → 200 + expected posts; empty query → redirect to `/blog/`; zero hits → friendly no-results template.

### Task 4.7: Widgets

**Files:**
- `etc/widget.xml` — define 6 widgets
- `Block/Widget/RecentPosts.php` + `FeaturedPost.php` + `PostLink.php` + `CategoryLink.php` + `TagLink.php` + `PostList.php`
- `Block/Adminhtml/Widget/Chooser/Post.php` + `Category.php` + `Tag.php`
- `view/frontend/templates/widget/*.phtml` + Hyvä variants

Each widget block is thin; logic in ViewModels where reusable.

Integration test: define a widget in CMS, render on a page, assert expected HTML.

### Task 4.8: RSS feed

**Files:**
- `Model/Rss/BlogFeed.php` (implements `Magento\Framework\App\Rss\DataProviderInterface`)
- `etc/rss.xml`
- `Controller/Rss/Index.php` (upgrade from Phase 3 stub)

Integration test: GET `/blog/rss` returns Atom 1.0 with latest N posts; filters by category/tag/author work.

### Task 4.9: Tag v0.4.0

---

## Phase 5 — GraphQL + polish + docs (~1 week, task-level)

**Deliverable:** `v1.0.0` — ship.

### Task 5.1: schema.graphqls — types

**Files:**
- `etc/schema.graphqls`

Add `BlogPost`, `BlogCategory`, `BlogTag`, `BlogAuthor` types, filter inputs, sort inputs, output types (with `items`, `page_info`, `total_count`). Extend `UrlRewriteEntityTypeEnum` with 4 new values.

### Task 5.2: Query resolvers

**Files:**
- `Model/Resolver/Post/ListResolver.php`, `DetailResolver.php`
- Same for Category, Tag, Author

Resolvers delegate to repositories using `SearchCriteriaBuilder`. Integration test: run each query against fixture data, assert response shape.

### Task 5.3: Mutation resolvers

**Files:**
- `Model/Resolver/Post/CreateResolver.php`, `UpdateResolver.php`, `DeleteResolver.php`
- Same for Category, Tag, Author
- `Model/Resolver/AdminAuthorization.php` — helper that checks `$context->getExtensionAttributes()->getIsAdmin()` + ACL

Integration test: mutations succeed with admin token, reject (GraphQlAuthorizationException) without.

### Task 5.4: URL resolver — BlogPost / BlogCategory / BlogTag / BlogAuthor

**Files:**
- `Model/Resolver/UrlResolver/BlogPost.php`, `BlogCategory.php`, `BlogTag.php`, `BlogAuthor.php`
- `etc/graphql/di.xml` — register with `CustomUrlLocatorInterface`

Integration test: `{ urlResolver(url:"/blog/my-post") { type id relative_url } }` returns `{ type: "BLOG_POST", id: 42, relative_url: "/blog/my-post" }`.

### Task 5.5: i18n

Run `bin/magento i18n:collect-phrases --output=i18n/en_US.csv . --magento` from module directory. Commit result.

### Task 5.6: README.md

**Files:**
- `README.md` — install (composer), configuration (key admin settings), compat matrix (Magento 2.4.6 / 2.4.7 × PHP 8.2 / 8.3 / Hyvä), upgrade notes (greenfield only — no migration from v0 fork), contributing, license.
- `CHANGELOG.md` — Keep-a-Changelog format, `1.0.0` entry summarizing all phases.

Include an **attribution note**: "Design inspired by Magefan Blog (OSL-3.0); v1 is an independent implementation with no shared code."

### Task 5.7: Infection MSI ≥ 75%

Run `vendor/bin/infection`. Address survivors by either killing them with new tests or documenting why they're acceptable (edge cases in templates etc., which Infection shouldn't see since we target `Model/`, `ViewModel/`, `Cron/` only).

### Task 5.8: Final CI pass + v1.0.0 tag

**Step 1:** Verify green CI on the feature branch across both PHP versions × both Magento versions.

**Step 2:** Merge `feat/v1-rewrite` into `main` (squash or merge-commit per project convention).

**Step 3:** Tag:

```bash
git tag -a v1.0.0 -m "v1.0.0 — MageOS Blog, full rewrite"
git push origin main
git push origin v1.0.0
```

**Step 4:** Verify Packagist sync. Verify GitHub release auto-created via `release.yml`.

Done.

---

## Out of scope for v1 (deliberately — track as issues for v1.1+)

- Comments (native, moderation UI, spam handling, email notifications)
- Importers from Mageplaza / Magefan / Aheadworks / Mirasvit / WordPress (separate `mageos/module-blog-migration` package)
- PageBuilder content editing
- AddThis / third-party social scripts
- Configurable URL prefix
- Custom CSS injection per page
- Preview URL with secret token model
- AdminGWS plugin (Magento Commerce-only)
- MFTF tests
- Performance benchmarks
- Author gravatar autofetch
- Multi-language content variants per post (i18n of content, not UI)

---

## Reference material

- Design doc: `docs/plans/2026-04-19-mageos-blog-v1-rewrite-design.md`
- Magento 2 developer docs: `https://developer.adobe.com/commerce/php/development/` (for API patterns)
- Hyvä docs: `https://docs.hyva.io/` (for companion template patterns)
- Graycore GitHub Actions: `https://github.com/graycoreio/github-actions-magento2`
- OSL-3.0 license text: `https://opensource.org/license/osl-3-0-php`

If stuck on a Magento-specific API (URL rewrites, search requests, UI components, GraphQL resolvers), use **Context7 MCP** (`mcp__context7__resolve-library-id` + `get-library-docs`) rather than guessing — training data may be out of date.
