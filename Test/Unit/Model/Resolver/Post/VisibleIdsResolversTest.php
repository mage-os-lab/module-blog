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
use MageOS\Blog\Model\Resolver\Post\AuthorIdResolver;
use MageOS\Blog\Model\Resolver\Post\CategoryIdsResolver;
use MageOS\Blog\Model\Resolver\Post\NestedEntityLoader;
use MageOS\Blog\Model\Resolver\Post\TagIdsResolver;
use MageOS\Blog\Model\Resolver\ReadPolicy;
use MageOS\Blog\Test\Unit\Model\Resolver\ResolverTestTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The deprecated raw id fields of a post must not point a visitor at hidden or missing entities.
 */
final class VisibleIdsResolversTest extends TestCase
{
    use ResolverTestTrait;

    #[Test]
    public function category_ids_leave_out_hidden_and_missing_categories(): void
    {
        $visible = $this->createMock(CategoryInterface::class);
        $hidden = $this->createMock(CategoryInterface::class);
        $repository = $this->createMock(CategoryRepositoryInterface::class);
        $repository->method('getById')->willReturnCallback(
            static fn (int $id): CategoryInterface => match ($id) {
                1 => $visible,
                2 => $hidden,
                default => throw NoSuchEntityException::singleField('categoryId', $id),
            }
        );
        $policy = $this->createMock(ReadPolicy::class);
        $policy->method('canReadCategory')->willReturnCallback(static fn ($context, CategoryInterface $c): bool => $c === $visible);

        $result = (new CategoryIdsResolver($this->loader(categories: $repository), $policy))
            ->resolve($this->field(), $this->context(), $this->info(), ['category_ids' => [1, 2, 99]]);

        self::assertSame([1], $result);
    }

    #[Test]
    public function category_ids_keep_every_readable_id_in_order(): void
    {
        $repository = $this->createMock(CategoryRepositoryInterface::class);
        $repository->method('getById')->willReturn($this->createMock(CategoryInterface::class));
        $policy = $this->createMock(ReadPolicy::class);
        $policy->method('canReadCategory')->willReturn(true);

        $result = (new CategoryIdsResolver($this->loader(categories: $repository), $policy))
            ->resolve($this->field(), $this->context(), $this->info(), ['category_ids' => ['3', 1]]);

        self::assertSame([3, 1], $result);
    }

    #[Test]
    public function category_ids_are_empty_without_ids(): void
    {
        $result = (new CategoryIdsResolver($this->loader(), $this->createMock(ReadPolicy::class)))
            ->resolve($this->field(), $this->context(), $this->info(), []);

        self::assertSame([], $result);
    }

    #[Test]
    public function tag_ids_leave_out_hidden_and_missing_tags(): void
    {
        $visible = $this->createMock(TagInterface::class);
        $hidden = $this->createMock(TagInterface::class);
        $repository = $this->createMock(TagRepositoryInterface::class);
        $repository->method('getById')->willReturnCallback(
            static fn (int $id): TagInterface => match ($id) {
                1 => $visible,
                2 => $hidden,
                default => throw NoSuchEntityException::singleField('tagId', $id),
            }
        );
        $policy = $this->createMock(ReadPolicy::class);
        $policy->method('canReadTag')->willReturnCallback(static fn ($context, TagInterface $t): bool => $t === $visible);

        $result = (new TagIdsResolver($this->loader(tags: $repository), $policy))
            ->resolve($this->field(), $this->context(), $this->info(), ['tag_ids' => [2, 1, 99]]);

        self::assertSame([1], $result);
    }

    #[Test]
    public function the_author_id_is_null_when_the_caller_may_not_read_the_author(): void
    {
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->method('getById')->willReturn($this->createMock(AuthorInterface::class));
        $policy = $this->createMock(ReadPolicy::class);
        $policy->method('canReadAuthor')->willReturn(false);

        $result = (new AuthorIdResolver($this->loader(authors: $repository), $policy))
            ->resolve($this->field(), $this->context(), $this->info(), ['author_id' => 4]);

        self::assertNull($result);
    }

    #[Test]
    public function the_author_id_is_null_when_the_author_no_longer_exists(): void
    {
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->method('getById')->willThrowException(NoSuchEntityException::singleField('authorId', 4));

        $result = (new AuthorIdResolver($this->loader(authors: $repository), $this->createMock(ReadPolicy::class)))
            ->resolve($this->field(), $this->context(), $this->info(), ['author_id' => 4]);

        self::assertNull($result);
    }

    #[Test]
    public function the_author_id_is_returned_when_the_caller_may_read_the_author(): void
    {
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->method('getById')->willReturn($this->createMock(AuthorInterface::class));
        $policy = $this->createMock(ReadPolicy::class);
        $policy->method('canReadAuthor')->willReturn(true);

        $result = (new AuthorIdResolver($this->loader(authors: $repository), $policy))
            ->resolve($this->field(), $this->context(), $this->info(), ['author_id' => '4']);

        self::assertSame(4, $result);
    }

    #[Test]
    public function a_post_without_an_author_has_a_null_author_id_without_a_lookup(): void
    {
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->expects(self::never())->method('getById');

        $result = (new AuthorIdResolver($this->loader(authors: $repository), $this->createMock(ReadPolicy::class)))
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
