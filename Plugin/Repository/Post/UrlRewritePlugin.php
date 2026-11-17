<?php

declare(strict_types=1);

namespace MageOS\Blog\Plugin\Repository\Post;

use Magento\UrlRewrite\Model\UrlPersistInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
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

    public function afterSave(PostRepositoryInterface $subject, PostInterface $result): PostInterface
    {
        $postId = (int) $result->getPostId();
        $newRows = $this->urlRewriteBuilder->buildForPost($result, $result->getStoreIds());

        // Must run before the redirect lookup, or removed stores would get 301 rows recreated.
        $this->staleStoreRewriteCleaner->deleteOutsideNewRows(
            UrlRewriteBuilder::ENTITY_TYPE_POST,
            $postId,
            $newRows,
        );

        $this->redirectBuilder->releasePathsHeldByRedirects(
            UrlRewriteBuilder::ENTITY_TYPE_POST,
            $postId,
            $newRows,
        );
        $redirectRows = $this->redirectBuilder->build(UrlRewriteBuilder::ENTITY_TYPE_POST, $postId, $newRows);

        $this->urlPersist->replace(array_merge($redirectRows, $newRows));

        return $result;
    }

    public function afterDelete(PostRepositoryInterface $subject, bool $result, PostInterface $post): bool
    {
        $this->urlPersist->deleteByData([
            UrlRewrite::ENTITY_TYPE => UrlRewriteBuilder::ENTITY_TYPE_POST,
            UrlRewrite::ENTITY_ID => (int) $post->getPostId(),
        ]);

        return $result;
    }

    public function afterDeleteById(PostRepositoryInterface $subject, bool $result, int $id): bool
    {
        $this->urlPersist->deleteByData([
            UrlRewrite::ENTITY_TYPE => UrlRewriteBuilder::ENTITY_TYPE_POST,
            UrlRewrite::ENTITY_ID => $id,
        ]);

        return $result;
    }
}
