<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Breadcrumb;

use Magento\Framework\UrlInterface;
use MageOS\Blog\Api\Data\AuthorInterface;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\Data\TagInterface;
use MageOS\Blog\Model\Breadcrumb\TrailBuilder;
use MageOS\Blog\Model\Url\Permalink;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TrailBuilderTest extends TestCase
{
    #[Test]
    public function index_trail_is_home_and_an_unlinked_blog(): void
    {
        $trail = $this->builder()->forIndex();

        self::assertSame(['home', 'blog'], array_keys($trail));
        self::assertSame('https://shop.test/', $trail['home']['link']);
        self::assertArrayNotHasKey('link', $trail['blog']);
    }

    #[Test]
    public function post_trail_runs_through_the_category(): void
    {
        $trail = $this->builder()->forPost($this->post('Hello'), $this->category('News', 'news'));

        self::assertSame(['home', 'blog', 'blog_category', 'blog_post'], array_keys($trail));
        self::assertSame('https://shop.test/blog', $trail['blog']['link']);
        self::assertSame('News', $trail['blog_category']['label']);
        self::assertSame('https://shop.test/blog/category/news', $trail['blog_category']['link']);
        self::assertSame('Hello', $trail['blog_post']['label']);
        self::assertArrayNotHasKey('link', $trail['blog_post']);
    }

    #[Test]
    public function post_trail_skips_the_category_segment_when_there_is_none(): void
    {
        $trail = $this->builder()->forPost($this->post('Hello'), null);

        self::assertSame(['home', 'blog', 'blog_post'], array_keys($trail));
    }

    #[Test]
    public function category_trail_ends_on_an_unlinked_category(): void
    {
        $trail = $this->builder()->forCategory($this->category('News', 'news'));

        self::assertSame(['home', 'blog', 'blog_category'], array_keys($trail));
        self::assertSame('https://shop.test/blog', $trail['blog']['link']);
        self::assertSame('News', $trail['blog_category']['label']);
        self::assertArrayNotHasKey('link', $trail['blog_category']);
    }

    #[Test]
    public function tag_trail_labels_the_tag(): void
    {
        $tag = $this->createStub(TagInterface::class);
        $tag->method('getTitle')->willReturn('php');

        $trail = $this->builder()->forTag($tag);

        self::assertSame('Tagged "php"', $trail['blog_tag']['label']);
        self::assertArrayNotHasKey('link', $trail['blog_tag']);
    }

    #[Test]
    public function author_trail_uses_the_author_name(): void
    {
        $author = $this->createStub(AuthorInterface::class);
        $author->method('getName')->willReturn('Jane Doe');

        $trail = $this->builder()->forAuthor($author);

        self::assertSame('Jane Doe', $trail['blog_author']['label']);
        self::assertSame('https://shop.test/blog', $trail['blog']['link']);
    }

    #[Test]
    public function search_trail_includes_the_query(): void
    {
        $trail = $this->builder()->forSearch('magento');

        self::assertSame('Search: magento', $trail['blog_search']['label']);
        self::assertArrayNotHasKey('link', $trail['blog_search']);
    }

    private function builder(): TrailBuilder
    {
        $urlBuilder = $this->createStub(UrlInterface::class);
        $urlBuilder->method('getUrl')->willReturnCallback(
            static fn (string $route, ?array $params = null): string => 'https://shop.test/' . ($params['_direct'] ?? '')
        );

        return new TrailBuilder($urlBuilder, new Permalink());
    }

    private function post(string $title): PostInterface
    {
        $post = $this->createStub(PostInterface::class);
        $post->method('getTitle')->willReturn($title);

        return $post;
    }

    private function category(string $title, string $urlKey): CategoryInterface
    {
        $category = $this->createStub(CategoryInterface::class);
        $category->method('getTitle')->willReturn($title);
        $category->method('getUrlKey')->willReturn($urlKey);

        return $category;
    }
}
