<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Resolver\Tag;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use MageOS\Blog\Api\Data\TagSearchResultsInterface;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Model\Resolver\Mapper\TagMapper;
use MageOS\Blog\Model\Resolver\ReadPolicy;
use MageOS\Blog\Model\Resolver\SearchCriteria\FilterBuilder;
use MageOS\Blog\Model\Resolver\Tag\ListResolver;
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
        $policy->method('tagListFilters')->with($context)->willReturn(['is_active' => 1, 'store_ids' => 1]);

        $criteria = $this->createMock(SearchCriteriaInterface::class);
        $filterBuilder = $this->createMock(FilterBuilder::class);
        $filterBuilder->expects(self::once())
            ->method('build')
            ->with(['is_active' => ['eq' => '0']], null, 20, 1, self::isType('array'), ['is_active' => 1, 'store_ids' => 1])
            ->willReturn($criteria);

        $results = $this->createMock(TagSearchResultsInterface::class);
        $results->method('getItems')->willReturn([]);
        $repository = $this->createMock(TagRepositoryInterface::class);
        $repository->expects(self::once())->method('getList')->with($criteria)->willReturn($results);

        $resolver = new ListResolver($repository, $filterBuilder, $this->createMock(TagMapper::class), $policy, $this->normalizer(), $this->openGuard());
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
        $results = $this->createMock(TagSearchResultsInterface::class);
        $results->method('getItems')->willReturn([]);
        $repository = $this->createMock(TagRepositoryInterface::class);
        $repository->method('getList')->willReturn($results);
        $resolver = new ListResolver(
            $repository,
            $filterBuilder,
            $this->createMock(TagMapper::class),
            $this->createMock(ReadPolicy::class),
            $this->normalizer(),
            $this->openGuard(),
        );

        $resolver->resolve($this->field(), $this->context(), $this->info(), null, ['filter' => ['store' => ['eq' => '2']]]);

        self::assertSame(['store_id' => ['eq' => '2']], $filter);
        self::assertSame('store_ids', $map['store_id']);
    }

    #[Test]
    public function sending_both_spellings_of_the_store_filter_is_rejected(): void
    {
        $resolver = new ListResolver(
            $this->createMock(TagRepositoryInterface::class),
            $this->createMock(FilterBuilder::class),
            $this->createMock(TagMapper::class),
            $this->createMock(ReadPolicy::class),
            $this->normalizer(),
            $this->openGuard(),
        );

        $this->expectException(GraphQlInputException::class);
        $resolver->resolve($this->field(), $this->context(), $this->info(), null, ['filter' => [
            'store' => ['eq' => '1'],
            'store_id' => ['eq' => '1'],
        ]]);
    }
}
