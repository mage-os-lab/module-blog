<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Resolver\Post;

use MageOS\Blog\Model\Resolver\Author\PostsResolver as AuthorPostsResolver;
use MageOS\Blog\Model\Resolver\Category\PostsResolver as CategoryPostsResolver;
use MageOS\Blog\Model\Resolver\Post\ListResolver;
use MageOS\Blog\Model\Resolver\Tag\PostsResolver as TagPostsResolver;
use MageOS\Blog\Test\Unit\Model\Resolver\ResolverTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ParentPostsResolversTest extends TestCase
{
    use ResolverTestTrait;

    /**
     * @return array<string, array{class-string, string}>
     */
    public static function parents(): array
    {
        return [
            'category' => [CategoryPostsResolver::class, 'category_ids'],
            'tag' => [TagPostsResolver::class, 'tag_ids'],
            'author' => [AuthorPostsResolver::class, 'author_id'],
        ];
    }

    #[Test]
    #[DataProvider('parents')]
    public function the_parent_id_becomes_the_constraint_and_the_client_args_pass_through(string $class, string $key): void
    {
        $context = $this->context();
        $args = ['filter' => ['status' => ['eq' => '0']], 'pageSize' => 5];
        $page = ['items' => [], 'page_info' => [], 'total_count' => 0];
        $list = $this->createMock(ListResolver::class);
        $list->expects(self::once())->method('listForParent')->with($context, $args, [$key => 42])->willReturn($page);

        $result = (new $class($list))->resolve($this->field(), $context, $this->info(), ['id' => '42'], $args);

        self::assertSame($page, $result);
    }

    #[Test]
    #[DataProvider('parents')]
    public function a_parent_without_an_id_is_constrained_to_the_id_that_matches_no_post(string $class, string $key): void
    {
        $context = $this->context();
        $list = $this->createMock(ListResolver::class);
        $list->expects(self::once())->method('listForParent')->with($context, [], [$key => 0])->willReturn([]);

        (new $class($list))->resolve($this->field(), $context, $this->info(), [], null);
    }
}
