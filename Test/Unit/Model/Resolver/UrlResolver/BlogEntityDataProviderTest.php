<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Resolver\UrlResolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\Data\AuthorInterface;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\Data\TagInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Model\Resolver\Mapper\AuthorMapper;
use MageOS\Blog\Model\Resolver\Mapper\CategoryMapper;
use MageOS\Blog\Model\Resolver\Mapper\PostMapper;
use MageOS\Blog\Model\Resolver\Mapper\TagMapper;
use MageOS\Blog\Model\Resolver\UrlResolver\BlogEntityDataProvider;
use MageOS\Blog\Model\Resolver\UrlResolver\RouteNotSupportedException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BlogEntityDataProviderTest extends TestCase
{
    private PostRepositoryInterface $posts;
    private CategoryRepositoryInterface $categories;
    private TagRepositoryInterface $tags;
    private AuthorRepositoryInterface $authors;

    protected function setUp(): void
    {
        $this->posts = $this->createMock(PostRepositoryInterface::class);
        $this->categories = $this->createMock(CategoryRepositoryInterface::class);
        $this->tags = $this->createMock(TagRepositoryInterface::class);
        $this->authors = $this->createMock(AuthorRepositoryInterface::class);
    }

    /**
     * @return array<string, array{string, string, class-string, string}>
     */
    public static function blogTypes(): array
    {
        return [
            'post' => ['BLOG_POST', 'posts', PostInterface::class, PostMapper::class],
            'category' => ['BLOG_CATEGORY', 'categories', CategoryInterface::class, CategoryMapper::class],
            'tag' => ['BLOG_TAG', 'tags', TagInterface::class, TagMapper::class],
            'author' => ['BLOG_AUTHOR', 'authors', AuthorInterface::class, AuthorMapper::class],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function blogTypeRepositories(): array
    {
        return array_map(static fn (array $row): array => [$row[0], $row[1]], self::blogTypes());
    }

    /**
     * @param class-string $entityClass
     * @param class-string $mapperClass
     */
    #[Test]
    #[DataProvider('blogTypes')]
    public function it_returns_the_mapper_data_of_the_loaded_entity(
        string $type,
        string $repository,
        string $entityClass,
        string $mapperClass,
    ): void {
        $entity = $this->createMock($entityClass);
        $this->{$repository}->expects(self::once())->method('getById')->with(7)->willReturn($entity);
        $mapper = $this->createMock($mapperClass);
        $mapper->expects(self::once())->method('toArray')->with($entity)->willReturn(['id' => 7, 'url_key' => 'x']);

        self::assertSame(
            ['id' => 7, 'url_key' => 'x', 'type_id' => $type],
            $this->provider($mapper)->getData($type, 7)
        );
    }

    #[Test]
    #[DataProvider('blogTypeRepositories')]
    public function a_missing_entity_is_not_routable(string $type, string $repository): void
    {
        $this->{$repository}->method('getById')->willThrowException(NoSuchEntityException::singleField('id', 7));

        $this->expectException(RouteNotSupportedException::class);

        $this->provider()->getData($type, 7);
    }

    #[Test]
    public function any_failure_loading_the_entity_is_not_routable(): void
    {
        $this->posts->method('getById')->willThrowException(new LocalizedException(__('Database went away.')));

        $this->expectException(RouteNotSupportedException::class);

        $this->provider()->getData('BLOG_POST', 7);
    }

    #[Test]
    public function the_entity_type_is_matched_case_insensitively(): void
    {
        $this->posts->expects(self::once())->method('getById')->with(7)
            ->willReturn($this->createMock(PostInterface::class));

        self::assertArrayHasKey('id', $this->provider()->getData('blog_post', 7));
    }

    #[Test]
    public function an_unknown_entity_type_is_not_routable(): void
    {
        $this->expectException(RouteNotSupportedException::class);

        $this->provider()->getData('PRODUCT', 7);
    }

    private function provider(?object $mapper = null): BlogEntityDataProvider
    {
        return new BlogEntityDataProvider(
            $this->posts,
            $this->categories,
            $this->tags,
            $this->authors,
            $mapper instanceof PostMapper ? $mapper : new PostMapper(),
            $mapper instanceof CategoryMapper ? $mapper : new CategoryMapper(),
            $mapper instanceof TagMapper ? $mapper : new TagMapper(),
            $mapper instanceof AuthorMapper ? $mapper : new AuthorMapper(),
        );
    }
}
