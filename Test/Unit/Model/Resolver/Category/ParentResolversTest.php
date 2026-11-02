<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Resolver\Category;

use Magento\Framework\Exception\NoSuchEntityException;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Model\Resolver\Category\ParentIdResolver;
use MageOS\Blog\Model\Resolver\Category\ParentResolver;
use MageOS\Blog\Model\Resolver\Mapper\CategoryMapper;
use MageOS\Blog\Model\Resolver\Post\NestedEntityLoader;
use MageOS\Blog\Model\Resolver\ReadPolicy;
use MageOS\Blog\Test\Unit\Model\Resolver\ResolverTestTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * A hidden or missing parent category is indistinguishable from no parent for a visitor.
 */
final class ParentResolversTest extends TestCase
{
    use ResolverTestTrait;

    #[Test]
    public function the_parent_is_mapped_when_the_caller_may_read_it(): void
    {
        $parent = $this->createMock(CategoryInterface::class);
        $repository = $this->repositoryReturning($parent);
        $mapper = $this->createMock(CategoryMapper::class);
        $mapper->method('toArray')->with($parent)->willReturn(['id' => 7]);

        $result = (new ParentResolver($this->loader($repository), $mapper, $this->policy(true)))
            ->resolve($this->field(), $this->context(), $this->info(), ['parent_id' => 7]);

        self::assertSame(['id' => 7], $result);
    }

    #[Test]
    public function a_hidden_parent_resolves_to_null(): void
    {
        $mapper = $this->createMock(CategoryMapper::class);
        $mapper->expects(self::never())->method('toArray');

        $result = (new ParentResolver($this->loader($this->repositoryReturning($this->createMock(CategoryInterface::class))), $mapper, $this->policy(false)))
            ->resolve($this->field(), $this->context(), $this->info(), ['parent_id' => 7]);

        self::assertNull($result);
    }

    #[Test]
    public function a_missing_parent_resolves_to_null(): void
    {
        $repository = $this->createMock(CategoryRepositoryInterface::class);
        $repository->method('getById')->willThrowException(NoSuchEntityException::singleField('categoryId', 7));

        $result = (new ParentResolver($this->loader($repository), $this->createMock(CategoryMapper::class), $this->createMock(ReadPolicy::class)))
            ->resolve($this->field(), $this->context(), $this->info(), ['parent_id' => 7]);

        self::assertNull($result);
    }

    #[Test]
    public function a_top_level_category_has_no_parent_and_no_lookup(): void
    {
        $repository = $this->createMock(CategoryRepositoryInterface::class);
        $repository->expects(self::never())->method('getById');
        $resolver = new ParentResolver($this->loader($repository), $this->createMock(CategoryMapper::class), $this->createMock(ReadPolicy::class));

        self::assertNull($resolver->resolve($this->field(), $this->context(), $this->info(), ['parent_id' => 0]));
        self::assertNull($resolver->resolve($this->field(), $this->context(), $this->info(), ['parent_id' => null]));
    }

    #[Test]
    public function the_parent_id_is_returned_when_the_caller_may_read_the_parent(): void
    {
        $result = (new ParentIdResolver($this->loader($this->repositoryReturning($this->createMock(CategoryInterface::class))), $this->policy(true)))
            ->resolve($this->field(), $this->context(), $this->info(), ['parent_id' => '7']);

        self::assertSame(7, $result);
    }

    #[Test]
    public function the_parent_id_is_null_when_the_parent_is_hidden(): void
    {
        $result = (new ParentIdResolver($this->loader($this->repositoryReturning($this->createMock(CategoryInterface::class))), $this->policy(false)))
            ->resolve($this->field(), $this->context(), $this->info(), ['parent_id' => 7]);

        self::assertNull($result);
    }

    #[Test]
    public function the_parent_id_is_null_when_the_parent_is_missing(): void
    {
        $repository = $this->createMock(CategoryRepositoryInterface::class);
        $repository->method('getById')->willThrowException(NoSuchEntityException::singleField('categoryId', 7));

        $result = (new ParentIdResolver($this->loader($repository), $this->createMock(ReadPolicy::class)))
            ->resolve($this->field(), $this->context(), $this->info(), ['parent_id' => 7]);

        self::assertNull($result);
    }

    #[Test]
    public function the_parent_id_of_a_top_level_category_is_kept_as_it_was(): void
    {
        $repository = $this->createMock(CategoryRepositoryInterface::class);
        $repository->expects(self::never())->method('getById');
        $resolver = new ParentIdResolver($this->loader($repository), $this->createMock(ReadPolicy::class));

        self::assertSame(0, $resolver->resolve($this->field(), $this->context(), $this->info(), ['parent_id' => 0]));
        self::assertNull($resolver->resolve($this->field(), $this->context(), $this->info(), ['parent_id' => null]));
    }

    private function policy(bool $canRead): ReadPolicy
    {
        $policy = $this->createMock(ReadPolicy::class);
        $policy->method('canReadCategory')->willReturn($canRead);

        return $policy;
    }

    private function repositoryReturning(CategoryInterface $category): CategoryRepositoryInterface
    {
        $repository = $this->createMock(CategoryRepositoryInterface::class);
        $repository->method('getById')->willReturn($category);

        return $repository;
    }

    private function loader(CategoryRepositoryInterface $categories): NestedEntityLoader
    {
        return new NestedEntityLoader(
            $categories,
            $this->createMock(TagRepositoryInterface::class),
            $this->createMock(AuthorRepositoryInterface::class),
        );
    }
}
