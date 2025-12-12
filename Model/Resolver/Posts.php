<?php
declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MageOS\Blog\Model\ResourceModel\Post\CollectionFactory as PostCollectionFactory;

class Posts implements ResolverInterface
{
    private PostCollectionFactory $postCollectionFactory;

    public function __construct(
        PostCollectionFactory $postCollectionFactory
    ) {
        $this->postCollectionFactory = $postCollectionFactory;
    }

    public function resolve(
        Field $field,
              $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $pageSize = $args['pageSize'] ?? 20;
        $currentPage = $args['currentPage'] ?? 1;

        // Create collection for complex filtering
        $collection = $this->postCollectionFactory->create();
        $collection->addFieldToSelect('*');

        // Add store filter
        $storeId = $context->getExtensionAttributes()->getStore()->getId();
        $collection->addStoreFilter($storeId);

        // Apply filters
        if (isset($args['filter'])) {
            $this->applyFilters($collection, $args['filter']);
        }

        // Apply sorting
        if (isset($args['sort'])) {
            $this->applySorting($collection, $args['sort']);
        } else {
            // Default sorting by publish_time DESC
            $collection->setOrder('publish_time', 'DESC');
        }

        // Apply pagination
        $collection->setPageSize($pageSize);
        $collection->setCurPage($currentPage);

        $items = [];
        foreach ($collection as $post) {
            $items[] = $this->formatPostData($post);
        }

        return [
            'items' => $items,
            'page_info' => [
                'page_size' => $pageSize,
                'current_page' => $currentPage,
                'total_pages' => $collection->getLastPageNumber()
            ],
            'total_count' => $collection->getSize()
        ];
    }

    private function applyFilters($collection, array $filters): void
    {
        foreach ($filters as $field => $condition) {
            if ($field === 'category_id') {
                $this->applyCategoryFilter($collection, $condition);
            } elseif ($field === 'tag_id') {
                $this->applyTagFilter($collection, $condition);
            } else {
                $this->applyFieldFilter($collection, $field, $condition);
            }
        }
    }

    private function applyCategoryFilter($collection, array $condition): void
    {
        $value = $condition['eq'] ?? $condition['in'] ?? null;
        if ($value !== null) {
            $collection->getSelect()->join(
                ['pc' => $collection->getTable('blog_post_category')],
                'main_table.post_id = pc.post_id',
                []
            );

            if (is_array($value)) {
                $collection->getSelect()->where('pc.category_id IN (?)', $value);
            } else {
                $collection->getSelect()->where('pc.category_id = ?', $value);
            }
        }
    }

    private function applyTagFilter($collection, array $condition): void
    {
        $value = $condition['eq'] ?? $condition['in'] ?? null;
        if ($value !== null) {
            $collection->getSelect()->join(
                ['pt' => $collection->getTable('blog_post_tag')],
                'main_table.post_id = pt.post_id',
                []
            );

            if (is_array($value)) {
                $collection->getSelect()->where('pt.tag_id IN (?)', $value);
            } else {
                $collection->getSelect()->where('pt.tag_id = ?', $value);
            }
        }
    }

    private function applyFieldFilter($collection, string $field, array $condition): void
    {
        foreach ($condition as $operator => $value) {
            switch ($operator) {
                case 'eq':
                    $collection->addFieldToFilter($field, ['eq' => $value]);
                    break;
                case 'neq':
                    $collection->addFieldToFilter($field, ['neq' => $value]);
                    break;
                case 'like':
                    $collection->addFieldToFilter($field, ['like' => '%' . $value . '%']);
                    break;
                case 'in':
                    $collection->addFieldToFilter($field, ['in' => $value]);
                    break;
                case 'nin':
                    $collection->addFieldToFilter($field, ['nin' => $value]);
                    break;
                case 'gt':
                    $collection->addFieldToFilter($field, ['gt' => $value]);
                    break;
                case 'lt':
                    $collection->addFieldToFilter($field, ['lt' => $value]);
                    break;
                case 'gteq':
                    $collection->addFieldToFilter($field, ['gteq' => $value]);
                    break;
                case 'lteq':
                    $collection->addFieldToFilter($field, ['lteq' => $value]);
                    break;
            }
        }
    }

    private function applySorting($collection, array $sort): void
    {
        foreach ($sort as $field => $direction) {
            $collection->setOrder($field, $direction);
        }
    }

    public function formatPostData($post): array
    {
        return [
            'post_id' => (int)$post->getPostId(),
            'title' => $post->getTitle(),
            'identifier' => $post->getIdentifier(),
            'content' => $this->contentFormatter($post->getContent()),
            'short_content' => $post->getShortContent(),
            'content_heading' => $post->getContentHeading(),
            'featured_img' => $post->getFeaturedImg(),
            'featured_img_alt' => $post->getFeaturedImgAlt(),
            'meta_title' => $post->getMetaTitle(),
            'meta_description' => $post->getMetaDescription(),
            'meta_keywords' => $post->getMetaKeywords(),
            'is_active' => (bool)$post->getIsActive(),
            'publish_time' => $post->getPublishTime(),
            'creation_time' => $post->getCreationTime(),
            'update_time' => $post->getUpdateTime(),
            'views_count' => (int)$post->getViewsCount(),
            'comments_count' => (int)$post->getCommentsCount(),
            'reading_time' => (int)$post->getReadingTime(),
            'og_title' => $post->getOgTitle(),
            'og_description' => $post->getOgDescription(),
            'og_img' => $post->getOgImg(),
            'og_type' => $post->getOgType(),
            'categories' => $this->getPostCategories($post->getPostId()),
            'tags' => $this->getPostTags($post->getPostId())
        ];
    }

    public function contentFormatter($content)
    {
        // used for plugins
        return $content;
    }

    private function getPostCategories(int $postId): array
    {
        $collection = $this->postCollectionFactory->create();
        $collection->getSelect()->reset()
            ->from(['pc' => $collection->getTable('blog_post_category')], [])
            ->join(
                ['c' => $collection->getTable('blog_category')],
                'pc.category_id = c.category_id',
                ['category_id', 'title', 'identifier', 'is_active']
            )
            ->where('pc.post_id = ?', $postId)
            ->where('c.is_active = ?', 1);

        $categories = [];
        foreach ($collection->getConnection()->fetchAll($collection->getSelect()) as $category) {
            $categories[] = [
                'category_id' => (int)$category['category_id'],
                'title' => $category['title'],
                'identifier' => $category['identifier'],
                'is_active' => (bool)$category['is_active']
            ];
        }

        return $categories;
    }

    private function getPostTags(int $postId): array
    {
        $collection = $this->postCollectionFactory->create();
        $collection->getSelect()->reset()
            ->from(['pt' => $collection->getTable('blog_post_tag')], [])
            ->join(
                ['t' => $collection->getTable('blog_tag')],
                'pt.tag_id = t.tag_id',
                ['tag_id', 'title', 'identifier', 'is_active']
            )
            ->where('pt.post_id = ?', $postId)
            ->where('t.is_active = ?', 1);

        $tags = [];
        foreach ($collection->getConnection()->fetchAll($collection->getSelect()) as $tag) {
            $tags[] = [
                'tag_id' => (int)$tag['tag_id'],
                'title' => $tag['title'],
                'identifier' => $tag['identifier'],
                'is_active' => (bool)$tag['is_active']
            ];
        }

        return $tags;
    }
}
