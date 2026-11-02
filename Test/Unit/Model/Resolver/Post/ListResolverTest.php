<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Resolver\Post;

use Magento\Framework\Api\SearchCriteriaInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\Data\PostSearchResultsInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Model\Resolver\Mapper\PostMapper;
use MageOS\Blog\Model\Resolver\Post\ListResolver;
use MageOS\Blog\Model\Resolver\ReadPolicy;
use MageOS\Blog\Model\Resolver\SearchCriteria\FilterBuilder;
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
        $policy->method('postListFilters')->with($context)->willReturn(['status' => 2, 'store_ids' => 1]);

        $criteria = $this->createMock(SearchCriteriaInterface::class);
        $filterBuilder = $this->createMock(FilterBuilder::class);
        $filterBuilder->expects(self::once())
            ->method('build')
            ->with(['status' => ['eq' => '0']], null, 20, 1, self::isType('array'), ['status' => 2, 'store_ids' => 1])
            ->willReturn($criteria);

        $repository = $this->createMock(PostRepositoryInterface::class);
        $repository->expects(self::once())->method('getList')->with($criteria)->willReturn($this->results([], 0));

        $resolver = new ListResolver($repository, $filterBuilder, $this->createMock(PostMapper::class), $policy);
        $resolver->resolve($this->field(), $context, $this->info(), null, ['filter' => ['status' => ['eq' => '0']]]);
    }

    #[Test]
    public function it_maps_the_items_and_reports_the_pagination(): void
    {
        $post = $this->createMock(PostInterface::class);
        $mapper = $this->createMock(PostMapper::class);
        $mapper->method('toArray')->with($post)->willReturn(['id' => 5]);
        $filterBuilder = $this->createMock(FilterBuilder::class);
        $filterBuilder->method('build')->willReturn($this->createMock(SearchCriteriaInterface::class));
        $repository = $this->createMock(PostRepositoryInterface::class);
        $repository->method('getList')->willReturn($this->results([$post], 45));

        $result = (new ListResolver($repository, $filterBuilder, $mapper, $this->createMock(ReadPolicy::class)))
            ->resolve($this->field(), $this->context(), $this->info(), null, ['pageSize' => 20, 'currentPage' => 2]);

        self::assertSame(
            [
                'items' => [['id' => 5]],
                'page_info' => ['page_size' => 20, 'current_page' => 2, 'total_pages' => 3],
                'total_count' => 45,
            ],
            $result
        );
    }

    /**
     * @param PostInterface[] $items
     */
    private function results(array $items, int $total): PostSearchResultsInterface
    {
        $results = $this->createMock(PostSearchResultsInterface::class);
        $results->method('getItems')->willReturn($items);
        $results->method('getTotalCount')->willReturn($total);

        return $results;
    }
}
