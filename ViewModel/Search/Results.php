<?php

declare(strict_types=1);

namespace MageOS\Blog\ViewModel\Search;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Model\BlogPostStatus;
use MageOS\Blog\Model\Config;

class Results implements ArgumentInterface
{
    /**
     * @var PostInterface[]|null
     */
    private ?array $cachedItems = null;
    private ?int $cachedTotal = null;

    public function __construct(
        private readonly RequestInterface $request,
        private readonly ResourceConnection $resource,
        private readonly PostRepositoryInterface $repository,
        private readonly StoreManagerInterface $storeManager,
        private readonly Config $config,
        private readonly UrlInterface $urlBuilder
    ) {
    }

    public function getQuery(): string
    {
        return trim((string) $this->request->getParam('q', ''));
    }

    /**
     * @return PostInterface[]
     */
    public function getItems(): array
    {
        $this->fetch();
        return $this->cachedItems ?? [];
    }

    public function getTotalCount(): int
    {
        $this->fetch();
        return $this->cachedTotal ?? 0;
    }

    public function getPageSize(): int
    {
        $size = $this->config->getPostsPerPage();
        return $size > 0 ? $size : 10;
    }

    public function getCurrentPage(): int
    {
        return max(1, (int) $this->request->getParam('p', 1));
    }

    public function getTotalPages(): int
    {
        $total = $this->getTotalCount();
        $size = $this->getPageSize();
        return $total === 0 ? 0 : (int) ceil($total / $size);
    }

    public function getPostUrl(PostInterface $post): string
    {
        return $this->urlBuilder->getUrl('blog/' . $post->getUrlKey());
    }

    public function getPageUrl(int $page): string
    {
        $params = (array) $this->request->getParams();
        $params['p'] = $page;
        return $this->urlBuilder->getUrl('blog/search', ['_query' => $params]);
    }

    private function fetch(): void
    {
        if ($this->cachedItems !== null) {
            return;
        }
        $this->cachedItems = [];
        $this->cachedTotal = 0;

        $query = $this->getQuery();
        if ($query === '') {
            return;
        }

        $connection = $this->resource->getConnection();
        $postTable = $this->resource->getTableName('mageos_blog_post');
        $storeTable = $this->resource->getTableName('mageos_blog_post_store');
        $storeId = (int) $this->storeManager->getStore()->getId();

        $ftMatch = 'MATCH (p.title, p.short_content, p.content, p.meta_description) '
            . 'AGAINST (:q IN NATURAL LANGUAGE MODE)';

        $countSelect = $connection->select()
            ->from(['p' => $postTable], ['COUNT(DISTINCT p.post_id)'])
            ->joinLeft(
                ['s' => $storeTable],
                's.post_id = p.post_id',
                []
            )
            ->where($ftMatch)
            ->where('p.status = ?', BlogPostStatus::Published->value)
            ->where('s.store_id IN (?) OR s.store_id IS NULL', [$storeId, 0]);

        $total = (int) $connection->fetchOne($countSelect, ['q' => $query]);
        $this->cachedTotal = $total;
        if ($total === 0) {
            return;
        }

        $pageSize = $this->getPageSize();
        $offset = ($this->getCurrentPage() - 1) * $pageSize;

        $idSelect = $connection->select()
            ->from(['p' => $postTable], ['post_id', 'relevance' => new Expression($ftMatch)])
            ->joinLeft(
                ['s' => $storeTable],
                's.post_id = p.post_id',
                []
            )
            ->where($ftMatch)
            ->where('p.status = ?', BlogPostStatus::Published->value)
            ->where('s.store_id IN (?) OR s.store_id IS NULL', [$storeId, 0])
            ->group('p.post_id')
            ->order('relevance DESC')
            ->limit($pageSize, $offset);

        $rows = $connection->fetchAll($idSelect, ['q' => $query]);

        $items = [];
        foreach ($rows as $row) {
            try {
                $items[] = $this->repository->getById((int) $row['post_id']);
            } catch (NoSuchEntityException) {
                // skip deleted
            }
        }
        $this->cachedItems = $items;
    }
}
