<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\UrlResolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\UrlRewriteGraphQl\Model\DataProvider\EntityDataProviderInterface;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Model\Resolver\Mapper\AuthorMapper;
use MageOS\Blog\Model\Resolver\Mapper\CategoryMapper;
use MageOS\Blog\Model\Resolver\Mapper\PostMapper;
use MageOS\Blog\Model\Resolver\Mapper\TagMapper;
use MageOS\Blog\Model\Url\UrlRewriteBuilder;

/**
 * Loads the entity behind a blog url_rewrite row for the route query.
 *
 * This provider does NOT decide who may read the entity: the data provider interface is not given the
 * GraphQL context. The Route plugin checks UrlEntityReadability on whatever this returns and answers
 * like an unknown URL when the caller may not read it. Never register this provider without that plugin.
 */
class BlogEntityDataProvider implements EntityDataProviderInterface
{
    public function __construct(
        private readonly PostRepositoryInterface $postRepository,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly TagRepositoryInterface $tagRepository,
        private readonly AuthorRepositoryInterface $authorRepository,
        private readonly PostMapper $postMapper,
        private readonly CategoryMapper $categoryMapper,
        private readonly TagMapper $tagMapper,
        private readonly AuthorMapper $authorMapper,
    ) {
    }

    /**
     * @throws RouteNotSupportedException when the entity cannot be loaded or the type is not a blog type
     * @return array<string, mixed>
     */
    public function getData(
        string $entity_type,
        int $id,
        ?ResolveInfo $info = null,
        ?int $storeId = null,
    ): array {
        try {
            $data = match (strtolower($entity_type)) {
                UrlRewriteBuilder::ENTITY_TYPE_POST => $this->postMapper->toArray(
                    $this->postRepository->getById($id)
                ),
                UrlRewriteBuilder::ENTITY_TYPE_CATEGORY => $this->categoryMapper->toArray(
                    $this->categoryRepository->getById($id)
                ),
                UrlRewriteBuilder::ENTITY_TYPE_TAG => $this->tagMapper->toArray(
                    $this->tagRepository->getById($id)
                ),
                UrlRewriteBuilder::ENTITY_TYPE_AUTHOR => $this->authorMapper->toArray(
                    $this->authorRepository->getById($id)
                ),
                default => throw new RouteNotSupportedException($entity_type),
            };

            // Core's RoutableInterfaceTypeResolver reads type_id and fails without it; core providers set it too.
            $data['type_id'] = $entity_type;

            return $data;
        } catch (LocalizedException) {
            // Gone, or failing to load: same answer as an unknown URL.
            throw new RouteNotSupportedException($entity_type);
        }
    }
}
