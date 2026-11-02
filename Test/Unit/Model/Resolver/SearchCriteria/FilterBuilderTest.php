<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Resolver\SearchCriteria;

use Magento\Framework\Api\FilterBuilder as ApiFilterBuilder;
use Magento\Framework\Api\ObjectFactory;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\ObjectManagerInterface;
use MageOS\Blog\Model\Resolver\SearchCriteria\FilterBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FilterBuilderTest extends TestCase
{
    private const FIELD_MAP = ['status' => 'status', 'title' => 'title', 'id' => 'post_id'];

    #[Test]
    public function client_filters_become_one_group_per_field(): void
    {
        $criteria = $this->builder()->build(
            ['status' => ['eq' => '2'], 'title' => ['match' => 'magento']],
            null,
            20,
            1,
            self::FIELD_MAP,
        );

        self::assertSame(
            [
                [['status', 'eq', '2']],
                [['title', 'like', '%magento%']],
            ],
            $this->filterGroupsOf($criteria)
        );
    }

    #[Test]
    public function required_filters_apply_when_the_client_sends_no_filter(): void
    {
        $criteria = $this->builder()->build(null, null, 20, 1, self::FIELD_MAP, ['status' => 2, 'store_ids' => 3]);

        self::assertSame(
            [
                [['status', 'eq', 2]],
                [['store_ids', 'eq', 3]],
            ],
            $this->filterGroupsOf($criteria)
        );
    }

    #[Test]
    public function a_required_filter_never_shares_a_group_with_a_client_filter_on_the_same_field(): void
    {
        // Filters inside one group are OR-ed, separate groups are AND-ed. If the required
        // status shared a group with the client's, asking for drafts would widen the result.
        $criteria = $this->builder()->build(
            ['status' => ['eq' => '0']],
            null,
            20,
            1,
            self::FIELD_MAP,
            ['status' => 2],
        );

        self::assertSame(
            [
                [['status', 'eq', '0']],
                [['status', 'eq', 2]],
            ],
            $this->filterGroupsOf($criteria)
        );
    }

    #[Test]
    public function required_filters_ignore_the_client_field_map(): void
    {
        $criteria = $this->builder()->build(['id' => ['eq' => '5']], null, 20, 1, self::FIELD_MAP, ['store_ids' => 1]);

        self::assertSame(
            [
                [['post_id', 'eq', '5']],
                [['store_ids', 'eq', 1]],
            ],
            $this->filterGroupsOf($criteria)
        );
    }

    #[Test]
    public function pagination_and_sorting_are_kept_alongside_required_filters(): void
    {
        $criteria = $this->builder()->build(null, ['title' => 'DESC'], 7, 3, self::FIELD_MAP, ['status' => 2]);

        self::assertSame(7, $criteria->getPageSize());
        self::assertSame(3, $criteria->getCurrentPage());
        self::assertSame('title', $criteria->getSortOrders()[0]->getField());
        self::assertSame('DESC', $criteria->getSortOrders()[0]->getDirection());
    }

    /**
     * @return list<list<array{string, string, mixed}>>
     */
    private function filterGroupsOf(SearchCriteriaInterface $criteria): array
    {
        $groups = [];
        foreach ($criteria->getFilterGroups() as $group) {
            $filters = [];
            foreach ($group->getFilters() as $filter) {
                $filters[] = [$filter->getField(), $filter->getConditionType(), $filter->getValue()];
            }
            $groups[] = $filters;
        }

        return $groups;
    }

    private function builder(): FilterBuilder
    {
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->method('create')->willReturnCallback(
            static fn (string $type, array $arguments = []): object => new $type($arguments['data'] ?? [])
        );
        $objectFactory = new ObjectFactory($objectManager);
        $apiFilterBuilder = new ApiFilterBuilder($objectFactory);

        return new FilterBuilder(
            new SearchCriteriaBuilder($objectFactory, new FilterGroupBuilder($objectFactory, $apiFilterBuilder), $apiFilterBuilder),
            $apiFilterBuilder,
            new SortOrderBuilder($objectFactory),
        );
    }
}
