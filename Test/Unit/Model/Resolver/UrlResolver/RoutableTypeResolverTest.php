<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Resolver\UrlResolver;

use Magento\UrlRewriteGraphQl\Model\RoutableInterfaceTypeResolver;
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
use MageOS\Blog\Model\Resolver\UrlResolver\RoutableTypeResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RoutableTypeResolverTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function types(): array
    {
        return [
            'post' => ['BLOG_POST', 'BlogPost'],
            'category' => ['BLOG_CATEGORY', 'BlogCategory'],
            'tag' => ['BLOG_TAG', 'BlogTag'],
            'author' => ['BLOG_AUTHOR', 'BlogAuthor'],
            'lower case' => ['blog_post', 'BlogPost'],
        ];
    }

    #[Test]
    #[DataProvider('types')]
    public function it_maps_the_url_type_to_the_graphql_type(string $urlType, string $expected): void
    {
        self::assertSame($expected, (new RoutableTypeResolver())->resolveType(['type' => $urlType]));
    }

    #[Test]
    #[DataProvider('types')]
    public function it_maps_the_type_id_core_passes_to_the_graphql_type(string $urlType, string $expected): void
    {
        self::assertSame($expected, (new RoutableTypeResolver())->resolveType(['type_id' => $urlType]));
    }

    #[Test]
    #[DataProvider('types')]
    public function core_routable_resolver_resolves_provider_output_plus_route_additions(
        string $urlType,
        string $expected
    ): void {
        $repos = [
            'BLOG_POST' => [PostRepositoryInterface::class, PostInterface::class],
            'BLOG_CATEGORY' => [CategoryRepositoryInterface::class, CategoryInterface::class],
            'BLOG_TAG' => [TagRepositoryInterface::class, TagInterface::class],
            'BLOG_AUTHOR' => [AuthorRepositoryInterface::class, AuthorInterface::class],
        ];
        $post = $this->createMock(PostRepositoryInterface::class);
        $category = $this->createMock(CategoryRepositoryInterface::class);
        $tag = $this->createMock(TagRepositoryInterface::class);
        $author = $this->createMock(AuthorRepositoryInterface::class);
        $mocks = ['BLOG_POST' => $post, 'BLOG_CATEGORY' => $category, 'BLOG_TAG' => $tag, 'BLOG_AUTHOR' => $author];
        $key = strtoupper($urlType);
        $mocks[$key]->method('getById')->willReturn($this->createMock($repos[$key][1]));
        $provider = new BlogEntityDataProvider(
            $post,
            $category,
            $tag,
            $author,
            new PostMapper(),
            new CategoryMapper(),
            new TagMapper(),
            new AuthorMapper(),
        );

        // Core Route adds redirect_code, relative_url and type on top of what the provider returns.
        $data = [
            ...$provider->getData($urlType, 7),
            'redirect_code' => 0,
            'relative_url' => 'blog/x',
            'type' => $urlType,
        ];
        $core = new RoutableInterfaceTypeResolver(['blog' => new RoutableTypeResolver()]);

        self::assertSame($expected, $core->resolveType($data));
    }

    #[Test]
    public function other_modules_types_are_left_to_their_own_resolvers(): void
    {
        self::assertSame('', (new RoutableTypeResolver())->resolveType(['type' => 'PRODUCT']));
    }

    #[Test]
    public function data_without_a_type_is_left_to_the_default_type(): void
    {
        self::assertSame('', (new RoutableTypeResolver())->resolveType([]));
    }
}
