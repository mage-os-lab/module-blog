<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Url;

use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

/**
 * UrlPersist::replace() only clears rows for the entity/store pairs it is given, so rows on stores
 * an entity was removed from must be deleted explicitly.
 */
class StaleStoreRewriteCleaner
{
    public function __construct(
        private readonly UrlPersistInterface $urlPersist,
        private readonly UrlFinderInterface $urlFinder,
    ) {
    }

    /**
     * Delete every rewrite (including 301 redirects) of the entity on stores that have no new row.
     *
     * @param UrlRewrite[] $newRows rows built for the entity's expanded store set
     */
    public function deleteOutsideNewRows(string $entityType, int $entityId, array $newRows): void
    {
        $keep = [];
        foreach ($newRows as $row) {
            $keep[(int) $row->getStoreId()] = true;
        }

        $stale = [];
        $existing = $this->urlFinder->findAllByData([
            UrlRewrite::ENTITY_TYPE => $entityType,
            UrlRewrite::ENTITY_ID => $entityId,
        ]);
        foreach ($existing as $row) {
            $storeId = (int) $row->getStoreId();
            if (!isset($keep[$storeId])) {
                $stale[$storeId] = $storeId;
            }
        }

        if ($stale === []) {
            return;
        }

        $this->urlPersist->deleteByData([
            UrlRewrite::ENTITY_TYPE => $entityType,
            UrlRewrite::ENTITY_ID => $entityId,
            UrlRewrite::STORE_ID => array_values($stale),
        ]);
    }
}
