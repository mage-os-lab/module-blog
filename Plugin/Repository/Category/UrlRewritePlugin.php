<?php

declare(strict_types=1);

namespace MageOS\Blog\Plugin\Repository\Category;

use Magento\UrlRewrite\Model\UrlPersistInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Model\Url\RedirectBuilder;
use MageOS\Blog\Model\Url\StaleStoreRewriteCleaner;
use MageOS\Blog\Model\Url\UrlRewriteBuilder;

class UrlRewritePlugin
{
    public function __construct(
        private readonly UrlRewriteBuilder $urlRewriteBuilder,
        private readonly UrlPersistInterface $urlPersist,
        private readonly StaleStoreRewriteCleaner $staleStoreRewriteCleaner,
        private readonly RedirectBuilder $redirectBuilder,
    ) {
    }

    public function afterSave(
        CategoryRepositoryInterface $subject,
        CategoryInterface $result,
    ): CategoryInterface {
        $categoryId = (int) $result->getCategoryId();
        $newRows = $this->urlRewriteBuilder->buildForCategory($result, $result->getStoreIds());

        // Must run before the redirect lookup, or removed stores would get 301 rows recreated.
        $this->staleStoreRewriteCleaner->deleteOutsideNewRows(
            UrlRewriteBuilder::ENTITY_TYPE_CATEGORY,
            $categoryId,
            $newRows,
        );

        $this->redirectBuilder->releasePathsHeldByRedirects(
            UrlRewriteBuilder::ENTITY_TYPE_CATEGORY,
            $categoryId,
            $newRows,
        );
        $redirectRows = $this->redirectBuilder->build(UrlRewriteBuilder::ENTITY_TYPE_CATEGORY, $categoryId, $newRows);

        $this->urlPersist->replace(array_merge($redirectRows, $newRows));

        return $result;
    }

    public function afterDelete(
        CategoryRepositoryInterface $subject,
        bool $result,
        CategoryInterface $category,
    ): bool {
        $this->urlPersist->deleteByData([
            UrlRewrite::ENTITY_TYPE => UrlRewriteBuilder::ENTITY_TYPE_CATEGORY,
            UrlRewrite::ENTITY_ID => (int) $category->getCategoryId(),
        ]);

        return $result;
    }

    public function afterDeleteById(
        CategoryRepositoryInterface $subject,
        bool $result,
        int $id,
    ): bool {
        $this->urlPersist->deleteByData([
            UrlRewrite::ENTITY_TYPE => UrlRewriteBuilder::ENTITY_TYPE_CATEGORY,
            UrlRewrite::ENTITY_ID => $id,
        ]);

        return $result;
    }
}
