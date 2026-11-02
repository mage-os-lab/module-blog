<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Resolver\UrlResolver;

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
