<?php

declare(strict_types=1);

namespace MageOS\Blog\ViewModel\Post;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Model\BlogPostStatus;
use MageOS\Blog\Model\Config;

class Listing implements ArgumentInterface
{
    /**
     * @var array{items: PostInterface[], total: int}|null
     */
    private ?array $cachedResults = null;

    /**
     * @var array<int, \MageOS\Blog\Api\Data\AuthorInterface|false>
     */
    private array $authorCache = [];

    public function __construct(
        private readonly PostRepositoryInterface $repository,
        private readonly SearchCriteriaBuilder $criteriaBuilder,
        private readonly SortOrderBuilder $sortOrderBuilder,
        private readonly StoreManagerInterface $storeManager,
        private readonly RequestInterface $request,
        private readonly UrlInterface $urlBuilder,
        private readonly Config $config,
        private readonly AuthorRepositoryInterface $authorRepository
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

        return $this->urlBuilder->getUrl('blog', ['_query' => $current]);
    }

    public function getPostUrl(PostInterface $post): string
    {
        return $this->urlBuilder->getUrl('blog/' . $post->getUrlKey());
    }

    public function getFormattedPublishDate(PostInterface $post): string
    {
        $date = $post->getPublishDate();
        if ($date === null || $date === '') {
            return '';
        }

        try {
            return (new \DateTimeImmutable($date))->format('F j, Y');
        } catch (\Throwable) {
            return '';
        }
    }

    public function getFeaturedImageUrl(PostInterface $post): ?string
    {
        $path = (string) $post->getFeaturedImage();
        if ($path === '') {
            return null;
        }
        $media = rtrim($this->urlBuilder->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA]), '/');
        return $media . '/mageos_blog/' . ltrim($path, '/');
    }

    public function getAuthorName(PostInterface $post): ?string
    {
        $author = $this->loadAuthor($post);
        return $author === null ? null : (string) $author->getName();
    }

    public function getAuthorUrl(PostInterface $post): ?string
    {
        $author = $this->loadAuthor($post);
        if ($author === null) {
            return null;
        }
        $slug = (string) $author->getSlug();
        return $slug === '' ? null : $this->urlBuilder->getUrl('blog/author/' . $slug);
    }

    private function loadAuthor(PostInterface $post): ?\MageOS\Blog\Api\Data\AuthorInterface
    {
        $id = $post->getAuthorId();
        if ($id === null || $id <= 0) {
            return null;
        }
        if (\array_key_exists($id, $this->authorCache)) {
            $cached = $this->authorCache[$id];
            return $cached === false ? null : $cached;
        }
        try {
            $author = $this->authorRepository->getById((int) $id);
        } catch (NoSuchEntityException) {
            $this->authorCache[$id] = false;
            return null;
        }
        $this->authorCache[$id] = $author;
        return $author;
    }

    /**
     * @return array{items: PostInterface[], total: int}
     */
    private function fetchResults(): array
    {
        if ($this->cachedResults !== null) {
            return $this->cachedResults;
        }

        $this->storeManager->getStore()->getId();
        $sort = $this->sortOrderBuilder
            ->setField(PostInterface::PUBLISH_DATE)
            ->setDirection(SortOrder::SORT_DESC)
            ->create();
        $criteria = $this->criteriaBuilder
            ->addFilter(PostInterface::STATUS, BlogPostStatus::Published->value)
            ->addSortOrder($sort)
            ->setPageSize($this->getPageSize())
            ->setCurrentPage($this->getCurrentPage())
            ->create();

        $results = $this->repository->getList($criteria);
        $this->cachedResults = ['items' => $results->getItems(), 'total' => $results->getTotalCount()];

        return $this->cachedResults;
    }
}
