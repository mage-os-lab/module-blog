<?php

declare(strict_types=1);

namespace MageOS\Blog\Plugin\Magento\UrlRewriteGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\UrlRewriteGraphQl\Model\Resolver\EntityUrl as CoreEntityUrl;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Model\Resolver\ReadPolicy;
use MageOS\Blog\Model\Url\UrlRewriteBuilder;

/**
 * Core's urlResolver answers from url_rewrite rows, and the blog writes those for every entity
 * whatever its status. Without this, a guessed draft slug returns its id and type to anyone.
 * A blog entity the caller may not read answers exactly like an unknown URL: null.
 */
class EntityUrl
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
     * @param array<string, mixed>|null $result
     * @param array<string, mixed>|null $value
     * @param array<string, mixed>|null $args
     *
     * @return array<string, mixed>|null
     */
    public function afterResolve(
        CoreEntityUrl $subject,
        ?array $result,
        Field $field,
        mixed $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null,
    ): ?array {
        if ($result === null || empty($result['id']) || !isset($result['type'])) {
            return $result;
        }

        return $this->isReadable(strtolower((string) $result['type']), (int) $result['id'], $context)
            ? $result
            : null;
    }

    private function isReadable(string $type, int $id, mixed $context): bool
    {
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
