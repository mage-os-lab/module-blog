<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Resolver\Tag;

use Magento\Framework\Api\SearchCriteriaInterface;
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

        $resolver = new ListResolver($repository, $filterBuilder, $this->createMock(TagMapper::class), $policy);
        $resolver->resolve($this->field(), $context, $this->info(), null, ['filter' => ['is_active' => ['eq' => '0']]]);
    }
}
