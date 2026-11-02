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
use MageOS\Blog\Model\Resolver\Post\AuthorResolver;
use MageOS\Blog\Model\Resolver\Post\CategoriesResolver;
use MageOS\Blog\Model\Resolver\Post\NestedEntityLoader;
use MageOS\Blog\Model\Resolver\ReadPolicy;
use MageOS\Blog\Test\Unit\Model\Resolver\ResolverTestTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class NestedEntityLoaderTest extends TestCase
{
    use ResolverTestTrait;

    #[Test]
    public function a_category_is_loaded_once_however_often_it_is_asked_for(): void
    {
        $repository = $this->createMock(CategoryRepositoryInterface::class);
        $repository->expects(self::once())->method('getById')->with(3)
            ->willReturn($this->createMock(CategoryInterface::class));

        $loader = $this->loader(categories: $repository);

        self::assertSame($loader->category(3), $loader->category(3));
    }

    #[Test]
    public function a_missing_category_is_looked_up_once_and_stays_null(): void
    {
        $repository = $this->createMock(CategoryRepositoryInterface::class);
        $repository->expects(self::once())->method('getById')
            ->willThrowException(NoSuchEntityException::singleField('categoryId', 9));

        $loader = $this->loader(categories: $repository);

        self::assertNull($loader->category(9));
        self::assertNull($loader->category(9));
    }

    #[Test]
    public function a_tag_is_loaded_once(): void
    {
        $repository = $this->createMock(TagRepositoryInterface::class);
        $repository->expects(self::once())->method('getById')->with(5)
            ->willReturn($this->createMock(TagInterface::class));

        $loader = $this->loader(tags: $repository);

        self::assertSame($loader->tag(5), $loader->tag(5));
    }

    #[Test]
    public function a_missing_tag_is_looked_up_once_and_stays_null(): void
    {
        $repository = $this->createMock(TagRepositoryInterface::class);
        $repository->expects(self::once())->method('getById')
            ->willThrowException(NoSuchEntityException::singleField('tagId', 9));

        $loader = $this->loader(tags: $repository);

        self::assertNull($loader->tag(9));
        self::assertNull($loader->tag(9));
    }

    #[Test]
    public function an_author_is_loaded_once(): void
    {
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->expects(self::once())->method('getById')->with(2)
            ->willReturn($this->createMock(AuthorInterface::class));

        $loader = $this->loader(authors: $repository);

        self::assertSame($loader->author(2), $loader->author(2));
    }

    #[Test]
    public function a_missing_author_is_looked_up_once_and_stays_null(): void
    {
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->expects(self::once())->method('getById')
            ->willThrowException(NoSuchEntityException::singleField('authorId', 9));

        $loader = $this->loader(authors: $repository);

        self::assertNull($loader->author(9));
        self::assertNull($loader->author(9));
    }

    #[Test]
    public function posts_sharing_categories_load_each_category_once_but_the_policy_runs_for_every_one(): void
    {
        $visible = $this->createMock(CategoryInterface::class);
        $hidden = $this->createMock(CategoryInterface::class);
        $repository = $this->createMock(CategoryRepositoryInterface::class);
        $repository->expects(self::exactly(2))->method('getById')->willReturnMap([[1, $visible], [2, $hidden]]);
        $mapper = $this->createMock(CategoryMapper::class);
        $mapper->method('toArray')->willReturn(['id' => 1]);
        $policy = $this->createMock(ReadPolicy::class);
        $policy->expects(self::exactly(6))->method('canReadCategory')
            ->willReturnCallback(static fn ($context, CategoryInterface $c): bool => $c === $visible);

        $resolver = new CategoriesResolver($this->loader(categories: $repository), $mapper, $policy);
        $results = [];
        for ($post = 0; $post < 3; $post++) {
            $results[] = $resolver->resolve($this->field(), $this->context(), $this->info(), ['category_ids' => [1, 2]]);
        }

        self::assertSame([[['id' => 1]], [['id' => 1]], [['id' => 1]]], $results);
    }

    #[Test]
    public function a_hidden_cached_author_stays_hidden_for_every_post(): void
    {
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->expects(self::once())->method('getById')->willReturn($this->createMock(AuthorInterface::class));
        $policy = $this->createMock(ReadPolicy::class);
        $policy->expects(self::exactly(2))->method('canReadAuthor')->willReturn(false);

        $resolver = new AuthorResolver($this->loader(authors: $repository), $this->createMock(AuthorMapper::class), $policy);

        self::assertNull($resolver->resolve($this->field(), $this->context(), $this->info(), ['author_id' => 4]));
        self::assertNull($resolver->resolve($this->field(), $this->context(), $this->info(), ['author_id' => 4]));
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
