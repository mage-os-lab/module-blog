<?php

declare(strict_types=1);

namespace MageOS\Blog\Plugin\Repository\Tag;

use Magento\UrlRewrite\Model\UrlPersistInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use MageOS\Blog\Api\Data\TagInterface;
use MageOS\Blog\Api\TagRepositoryInterface;
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

    public function afterSave(TagRepositoryInterface $subject, TagInterface $result): TagInterface
    {
        $tagId = (int) $result->getTagId();
        $newRows = $this->urlRewriteBuilder->buildForTag($result, $result->getStoreIds());

        // Must run before the redirect lookup, or removed stores would get 301 rows recreated.
        $this->staleStoreRewriteCleaner->deleteOutsideNewRows(
            UrlRewriteBuilder::ENTITY_TYPE_TAG,
            $tagId,
            $newRows,
        );

        $this->redirectBuilder->releasePathsHeldByRedirects(
            UrlRewriteBuilder::ENTITY_TYPE_TAG,
            $tagId,
            $newRows,
        );
        $redirectRows = $this->redirectBuilder->build(UrlRewriteBuilder::ENTITY_TYPE_TAG, $tagId, $newRows);

        $this->urlPersist->replace(array_merge($redirectRows, $newRows));

        return $result;
    }

    public function afterDelete(TagRepositoryInterface $subject, bool $result, TagInterface $tag): bool
    {
        $this->urlPersist->deleteByData([
            UrlRewrite::ENTITY_TYPE => UrlRewriteBuilder::ENTITY_TYPE_TAG,
            UrlRewrite::ENTITY_ID => (int) $tag->getTagId(),
        ]);

        return $result;
    }

    public function afterDeleteById(TagRepositoryInterface $subject, bool $result, int $id): bool
    {
        $this->urlPersist->deleteByData([
            UrlRewrite::ENTITY_TYPE => UrlRewriteBuilder::ENTITY_TYPE_TAG,
            UrlRewrite::ENTITY_ID => $id,
        ]);

        return $result;
    }
}
