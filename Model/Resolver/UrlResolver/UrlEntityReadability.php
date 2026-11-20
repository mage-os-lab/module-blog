<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\UrlResolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Model\Resolver\ReadPolicy;
use MageOS\Blog\Model\Url\UrlRewriteBuilder;

/**
 * Answers "may this caller see the entity behind a url_rewrite row?" for the urlResolver and route
 * plugins. A blog entity is readable only when ReadPolicy says so; every failure counts as "no".
 */
class UrlEntityReadability
{
    public function __construct(
        private readonly PostRepositoryInterface $postRepository,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly TagRepositoryInterface $tagRepository,
        private readonly AuthorRepositoryInterface $authorRepository,
        private readonly ReadPolicy $readPolicy,
    ) {
    }

    /**
     * Entity types of other modules are always readable here: they are not ours to judge.
     */
    public function canRead(string $type, int $id, mixed $context): bool
    {
        $type = strtolower($type);

        // Fail closed: a blog entity is only ever readable with a context the policy understands.
        if (!$context instanceof ContextInterface) {
            return !str_starts_with($type, 'blog_');
        }

        try {
            return match ($type) {
                UrlRewriteBuilder::ENTITY_TYPE_POST => $this->readPolicy->canReadPost(
                    $context,
                    $this->postRepository->getById($id)
                ),
                UrlRewriteBuilder::ENTITY_TYPE_CATEGORY => $this->readPolicy->canReadCategory(
                    $context,
                    $this->categoryRepository->getById($id)
                ),
                UrlRewriteBuilder::ENTITY_TYPE_TAG => $this->readPolicy->canReadTag(
                    $context,
                    $this->tagRepository->getById($id)
                ),
                UrlRewriteBuilder::ENTITY_TYPE_AUTHOR => $this->readPolicy->canReadAuthor(
                    $context,
                    $this->authorRepository->getById($id)
                ),
                default => true,
            };
        } catch (LocalizedException) {
            // Not found, or any other failure to load it: either way the entity is not shown.
            return false;
        }
    }
}
