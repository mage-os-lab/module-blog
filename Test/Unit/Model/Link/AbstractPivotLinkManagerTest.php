<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Link;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use MageOS\Blog\Model\Post\Link\CategoryLinkManager;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AbstractPivotLinkManagerTest extends TestCase
{
    #[Test]
    public function many_ids_are_fetched_with_one_query_and_grouped_by_left_id(): void
    {
        $select = $this->createMock(Select::class);
        $select->method('from')->willReturnSelf();
        $select->expects(self::once())->method('where')->with('post_id IN (?)', [1, 2, 3])->willReturnSelf();

        $connection = $this->createMock(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->expects(self::once())->method('fetchAll')->with($select)->willReturn([
            ['post_id' => '1', 'category_id' => '10'],
            ['post_id' => '1', 'category_id' => '11'],
            ['post_id' => '3', 'category_id' => '10'],
        ]);

        $resource = $this->createMock(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTableName')->willReturnArgument(0);

        self::assertSame(
            [1 => [10, 11], 2 => [], 3 => [10]],
            (new CategoryLinkManager($resource))->getLinkedIdsForMany([1, 2, 3, 2])
        );
    }

    #[Test]
    public function no_ids_means_no_query(): void
    {
        $resource = $this->createMock(ResourceConnection::class);
        $resource->expects(self::never())->method('getConnection');

        self::assertSame([], (new CategoryLinkManager($resource))->getLinkedIdsForMany([]));
    }
}
