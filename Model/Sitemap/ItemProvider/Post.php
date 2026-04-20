<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Sitemap\ItemProvider;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Model\AbstractModel;
use Magento\Sitemap\Model\ItemProvider\ItemProviderInterface;
use Magento\Sitemap\Model\SitemapItemInterfaceFactory;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Model\BlogPostStatus;
use MageOS\Blog\Model\Config;

class Post implements ItemProviderInterface
{
    private const ENTITY_SLUG = 'post';

    public function __construct(
        private readonly PostRepositoryInterface $repository,
        private readonly SearchCriteriaBuilder $criteriaBuilder,
        private readonly SitemapItemInterfaceFactory $sitemapItemFactory,
        private readonly Config $config
    ) {
    }

    /**
     * @param int $storeId
     * @return \Magento\Sitemap\Model\SitemapItemInterface[]
     */
    public function getItems($storeId): array
    {
        if (!$this->config->isSitemapEntityEnabled(self::ENTITY_SLUG, (int) $storeId)) {
            return [];
        }

        $criteria = $this->criteriaBuilder
            ->addFilter(PostInterface::STATUS, BlogPostStatus::Published->value)
            ->create();

        $frequency = $this->config->getSitemapEntityFrequency(self::ENTITY_SLUG, (int) $storeId) ?: 'weekly';
        $priority = $this->config->getSitemapEntityPriority(self::ENTITY_SLUG, (int) $storeId) ?: '0.5';

        $items = [];
        foreach ($this->repository->getList($criteria)->getItems() as $post) {
            $storeIds = $post->getStoreIds();
            if ($storeIds !== []
                && !\in_array((int) $storeId, $storeIds, true)
                && !\in_array(0, $storeIds, true)
            ) {
                continue;
            }
            $updatedAt = $post instanceof AbstractModel ? (string) ($post->getData('update_time') ?? '') : '';
            $items[] = $this->sitemapItemFactory->create([
                'url' => 'blog/' . $post->getUrlKey(),
                'priority' => (string) $priority,
                'changeFrequency' => (string) $frequency,
                'updatedAt' => $updatedAt,
                'images' => null,
            ]);
        }
        return $items;
    }
}
