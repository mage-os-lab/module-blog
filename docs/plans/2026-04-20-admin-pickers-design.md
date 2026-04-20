# Admin pickers + author bug — design

_Scope: v1.0.1 patch release. Effort: ~4 hours._

## Problem

Live smoke (20 April 2026) against `mage-os-typesense` surfaced two issues that
block real day-to-day admin use of MageOS_Blog v1.0.0:

1. **Author edit form crashes** — `view/adminhtml/ui_component/mageos_blog_author_form.xml` has the same invalid
   nested `<param name="type" xsi:type="string">avatar</param>` inside `uploaderConfig` that
   the post-form carried. `xsi:type="string"` is not a valid `<param>` type.
2. **The post-form taxonomy and related-entity fields are plain text inputs** asking the editor
   for "Category IDs (comma-separated)". Same for `tag_ids`, `related_post_ids`,
   `related_product_ids`. Unusable without DB access.
3. **The category edit form's `parent_id` is a plain number input** — same problem.

## Non-goals

- Full product-style category admin (split-view tree sidebar + edit form, drag-drop
  reorder, jstree widget). Tracked for v1.1.
- Inline "New Category" creation button on the post-form picker.
- Custom JS tree modal. We get the hierarchy visualization via Magento's standard
  hierarchical `optgroup` format, which `ui-select` already renders.

## Design

### Shared pattern for taxonomy + related-entity pickers (post form)

Three fields — `category_ids`, `tag_ids`, `related_post_ids` — switch from
`<formElement>input</formElement>` to `<formElement>select</formElement>` with
`<multiple>true</multiple>`. That activates `Magento_Ui/js/form/element/ui-select`
— a searchable multi-select with chips. No custom JS.

Options source classes (all implement `Magento\Framework\Data\OptionSourceInterface`):

- `MageOS\Blog\Ui\Component\Form\Categories\Options` — returns all active categories
  in Magento's hierarchical `optgroup` format:

  ```php
  [
      ['value' => 1, 'label' => 'News', 'optgroup' => [
          ['value' => 3, 'label' => 'Tech'],
      ]],
      ['value' => 2, 'label' => 'Guides'],
  ]
  ```

  `ui-select` renders this with visual nesting, so picking from a deep tree feels
  as natural as Magento's catalog category picker.

- `MageOS\Blog\Ui\Component\Form\Tags\Options` — flat list.

- `MageOS\Blog\Ui\Component\Form\RelatedPosts\Options` — flat list of published
  posts, excluding the current post so a post can't relate to itself. Cap at 500.
  The current post's ID is read from the request via `RequestInterface::getParam('post_id')`.

Data plumbing:

- The DataProvider already returns `category_ids` / `tag_ids` as `int[]` arrays
  (they come from the pivot tables via `Post::getCategoryIds()` etc.).
- The existing `Save` controller splits the form's CSV; we'll change it to accept
  an array directly when `ui-select` posts one.

### Related-products picker

`related_product_ids` needs a picker against the Magento catalog, so reuse the
canonical "pick products from grid" UX Magento itself uses on the product form's
"Related Products" tab: an `insertListing` modal that embeds `product_listing`.

Files:

- `view/adminhtml/ui_component/mageos_blog_post_form.xml` — replace the field with
  an `insertListing` pointing at `product_listing` with `dataScope=related_product_ids`.
- No new PHP classes; `product_listing` already exists and is reusable.

### Category form: parent_id picker

Single-select, not multi. Source: `MageOS\Blog\Ui\Component\Form\ParentCategory\Options`.

Rules:

- All categories listed, in hierarchical `optgroup` format.
- Prepended with `— None (Root) —` (value `null`).
- When editing category #N, the options list filters out #N itself **and all
  descendants of N** so the user cannot create a cycle. Descendant calculation via
  recursive walk of `parent_id`.
- The current `category_id` is read from the request.

### Author form avatar bug

Replace

```xml
<param name="url" xsi:type="url" path="mageos_blog/author/uploadImage">
    <param name="type" xsi:type="string">avatar</param>
</param>
```

with

```xml
<param name="url" xsi:type="url" path="mageos_blog/author/uploadImage/type/avatar"/>
```

Matching the post-form fix in commit `30d63f4`. The controller already reads
`type` via `Request::getParam('type')` which picks up Magento's URL-path
positional parameters.

## Out of scope, noted for v1.1

- Full split-view tree admin for blog categories (drag-drop reorder, root/subcategory
  buttons, jstree).
- Inline "New Category" creation button from the post-form picker.
- Paginated source for `related_post_ids` on big blogs (>500 posts).

## Commit plan

1. `fix(admin): author avatar upload xsi:type` — author bug.
2. `feat(admin): hierarchical parent-category picker on category form` — #6.
3. `feat(admin): category + tag + related-post pickers on post form` — #2–4, shared pattern.
4. `feat(admin): related-products insertListing picker on post form` — #5.

Each commit is self-contained and leaves the module in a working state.

## Verification

- PHPStan level 8 clean.
- PHPCS clean.
- PHPUnit: 57 / 57 tests still green (no new units for source classes — they're
  thin; we rely on integration smoke).
- Live admin smoke: open post edit + category edit + author edit in Playwright,
  pick a parent category, pick several tags, save, refetch, assert the values
  round-trip.
