<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Url;

use MageOS\Blog\Model\Url\Permalink;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Pins the request paths every blog page had before they were centralised, so the refactor
 * cannot change a URL that is already indexed.
 */
final class PermalinkTest extends TestCase
{
    #[Test]
    public function index_lives_on_the_front_name(): void
    {
        self::assertSame('blog', (new Permalink())->getIndexPath());
    }

    #[Test]
    public function search_lives_under_the_index(): void
    {
        self::assertSame('blog/search', (new Permalink())->getSearchPath());
    }

    #[Test]
    public function post_sits_directly_under_the_index(): void
    {
        self::assertSame('blog/hello-world', (new Permalink())->getPostPath('hello-world'));
    }

    #[Test]
    public function category_tag_and_author_get_their_own_segment(): void
    {
        $permalink = new Permalink();

        self::assertSame('blog/category/news', $permalink->getCategoryPath('news'));
        self::assertSame('blog/tag/magento', $permalink->getTagPath('magento'));
        self::assertSame('blog/author/jane-doe', $permalink->getAuthorPath('jane-doe'));
    }

    #[Test]
    public function index_path_has_no_trailing_slash(): void
    {
        // Rewrites are stored without one; a trailing slash on a link costs a 301 (#31).
        self::assertStringEndsNotWith('/', (new Permalink())->getIndexPath());
    }
}
