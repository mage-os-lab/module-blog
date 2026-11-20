<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Resolver\Post;

use Magento\Framework\Exception\NoSuchEntityException;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\Data\AuthorInterface;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Api\Data\TagInterface;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Model\Resolver\Mapper\AuthorMapper;
use MageOS\Blog\Model\Resolver\Mapper\CategoryMapper;
use MageOS\Blog\Model\Resolver\Mapper\TagMapper;
use MageOS\Blog\Model\Resolver\Post\AuthorResolver;
use MageOS\Blog\Model\Resolver\Post\CategoriesResolver;
use MageOS\Blog\Model\Resolver\Post\NestedEntityLoader;
use MageOS\Blog\Model\Resolver\Post\TagsResolver;
use MageOS\Blog\Model\Resolver\ReadPolicy;
use MageOS\Blog\Test\Unit\Model\Resolver\ResolverTestTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * A published post must not leak the hidden categories, tags or author attached to it.
 */
final class NestedResolversTest extends TestCase
{
    use ResolverTestTrait;

    #[Test]
    public function categories_leave_out_the_ones_the_caller_may_not_read(): void
    {
        $visible = $this->createMock(CategoryInterface::class);
        $hidden = $this->createMock(CategoryInterface::class);
        $repository = $this->createMock(CategoryRepositoryInterface::class);
        $repository->method('getById')->willReturnMap([[1, $visible], [2, $hidden]]);
        $mapper = $this->createMock(CategoryMapper::class);
        $mapper->method('toArray')->willReturnCallback(static fn (CategoryInterface $c): array => ['id' => $c === $visible ? 1 : 2]);
        $policy = $this->createMock(ReadPolicy::class);
        $policy->method('canReadCategory')->willReturnCallback(static fn ($context, CategoryInterface $c): bool => $c === $visible);

        $result = (new CategoriesResolver($this->loader(categories: $repository), $mapper, $policy))
            ->resolve($this->field(), $this->context(), $this->info(), ['category_ids' => [1, 2]]);

        self::assertSame([['id' => 1]], $result);
    }

    #[Test]
    public function categories_still_skip_ids_that_no_longer_exist(): void
    {
        $category = $this->createMock(CategoryInterface::class);
        $repository = $this->createMock(CategoryRepositoryInterface::class);
        $repository->method('getById')->willReturnCallback(
            static fn (int $id): CategoryInterface => $id === 1
                ? $category
                : throw NoSuchEntityException::singleField('categoryId', $id)
        );
        $mapper = $this->createMock(CategoryMapper::class);
        $mapper->method('toArray')->willReturn(['id' => 1]);
        $policy = $this->createMock(ReadPolicy::class);
        $policy->method('canReadCategory')->willReturn(true);

        $result = (new CategoriesResolver($this->loader(categories: $repository), $mapper, $policy))
            ->resolve($this->field(), $this->context(), $this->info(), ['category_ids' => [1, 99]]);

        self::assertSame([['id' => 1]], $result);
    }

    #[Test]
    public function tags_leave_out_the_ones_the_caller_may_not_read(): void
    {
        $visible = $this->createMock(TagInterface::class);
        $hidden = $this->createMock(TagInterface::class);
        $repository = $this->createMock(TagRepositoryInterface::class);
        $repository->method('getById')->willReturnMap([[1, $visible], [2, $hidden]]);
        $mapper = $this->createMock(TagMapper::class);
        $mapper->method('toArray')->willReturnCallback(static fn (TagInterface $t): array => ['id' => $t === $visible ? 1 : 2]);
        $policy = $this->createMock(ReadPolicy::class);
        $policy->method('canReadTag')->willReturnCallback(static fn ($context, TagInterface $t): bool => $t === $visible);

        $result = (new TagsResolver($this->loader(tags: $repository), $mapper, $policy))
            ->resolve($this->field(), $this->context(), $this->info(), ['tag_ids' => [1, 2]]);

        self::assertSame([['id' => 1]], $result);
    }

    #[Test]
    public function tags_still_skip_ids_that_no_longer_exist(): void
    {
        $repository = $this->createMock(TagRepositoryInterface::class);
        $repository->method('getById')->willThrowException(NoSuchEntityException::singleField('tagId', 9));
        $policy = $this->createMock(ReadPolicy::class);

        $result = (new TagsResolver($this->loader(tags: $repository), $this->createMock(TagMapper::class), $policy))
            ->resolve($this->field(), $this->context(), $this->info(), ['tag_ids' => [9]]);

        self::assertSame([], $result);
    }

    #[Test]
    public function the_author_is_null_when_the_caller_may_not_read_it(): void
    {
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->method('getById')->willReturn($this->createMock(AuthorInterface::class));
        $mapper = $this->createMock(AuthorMapper::class);
        $mapper->expects(self::never())->method('toArray');
        $policy = $this->createMock(ReadPolicy::class);
        $policy->method('canReadAuthor')->willReturn(false);

        $result = (new AuthorResolver($this->loader(authors: $repository), $mapper, $policy))
            ->resolve($this->field(), $this->context(), $this->info(), ['author_id' => 4]);

        self::assertNull($result);
    }

    #[Test]
    public function the_author_is_mapped_when_the_caller_may_read_it(): void
    {
        $author = $this->createMock(AuthorInterface::class);
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->method('getById')->with(4)->willReturn($author);
        $mapper = $this->createMock(AuthorMapper::class);
        $mapper->method('toArray')->with($author)->willReturn(['id' => 4]);
        $policy = $this->createMock(ReadPolicy::class);
        $policy->method('canReadAuthor')->willReturn(true);

        $result = (new AuthorResolver($this->loader(authors: $repository), $mapper, $policy))
            ->resolve($this->field(), $this->context(), $this->info(), ['author_id' => 4]);

        self::assertSame(['id' => 4], $result);
    }

    #[Test]
    public function a_post_without_an_author_resolves_to_null_without_a_lookup(): void
    {
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->expects(self::never())->method('getById');

        $result = (new AuthorResolver($this->loader(authors: $repository), $this->createMock(AuthorMapper::class), $this->createMock(ReadPolicy::class)))
            ->resolve($this->field(), $this->context(), $this->info(), ['author_id' => null]);

        self::assertNull($result);
    }

    private function loader(
        ?CategoryRepositoryInterface $categories = null,
        ?TagRepositoryInterface $tags = null,
        ?AuthorRepositoryInterface $authors = null,
    ): NestedEntityLoader {
        return new NestedEntityLoader(
            $categories ?? $this->createMock(CategoryRepositoryInterface::class),
            $tags ?? $this->createMock(TagRepositoryInterface::class),
            $authors ?? $this->createMock(AuthorRepositoryInterface::class),
        );
    }
}
