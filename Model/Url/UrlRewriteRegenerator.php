<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Url;

use Magento\Framework\App\ResourceConnection;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Api\TagRepositoryInterface;

/**
 * Rebuilds the url_rewrite rows of every entity of one type from its current state and the
 * permalink config, with redirects from every path the entity had before.
 *
 * Shared by the mageos:blog:url-rewrite:regenerate command and the permalink config observer.
 */
class UrlRewriteRegenerator
{
    public const TYPE_POST = 'post';
    public const TYPE_CATEGORY = 'category';
    public const TYPE_TAG = 'tag';
    public const TYPE_AUTHOR = 'author';

    /**
     * Type => url_rewrite entity type, primary table and id column.
     *
     * @var array<string, array{entity_type: string, table: string, id: string}>
     */
    private const TYPES = [
        self::TYPE_POST => [
            'entity_type' => UrlRewriteBuilder::ENTITY_TYPE_POST,
            'table' => 'mageos_blog_post',
            'id' => 'post_id',
        ],
        self::TYPE_CATEGORY => [
            'entity_type' => UrlRewriteBuilder::ENTITY_TYPE_CATEGORY,
            'table' => 'mageos_blog_category',
            'id' => 'category_id',
        ],
        self::TYPE_TAG => [
            'entity_type' => UrlRewriteBuilder::ENTITY_TYPE_TAG,
            'table' => 'mageos_blog_tag',
            'id' => 'tag_id',
        ],
        self::TYPE_AUTHOR => [
            'entity_type' => UrlRewriteBuilder::ENTITY_TYPE_AUTHOR,
            'table' => 'mageos_blog_author',
            'id' => 'author_id',
        ],
    ];

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly UrlRewriteBuilder $urlRewriteBuilder,
        private readonly RedirectBuilder $redirectBuilder,
        private readonly StaleStoreRewriteCleaner $staleStoreRewriteCleaner,
        private readonly UrlPersistInterface $urlPersist,
        private readonly PostRepositoryInterface $postRepository,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly TagRepositoryInterface $tagRepository,
        private readonly AuthorRepositoryInterface $authorRepository,
    ) {
    }

    /**
     * @return string[]
     */
    public function getTypes(): array
    {
        return array_keys(self::TYPES);
    }

    /**
     * @return array{rewrites: int, redirects: int, errors: string[]}
     */
    public function regenerate(string $type, bool $dryRun = false): array
    {
        if (!isset(self::TYPES[$type])) {
            throw new \InvalidArgumentException('Unknown entity type: ' . $type);
        }

        $entityType = self::TYPES[$type]['entity_type'];
        $rewrites = 0;
        $redirects = 0;
        $errors = [];

        foreach ($this->fetchIds($type) as $id) {
            try {
                $rows = $this->buildRows($type, $id);
                $redirectRows = $this->redirectBuilder->build($entityType, $id, $rows);
                if (!$dryRun) {
                    $this->staleStoreRewriteCleaner->deleteOutsideNewRows($entityType, $id, $rows);
                    $this->redirectBuilder->releasePathsHeldByRedirects($entityType, $id, $rows);
                    $this->urlPersist->replace([...$redirectRows, ...$rows]);
                }
                $rewrites += \count($rows);
                $redirects += \count($redirectRows);
            } catch (\Exception $e) {
                $errors[] = \sprintf('%s %d: %s', $type, $id, $e->getMessage());
            }
        }

        return ['rewrites' => $rewrites, 'redirects' => $redirects, 'errors' => $errors];
    }

    /**
     * @return int[]
     */
    private function fetchIds(string $type): array
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()->from(
            $this->resource->getTableName(self::TYPES[$type]['table']),
            [self::TYPES[$type]['id']]
        );

        return array_map('intval', $connection->fetchCol($select));
    }

    /**
     * @return UrlRewrite[]
     */
    private function buildRows(string $type, int $id): array
    {
        return match ($type) {
            self::TYPE_POST => $this->postRows($id),
            self::TYPE_CATEGORY => $this->categoryRows($id),
            self::TYPE_TAG => $this->tagRows($id),
            self::TYPE_AUTHOR => $this->urlRewriteBuilder->buildForAuthor($this->authorRepository->getById($id)),
            default => throw new \InvalidArgumentException('Unknown entity type: ' . $type),
        };
    }

    /**
     * @return UrlRewrite[]
     */
    private function postRows(int $id): array
    {
        $post = $this->postRepository->getById($id);

        return $this->urlRewriteBuilder->buildForPost($post, $post->getStoreIds());
    }

    /**
     * @return UrlRewrite[]
     */
    private function categoryRows(int $id): array
    {
        $category = $this->categoryRepository->getById($id);

        return $this->urlRewriteBuilder->buildForCategory($category, $category->getStoreIds());
    }

    /**
     * @return UrlRewrite[]
     */
    private function tagRows(int $id): array
    {
        $tag = $this->tagRepository->getById($id);

        return $this->urlRewriteBuilder->buildForTag($tag, $tag->getStoreIds());
    }
}
