<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\Data\AuthorInterface;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Api\Data\TagInterface;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Model\Resolver\ReadPolicy;
use MageOS\Blog\Model\Resolver\RelationFilterGuard;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RelationFilterGuardTest extends TestCase
{
    private const NO_MATCH = -1;

    private ContextInterface $context;
    private ReadPolicy $policy;
    private CategoryRepositoryInterface $categories;
    private TagRepositoryInterface $tags;
    private AuthorRepositoryInterface $authors;

    protected function setUp(): void
    {
        $this->context = $this->createMock(ContextInterface::class);
        $this->policy = $this->createMock(ReadPolicy::class);
        $this->categories = $this->createMock(CategoryRepositoryInterface::class);
        $this->tags = $this->createMock(TagRepositoryInterface::class);
        $this->authors = $this->createMock(AuthorRepositoryInterface::class);
    }

    #[Test]
    public function a_readable_category_id_is_kept(): void
    {
        $category = $this->createMock(CategoryInterface::class);
        $this->categories->method('getById')->with(3)->willReturn($category);
        $this->policy->method('canReadCategory')->with($this->context, $category)->willReturn(true);

        self::assertSame(
            ['category_id' => ['eq' => 3]],
            $this->guard()->restrict($this->context, ['category_id' => ['eq' => '3']], ['category_id' => 'category'])
        );
    }

    #[Test]
    public function a_hidden_category_id_becomes_the_same_no_match_value_as_a_missing_one(): void
    {
        $hidden = $this->createMock(CategoryInterface::class);
        $this->categories->method('getById')->willReturnCallback(
            fn (int $id) => $id === 3 ? $hidden : throw new NoSuchEntityException(__('missing'))
        );
        $this->policy->method('canReadCategory')->willReturn(false);

        $guard = $this->guard();
        $map = ['category_id' => 'category'];

        self::assertSame(
            $guard->restrict($this->context, ['category_id' => ['eq' => '999']], $map),
            $guard->restrict($this->context, ['category_id' => ['eq' => '3']], $map)
        );
        self::assertSame(
            ['category_id' => ['eq' => self::NO_MATCH]],
            $guard->restrict($this->context, ['category_id' => ['eq' => '3']], $map)
        );
    }

    #[Test]
    public function only_the_hidden_ids_of_an_in_list_are_neutralised(): void
    {
        $visible = $this->createMock(TagInterface::class);
        $hidden = $this->createMock(TagInterface::class);
        $this->tags->method('getById')->willReturnMap([[1, $visible], [2, $hidden]]);
        $this->policy->method('canReadTag')->willReturnCallback(fn ($c, $tag) => $tag === $visible);

        self::assertSame(
            ['tag_id' => ['in' => [1, self::NO_MATCH]]],
            $this->guard()->restrict($this->context, ['tag_id' => ['in' => ['1', '2']]], ['tag_id' => 'tag'])
        );
    }

    #[Test]
    public function a_repeated_id_is_loaded_once(): void
    {
        $tag = $this->createMock(TagInterface::class);
        $this->tags->expects(self::once())->method('getById')->with(1)->willReturn($tag);
        $this->policy->method('canReadTag')->willReturn(true);

        self::assertSame(
            ['tag_id' => ['in' => [1]]],
            $this->guard()->restrict($this->context, ['tag_id' => ['in' => ['1', 1, '1']]], ['tag_id' => 'tag'])
        );
    }

    #[Test]
    public function an_oversized_in_list_is_rejected_before_any_load(): void
    {
        $this->tags->expects(self::never())->method('getById');

        $this->expectException(GraphQlInputException::class);

        $this->guard()->restrict(
            $this->context,
            ['tag_id' => ['in' => range(1, RelationFilterGuard::MAX_IN_IDS + 1)]],
            ['tag_id' => 'tag']
        );
    }

    #[Test]
    public function a_list_at_the_cap_is_accepted(): void
    {
        $this->tags->method('getById')->willThrowException(new NoSuchEntityException(__('missing')));

        $result = $this->guard()->restrict(
            $this->context,
            ['tag_id' => ['in' => range(1, RelationFilterGuard::MAX_IN_IDS)]],
            ['tag_id' => 'tag']
        );

        self::assertSame([self::NO_MATCH], $result['tag_id']['in']);
    }

    #[Test]
    public function a_hidden_author_id_is_neutralised(): void
    {
        $author = $this->createMock(AuthorInterface::class);
        $this->authors->method('getById')->willReturn($author);
        $this->policy->method('canReadAuthor')->willReturn(false);

        self::assertSame(
            ['author_id' => ['eq' => self::NO_MATCH]],
            $this->guard()->restrict($this->context, ['author_id' => ['eq' => '5']], ['author_id' => 'author'])
        );
    }

    #[Test]
    public function a_non_numeric_id_matches_nothing(): void
    {
        self::assertSame(
            ['category_id' => ['eq' => self::NO_MATCH]],
            $this->guard()->restrict($this->context, ['category_id' => ['eq' => 'abc']], ['category_id' => 'category'])
        );
    }

    #[Test]
    public function the_root_parent_id_is_not_a_category_lookup(): void
    {
        $this->categories->expects(self::never())->method('getById');

        self::assertSame(
            ['parent_id' => ['eq' => 0]],
            $this->guard()->restrict($this->context, ['parent_id' => ['eq' => '0']], ['parent_id' => 'category'])
        );
    }

    #[Test]
    public function other_filters_and_unset_relations_are_untouched(): void
    {
        self::assertSame(
            ['title' => ['match' => 'x']],
            $this->guard()->restrict($this->context, ['title' => ['match' => 'x']], ['category_id' => 'category'])
        );
    }

    private function guard(): RelationFilterGuard
    {
        return new RelationFilterGuard($this->policy, $this->categories, $this->tags, $this->authors);
    }
}
