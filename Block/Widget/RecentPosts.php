<?php

declare(strict_types=1);

namespace MageOS\Blog\Block\Widget;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Block\BlogCacheIdentities;
use MageOS\Blog\Model\BlogPostStatus;
use MageOS\Blog\Model\Url\Permalink;

class RecentPosts extends Template implements BlockInterface, IdentityInterface
{
    use BlogCacheIdentities;

    /**
     * Posts rendered by this widget, wherever it is embedded — a CMS page, a
     * block, a layout handle. Publishing a post must drop those pages too.
     *
     * @return string[]
     */
    public function getIdentities(): array
    {
        return $this->postIdentities($this->getPosts());
    }

    protected $_template = 'MageOS_Blog::widget/recent-posts.phtml';

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        Template\Context $context,
        private readonly PostRepositoryInterface $repository,
        private readonly SearchCriteriaBuilder $criteriaBuilder,
        private readonly SortOrderBuilder $sortOrderBuilder,
        private readonly Permalink $permalink,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return PostInterface[]
     */
    public function getPosts(): array
    {
        $count = max(1, (int) $this->getData('count'));
        $sort = $this->sortOrderBuilder
            ->setField(PostInterface::PUBLISH_DATE)
            ->setDirection(SortOrder::SORT_DESC)
            ->create();

        $criteria = $this->criteriaBuilder
            ->addFilter(PostInterface::STATUS, BlogPostStatus::Published->value)
            ->addSortOrder($sort)
            ->setPageSize($count)
            ->setCurrentPage(1)
            ->create();

        return $this->repository->getList($criteria)->getItems();
    }

    public function getPostUrl(PostInterface $post): string
    {
        return $this->_urlBuilder->getUrl(
            '',
            ['_direct' => $this->permalink->getPostPath($post->getUrlKey())]
        );
    }

    public function getTitleSafe(): string
    {
        return (string) $this->getData('title');
    }
}
