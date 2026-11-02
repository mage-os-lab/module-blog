<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Url;

use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use MageOS\Blog\Model\Url\StaleStoreRewriteCleaner;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class StaleStoreRewriteCleanerTest extends TestCase
{
    #[Test]
    public function deletes_all_rows_of_stores_without_a_new_row_in_one_call(): void
    {
        $persist = $this->createMock(UrlPersistInterface::class);
        $persist->expects(self::once())->method('deleteByData')->with([
            UrlRewrite::ENTITY_TYPE => 'blog_post',
            UrlRewrite::ENTITY_ID => 7,
            UrlRewrite::STORE_ID => [3, 4],
        ]);

        $cleaner = new StaleStoreRewriteCleaner($persist, $this->finderReturningStores([1, 3, 3, 4]));
        $cleaner->deleteOutsideNewRows('blog_post', 7, [$this->row(1)]);
    }

    #[Test]
    public function deletes_every_row_when_the_entity_has_no_new_rows_at_all(): void
    {
        $persist = $this->createMock(UrlPersistInterface::class);
        $persist->expects(self::once())->method('deleteByData')->with([
            UrlRewrite::ENTITY_TYPE => 'blog_post',
            UrlRewrite::ENTITY_ID => 7,
            UrlRewrite::STORE_ID => [1, 2],
        ]);

        $cleaner = new StaleStoreRewriteCleaner($persist, $this->finderReturningStores([1, 2]));
        $cleaner->deleteOutsideNewRows('blog_post', 7, []);
    }

    #[Test]
    public function deletes_nothing_when_every_existing_store_has_a_new_row(): void
    {
        $persist = $this->createMock(UrlPersistInterface::class);
        $persist->expects(self::never())->method('deleteByData');

        $cleaner = new StaleStoreRewriteCleaner($persist, $this->finderReturningStores([1, 2]));
        $cleaner->deleteOutsideNewRows('blog_post', 7, [$this->row(1), $this->row(2), $this->row(5)]);
    }

    #[Test]
    public function deletes_nothing_for_an_entity_without_rows(): void
    {
        $persist = $this->createMock(UrlPersistInterface::class);
        $persist->expects(self::never())->method('deleteByData');

        $cleaner = new StaleStoreRewriteCleaner($persist, $this->finderReturningStores([]));
        $cleaner->deleteOutsideNewRows('blog_tag', 1, [$this->row(1)]);
    }

    /**
     * @param int[] $storeIds
     */
    private function finderReturningStores(array $storeIds): UrlFinderInterface
    {
        $finder = $this->createStub(UrlFinderInterface::class);
        $finder->method('findAllByData')->willReturn(array_map($this->row(...), $storeIds));

        return $finder;
    }

    private function row(int $storeId): UrlRewrite
    {
        $row = $this->createStub(UrlRewrite::class);
        $row->method('getStoreId')->willReturn($storeId);

        return $row;
    }
}
