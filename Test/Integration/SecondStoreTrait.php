<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Integration;

use Magento\Framework\App\ResourceConnection;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Resolves the store view added by the `Magento/Store/_files/store.php` fixture.
 *
 * Resolved rather than hardcoded: the *_store pivot tables carry a foreign key
 * to store.store_id, so an invented id fails the insert instead of the assertion.
 */
trait SecondStoreTrait
{
    private function secondStoreId(): int
    {
        $resource = Bootstrap::getObjectManager()->get(ResourceConnection::class);
        $connection = $resource->getConnection();

        $id = $connection->fetchOne(
            $connection->select()
                ->from($resource->getTableName('store'), ['store_id'])
                ->where('store_id > ?', 1)
                ->order('store_id ASC')
                ->limit(1)
        );

        self::assertNotFalse($id, 'The store fixture should have added a second store view.');

        return (int) $id;
    }
}
