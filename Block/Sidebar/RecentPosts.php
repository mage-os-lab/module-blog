<?php

declare(strict_types=1);

namespace MageOS\Blog\Block\Sidebar;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\View\Element\Template;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Block\Widget\RecentPosts as RecentPostsWidget;
use MageOS\Blog\Model\Config;

class RecentPosts extends RecentPostsWidget
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        Template\Context $context,
        PostRepositoryInterface $repository,
        SearchCriteriaBuilder $criteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        Config $config,
        array $data = []
    ) {
        $data['title'] = (string) __('Recent Posts');
        $data['count'] = max(1, $config->getRecentPostsCount());

        parent::__construct($context, $repository, $criteriaBuilder, $sortOrderBuilder, $data);
    }
}
