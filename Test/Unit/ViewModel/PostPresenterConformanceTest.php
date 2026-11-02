<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\ViewModel;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\Data\AuthorInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Model\Config;
use MageOS\Blog\Model\Post\PostsByAssignmentProvider;
use MageOS\Blog\ViewModel\Author\Detail as AuthorDetail;
use MageOS\Blog\ViewModel\Category\Detail as CategoryDetail;
use MageOS\Blog\ViewModel\Post\PostPresenterInterface;
use MageOS\Blog\ViewModel\Search\Results as SearchResults;
use MageOS\Blog\ViewModel\Tag\Detail as TagDetail;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

/**
 * The category, tag, author and search ViewModels render post cards, so they
 * must all share the one presenter implementation (and with it the one place
 * the byline can be switched off).
 */
final class PostPresenterConformanceTest extends TestCase
{
    private UrlInterface&Stub $urlBuilder;
    private AuthorRepositoryInterface&Stub $authorRepository;

    protected function setUp(): void
    {
        $this->urlBuilder = $this->createStub(UrlInterface::class);
        $this->urlBuilder->method('getUrl')->willReturnCallback(
            static fn (string $route, array $params): string => 'https://shop.test/' . $params['_direct']
        );
        $this->urlBuilder->method('getBaseUrl')->willReturn('https://shop.test/media/');
        $this->authorRepository = $this->createStub(AuthorRepositoryInterface::class);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function viewModels(): array
    {
        return [
            'category' => ['category'],
            'tag' => ['tag'],
            'author' => ['author'],
            'search' => ['search'],
        ];
    }

    #[Test]
    #[DataProvider('viewModels')]
    public function implements_the_post_presenter_contract(string $kind): void
    {
        self::assertInstanceOf(PostPresenterInterface::class, $this->make($kind));
    }

    #[Test]
    #[DataProvider('viewModels')]
    public function author_name_and_url_come_from_the_author_record(string $kind): void
    {
        $this->authorRepository->method('getById')->willReturn($this->author('Jane Doe', 'jane-doe'));
        $vm = $this->make($kind);
        $post = $this->post(7);

        self::assertSame('Jane Doe', $vm->getAuthorName($post));
        self::assertSame('https://shop.test/blog/author/jane-doe', $vm->getAuthorUrl($post));
    }

    #[Test]
    #[DataProvider('viewModels')]
    public function author_url_is_null_when_the_author_has_no_slug(string $kind): void
    {
        $this->authorRepository->method('getById')->willReturn($this->author('Jane Doe', ''));

        self::assertNull($this->make($kind)->getAuthorUrl($this->post(7)));
    }

    #[Test]
    #[DataProvider('viewModels')]
    public function author_is_null_when_the_post_has_no_author(string $kind): void
    {
        $vm = $this->make($kind);

        self::assertNull($vm->getAuthorName($this->post(null)));
        self::assertNull($vm->getAuthorUrl($this->post(null)));
    }

    #[Test]
    #[DataProvider('viewModels')]
    public function author_is_null_when_the_author_record_is_gone(string $kind): void
    {
        $this->authorRepository->method('getById')->willThrowException(new NoSuchEntityException());

        self::assertNull($this->make($kind)->getAuthorName($this->post(7)));
    }

    #[Test]
    #[DataProvider('viewModels')]
    public function post_url_and_featured_image_url_are_built_the_same_way(string $kind): void
    {
        $vm = $this->make($kind);
        $post = $this->createStub(PostInterface::class);
        $post->method('getUrlKey')->willReturn('hello');
        $post->method('getFeaturedImage')->willReturn('/a/b.jpg');

        self::assertSame('https://shop.test/blog/hello', $vm->getPostUrl($post));
        self::assertSame('https://shop.test/media/mageos_blog/a/b.jpg', $vm->getFeaturedImageUrl($post));
    }

    #[Test]
    #[DataProvider('viewModels')]
    public function publish_date_is_formatted_or_empty(string $kind): void
    {
        $vm = $this->make($kind);
        $dated = $this->createStub(PostInterface::class);
        $dated->method('getPublishDate')->willReturn('2026-03-05 10:00:00');
        $undated = $this->createStub(PostInterface::class);
        $undated->method('getPublishDate')->willReturn(null);

        self::assertSame('March 5, 2026', $vm->getFormattedPublishDate($dated));
        self::assertSame('', $vm->getFormattedPublishDate($undated));
    }

    private function make(string $kind): PostPresenterInterface
    {
        $storeManager = $this->createStub(StoreManagerInterface::class);

        return match ($kind) {
            'category' => new CategoryDetail(
                $this->createStub(Registry::class),
                $this->urlBuilder,
                $this->createStub(PostsByAssignmentProvider::class),
                $storeManager,
                $this->authorRepository,
            ),
            'tag' => new TagDetail(
                $this->createStub(Registry::class),
                $this->urlBuilder,
                $this->createStub(PostsByAssignmentProvider::class),
                $storeManager,
                $this->authorRepository,
            ),
            'author' => new AuthorDetail(
                $this->createStub(Registry::class),
                $this->urlBuilder,
                $this->createStub(PostsByAssignmentProvider::class),
                $storeManager,
                $this->authorRepository,
            ),
            default => new SearchResults(
                $this->createStub(RequestInterface::class),
                $this->createStub(ResourceConnection::class),
                $this->createStub(PostRepositoryInterface::class),
                $storeManager,
                $this->createStub(Config::class),
                $this->urlBuilder,
                $this->authorRepository,
            ),
        };
    }

    private function post(?int $authorId): PostInterface&Stub
    {
        $post = $this->createStub(PostInterface::class);
        $post->method('getAuthorId')->willReturn($authorId);

        return $post;
    }

    private function author(string $name, string $slug): AuthorInterface&Stub
    {
        $author = $this->createStub(AuthorInterface::class);
        $author->method('getName')->willReturn($name);
        $author->method('getSlug')->willReturn($slug);

        return $author;
    }
}
