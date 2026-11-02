<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\SearchCriteria;

use Magento\Framework\Api\Filter;
use MageOS\Blog\Model\ResourceModel\Category\Collection as CategoryCollection;
use MageOS\Blog\Model\ResourceModel\Post\Collection as PostCollection;
use MageOS\Blog\Model\SearchCriteria\CategoryFilter;
use MageOS\Blog\Model\SearchCriteria\TagFilter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PostRelationFilterTest extends TestCase
{
    #[Test]
    public function the_category_filter_restricts_posts_to_the_given_category_ids(): void
    {
        $collection = $this->createMock(PostCollection::class);
        $collection->expects(self::once())->method('addCategoryFilter')->with([4, 7]);

        self::assertTrue((new CategoryFilter())->apply($this->filter('category_ids', 'in', [4, '7']), $collection));
    }

    #[Test]
    public function the_category_filter_accepts_a_single_id_and_a_comma_list(): void
    {
        $seen = [];
        $collection = $this->createMock(PostCollection::class);
        $collection->method('addCategoryFilter')->willReturnCallback(
            function (array $ids) use (&$seen, $collection): PostCollection {
                $seen[] = $ids;

                return $collection;
            }
        );

        $filter = new CategoryFilter();
        $filter->apply($this->filter('category_ids', 'eq', '4'), $collection);
        $filter->apply($this->filter('category_ids', 'in', '4,7'), $collection);

        self::assertSame([[4], [4, 7]], $seen);
    }

    #[Test]
    public function the_tag_filter_restricts_posts_to_the_given_tag_ids(): void
    {
        $collection = $this->createMock(PostCollection::class);
        $collection->expects(self::once())->method('addTagFilter')->with([9]);

        self::assertTrue((new TagFilter())->apply($this->filter('tag_ids', 'eq', '9'), $collection));
    }

    #[Test]
    public function the_filters_leave_other_collections_alone(): void
    {
        $collection = $this->createMock(CategoryCollection::class);

        self::assertFalse((new CategoryFilter())->apply($this->filter('category_ids', 'eq', '1'), $collection));
        self::assertFalse((new TagFilter())->apply($this->filter('tag_ids', 'eq', '1'), $collection));
    }

    #[Test]
    public function the_relation_filters_are_registered_for_the_repositories(): void
    {
        $di = (string) file_get_contents(__DIR__ . '/../../../../etc/di.xml');

        self::assertStringContainsString('<item name="category_ids" xsi:type="object">MageOS\Blog\Model\SearchCriteria\CategoryFilter</item>', $di);
        self::assertStringContainsString('<item name="tag_ids" xsi:type="object">MageOS\Blog\Model\SearchCriteria\TagFilter</item>', $di);
    }

    /**
     * @param array<int|string>|string $value
     */
    private function filter(string $field, string $condition, array|string $value): Filter
    {
        return (new Filter())->setField($field)->setConditionType($condition)->setValue($value);
    }
}
