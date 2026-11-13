<?php

declare(strict_types=1);

namespace MageOS\Blog\Block\Widget;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Block\BlogCacheIdentities;
use MageOS\Blog\Model\BlogPostStatus;
use MageOS\Blog\Model\Url\Permalink;

class PostList extends Template implements BlockInterface, IdentityInterface
{
    use BlogCacheIdentities;

    protected $_template = 'MageOS_Blog::widget/post-list.phtml';

    /**
     * @return string[]
     */
    public function getIdentities(): array
    {
        return $this->postIdentities($this->getPosts());
    }

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        Template\Context $context,
        private readonly PostRepositoryInterface $repository,
        private readonly SearchCriteriaBuilder $criteriaBuilder,
        private readonly SortOrderBuilder $sortOrderBuilder,
        private readonly ResourceConnection $resource,
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
        $mode = (string) $this->getData('mode');

        $postIds = match ($mode) {
            'category' => $this->postIdsByCategory((int) $this->getData('category_id'), $count),
            'tag' => $this->postIdsByTag((int) $this->getData('tag_id'), $count),
            default => null,
        };

        if ($postIds === []) {
            return [];
        }

        $sort = $this->sortOrderBuilder
            ->setField(PostInterface::PUBLISH_DATE)
            ->setDirection(SortOrder::SORT_DESC)
            ->create();

        $builder = $this->criteriaBuilder
            ->addFilter(PostInterface::STATUS, BlogPostStatus::Published->value)
            ->addSortOrder($sort)
            ->setPageSize($count)
            ->setCurrentPage(1);

        if ($postIds !== null) {
            $builder->addFilter(PostInterface::POST_ID, $postIds, 'in');
        }

        return $this->repository->getList($builder->create())->getItems();
    }

    public function getPostUrl(PostInterface $post): string
    {
        return $this->_urlBuilder->getUrl(
            '',
            ['_direct' => $this->permalink->getPostPath($post->getUrlKey())]
        );
    }

    /**
     * @return int[]
     */
    private function postIdsByCategory(int $categoryId, int $limit): array
    {
        if ($categoryId <= 0) {
            return [];
        }
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('mageos_blog_post_category');
        $select = $connection->select()
            ->from($table, ['post_id'])
            ->where('category_id = ?', $categoryId)
            ->limit($limit);
        return array_map('intval', $connection->fetchCol($select));
    }

    /**
     * @return int[]
     */
    private function postIdsByTag(int $tagId, int $limit): array
    {
        if ($tagId <= 0) {
            return [];
        }
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('mageos_blog_post_tag');
        $select = $connection->select()
            ->from($table, ['post_id'])
            ->where('tag_id = ?', $tagId)
            ->limit($limit);
        return array_map('intval', $connection->fetchCol($select));
    }
}
