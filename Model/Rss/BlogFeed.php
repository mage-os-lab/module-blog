<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Rss;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\App\Rss\DataProviderInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Model\BlogPostStatus;
use MageOS\Blog\Model\Config;

class BlogFeed implements DataProviderInterface
{
    public function __construct(
        private readonly PostRepositoryInterface $repository,
        private readonly SearchCriteriaBuilder $criteriaBuilder,
        private readonly SortOrderBuilder $sortOrderBuilder,
        private readonly Config $config,
        private readonly StoreManagerInterface $storeManager,
        private readonly UrlInterface $urlBuilder
    ) {
    }

    public function isAllowed()
    {
        return $this->config->isEnabled() && $this->config->isRssEnabled();
    }

    /**
     * @return array<string, mixed>
     */
    public function getRssData()
    {
        $limit = max(1, $this->config->getRssLimit() ?: 20);
        $sort = $this->sortOrderBuilder
            ->setField(PostInterface::PUBLISH_DATE)
            ->setDirection(SortOrder::SORT_DESC)
            ->create();

        $criteria = $this->criteriaBuilder
            ->addFilter(PostInterface::STATUS, BlogPostStatus::Published->value)
            ->addSortOrder($sort)
            ->setPageSize($limit)
            ->setCurrentPage(1)
            ->create();

        $store = $this->storeManager->getStore();
        $entries = [];
        foreach ($this->repository->getList($criteria)->getItems() as $post) {
            $entries[] = [
                'title' => (string) $post->getTitle(),
                'link' => $this->urlBuilder->getUrl('blog/' . $post->getUrlKey()),
                'description' => (string) ($post->getShortContent() ?? ''),
                'pubDate' => $this->formatRfc2822((string) ($post->getPublishDate() ?? '')),
            ];
        }

        return [
            'title' => (string) $store->getName() . ' — ' . (string) __('Blog'),
            'description' => (string) __('Latest blog posts from %1', $store->getName()),
            'link' => $this->urlBuilder->getUrl('blog/'),
            'entries' => $entries,
        ];
    }

    public function getCacheKey()
    {
        return 'mageos_blog_rss_' . (int) $this->storeManager->getStore()->getId();
    }

    public function getCacheLifetime()
    {
        return 600;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getFeeds()
    {
        return [];
    }

    public function isAuthRequired()
    {
        return false;
    }

    private function formatRfc2822(string $date): string
    {
        if ($date === '') {
            return '';
        }
        try {
            return (new \DateTimeImmutable($date))->format(\DateTimeInterface::RFC2822);
        } catch (\Throwable) {
            return '';
        }
    }
}
