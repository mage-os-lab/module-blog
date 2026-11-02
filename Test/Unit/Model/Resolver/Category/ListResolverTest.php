<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Resolver\Category;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\Data\CategorySearchResultsInterface;
use MageOS\Blog\Model\Resolver\Category\ListResolver;
use MageOS\Blog\Model\Resolver\Hydrator\LinkHydrator;
use MageOS\Blog\Model\Resolver\Mapper\CategoryMapper;
use MageOS\Blog\Model\Resolver\ReadPolicy;
use MageOS\Blog\Model\Resolver\RelationFilterGuard;
use MageOS\Blog\Model\Resolver\SearchCriteria\FilterBuilder;
use MageOS\Blog\Model\Resolver\SearchCriteria\PageArguments;
use MageOS\Blog\Test\Unit\Model\Resolver\ResolverTestTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ListResolverTest extends TestCase
{
    use ResolverTestTrait;

    #[Test]
    public function the_read_policy_filters_are_required_on_the_criteria(): void
    {
        $context = $this->context();
        $policy = $this->createMock(ReadPolicy::class);
        $policy->method('categoryListFilters')->with($context)->willReturn(['is_active' => 1, 'store_ids' => 1]);

        $criteria = $this->createMock(SearchCriteriaInterface::class);
        $filterBuilder = $this->createMock(FilterBuilder::class);
        $filterBuilder->expects(self::once())
            ->method('build')
            ->with(['is_active' => ['eq' => '0']], null, 20, 1, self::isType('array'), ['is_active' => 1, 'store_ids' => 1])
            ->willReturn($criteria);

        $results = $this->createMock(CategorySearchResultsInterface::class);
        $results->method('getItems')->willReturn([]);
        $repository = $this->createMock(CategoryRepositoryInterface::class);
        $repository->expects(self::once())->method('getList')->with($criteria)->willReturn($results);

        $resolver = new ListResolver($repository, $filterBuilder, $this->createMock(CategoryMapper::class), $policy, new PageArguments(), $this->createMock(LinkHydrator::class), $this->normalizer(), $this->openGuard());
        $resolver->resolve($this->field(), $context, $this->info(), null, ['filter' => ['is_active' => ['eq' => '0']]]);
    }

    #[Test]
    public function the_unsuffixed_keys_reach_the_filter_builder_as_the_legacy_keys_and_store_maps_to_store_ids(): void
    {
        $filter = null;
        $map = null;
        $filterBuilder = $this->createMock(FilterBuilder::class);
        $filterBuilder->method('build')->willReturnCallback(
            function ($f, $s, $p, $c, $fieldMap) use (&$filter, &$map): SearchCriteriaInterface {
                $filter = $f;
                $map = $fieldMap;

                return $this->createMock(SearchCriteriaInterface::class);
            }
        );
        $results = $this->createMock(CategorySearchResultsInterface::class);
        $results->method('getItems')->willReturn([]);
        $repository = $this->createMock(CategoryRepositoryInterface::class);
        $repository->method('getList')->willReturn($results);
        $resolver = new ListResolver(
            $repository,
            $filterBuilder,
            $this->createMock(CategoryMapper::class),
            $this->createMock(ReadPolicy::class),
            new PageArguments(),
            $this->createMock(LinkHydrator::class),
            $this->normalizer(),
            $this->openGuard(),
        );

        $resolver->resolve($this->field(), $this->context(), $this->info(), null, ['filter' => ['store' => ['eq' => '2'], 'parent' => ['eq' => '7']]]);

        self::assertSame(['store_id' => ['eq' => '2'], 'parent_id' => ['eq' => '7']], $filter);
        self::assertSame('store_ids', $map['store_id']);
    }

    #[Test]
    public function sending_both_spellings_of_the_store_filter_is_rejected(): void
    {
        $resolver = new ListResolver(
            $this->createMock(CategoryRepositoryInterface::class),
            $this->createMock(FilterBuilder::class),
            $this->createMock(CategoryMapper::class),
            $this->createMock(ReadPolicy::class),
            new PageArguments(),
            $this->createMock(LinkHydrator::class),
            $this->normalizer(),
            $this->openGuard(),
        );

        $this->expectException(GraphQlInputException::class);
        $resolver->resolve($this->field(), $this->context(), $this->info(), null, ['filter' => [
            'store' => ['eq' => '1'],
            'store_id' => ['eq' => '1'],
        ]]);
    }

    #[Test]
    public function the_parent_filter_goes_through_the_relation_guard(): void
    {
        $context = $this->context();
        $guard = $this->createMock(RelationFilterGuard::class);
        $guard->expects(self::once())->method('restrict')
            ->with($context, ['parent_id' => ['eq' => '7']], ['parent_id' => 'category'])
            ->willReturn(['parent_id' => ['eq' => -1]]);
        $filterBuilder = $this->createMock(FilterBuilder::class);
        $filterBuilder->expects(self::once())->method('build')
            ->with(['parent_id' => ['eq' => -1]], null, 20, 1, self::isType('array'), self::anything())
            ->willReturn($this->createMock(SearchCriteriaInterface::class));
        $results = $this->createMock(CategorySearchResultsInterface::class);
        $results->method('getItems')->willReturn([]);
        $repository = $this->createMock(CategoryRepositoryInterface::class);
        $repository->method('getList')->willReturn($results);

        (new ListResolver($repository, $filterBuilder, $this->createMock(CategoryMapper::class), $this->createMock(ReadPolicy::class), new PageArguments(), $this->createMock(LinkHydrator::class), $this->normalizer(), $guard))
            ->resolve($this->field(), $context, $this->info(), null, ['filter' => ['parent' => ['eq' => '7']]]);
    }
}
