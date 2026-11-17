<?php

declare(strict_types=1);

namespace MageOS\Blog\ViewModel\Post;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Model\BlogPostStatus;
use MageOS\Blog\Model\Config;
use MageOS\Blog\Model\Url\Permalink;

class Listing implements ArgumentInterface, PostPresenterInterface
{
    use PostPresenterTrait;

    /**
     * @var array{items: PostInterface[], total: int}|null
     */
    private ?array $cachedResults = null;

    public function __construct(
        private readonly PostRepositoryInterface $repository,
        private readonly SearchCriteriaBuilder $criteriaBuilder,
        private readonly StoreManagerInterface $storeManager,
        private readonly RequestInterface $request,
        private readonly UrlInterface $urlBuilder,
        private readonly Config $config,
        private readonly AuthorRepositoryInterface $authorRepository,
        private readonly SortOrderBuilder $sortOrderBuilder,
        private readonly Permalink $permalink,
    ) {
    }

    /**
     * @return PostInterface[]
     */
    public function getItems(): array
    {
        return $this->fetchResults()['items'];
    }

    public function getTotalCount(): int
    {
        return $this->fetchResults()['total'];
    }

    public function getCurrentPage(): int
    {
        $page = (int) $this->request->getParam('p', 1);

        return max($page, 1);
    }

    public function getPageSize(): int
    {
        $size = $this->config->getPostsPerPage();

        return $size > 0 ? $size : 10;
    }

    public function getTotalPages(): int
    {
        $total = $this->getTotalCount();
        $size = $this->getPageSize();

        return $total === 0 ? 0 : (int) ceil($total / $size);
    }

    public function getPageUrl(int $page): string
    {
        $current = (array) $this->request->getParams();
        $current['p'] = $page;

        return $this->urlBuilder->getUrl('', ['_direct' => $this->permalink->getIndexPath(), '_query' => $current]);
    }

    private function urlBuilder(): UrlInterface
    {
        return $this->urlBuilder;
    }

    private function authorRepository(): AuthorRepositoryInterface
    {
        return $this->authorRepository;
    }

    private function config(): Config
    {
        return $this->config;
    }

    private function permalink(): Permalink
    {
        return $this->permalink;
    }

    /**
     * @return array{items: PostInterface[], total: int}
     */
    private function fetchResults(): array
    {
        if ($this->cachedResults !== null) {
            return $this->cachedResults;
        }

        $storeId = $this->storeManager->getStore()->getId();
        $sortOrder = $this->sortOrderBuilder
            ->setField(PostInterface::PUBLISH_DATE)
            ->setDirection(SortOrder::SORT_DESC)
            ->create();

        $criteria = $this->criteriaBuilder
            ->addFilter(PostInterface::STATUS, BlogPostStatus::Published->value)
            ->addFilter(PostInterface::STORE_IDS, [$storeId, 0], 'in')
            ->addSortOrder($sortOrder)
            ->setPageSize($this->getPageSize())
            ->setCurrentPage($this->getCurrentPage())
            ->create();

        $results = $this->repository->getList($criteria);
        $this->cachedResults = ['items' => $results->getItems(), 'total' => $results->getTotalCount()];

        return $this->cachedResults;
    }
}
