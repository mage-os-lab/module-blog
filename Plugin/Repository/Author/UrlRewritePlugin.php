<?php

declare(strict_types=1);

namespace MageOS\Blog\Plugin\Repository\Author;

use Magento\UrlRewrite\Model\UrlPersistInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\Data\AuthorInterface;
use MageOS\Blog\Model\Url\RedirectBuilder;
use MageOS\Blog\Model\Url\UrlRewriteBuilder;

class UrlRewritePlugin
{
    public function __construct(
        private readonly UrlRewriteBuilder $urlRewriteBuilder,
        private readonly UrlPersistInterface $urlPersist,
        private readonly RedirectBuilder $redirectBuilder,
    ) {
    }

    public function afterSave(
        AuthorRepositoryInterface $subject,
        AuthorInterface $result,
    ): AuthorInterface {
        $authorId = (int) $result->getAuthorId();
        $newRows = $this->urlRewriteBuilder->buildForAuthor($result);

        $this->redirectBuilder->releasePathsHeldByRedirects(
            UrlRewriteBuilder::ENTITY_TYPE_AUTHOR,
            $authorId,
            $newRows,
        );
        $redirectRows = $this->redirectBuilder->build(UrlRewriteBuilder::ENTITY_TYPE_AUTHOR, $authorId, $newRows);

        $this->urlPersist->replace(array_merge($redirectRows, $newRows));

        return $result;
    }

    public function afterDelete(
        AuthorRepositoryInterface $subject,
        bool $result,
        AuthorInterface $author,
    ): bool {
        $this->urlPersist->deleteByData([
            UrlRewrite::ENTITY_TYPE => UrlRewriteBuilder::ENTITY_TYPE_AUTHOR,
            UrlRewrite::ENTITY_ID => (int) $author->getAuthorId(),
        ]);

        return $result;
    }

    public function afterDeleteById(
        AuthorRepositoryInterface $subject,
        bool $result,
        int $id,
    ): bool {
        $this->urlPersist->deleteByData([
            UrlRewrite::ENTITY_TYPE => UrlRewriteBuilder::ENTITY_TYPE_AUTHOR,
            UrlRewrite::ENTITY_ID => $id,
        ]);

        return $result;
    }
}
