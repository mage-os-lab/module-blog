<?php
declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MageOS\Blog\Model\ResourceModel\Tag\CollectionFactory as TagCollectionFactory;

class Tags implements ResolverInterface
{
    private TagCollectionFactory $tagCollectionFactory;

    public function __construct(
        TagCollectionFactory $tagCollectionFactory
    ) {
        $this->tagCollectionFactory = $tagCollectionFactory;
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

        $collection = $this->tagCollectionFactory->create();
        $collection->addFieldToSelect('*');

        // Add store filter
        $storeId = $context->getExtensionAttributes()->getStore()->getId();
        $collection->addStoreFilter($storeId);

        // Apply filters
        if (isset($args['filter'])) {
            $this->applyFilters($collection, $args['filter']);
        }

        // Default sorting by title
        $collection->setOrder('title', 'ASC');

        // Apply pagination
        $collection->setPageSize($pageSize);
        $collection->setCurPage($currentPage);

        $items = [];
        foreach ($collection as $tag) {
            $items[] = $this->formatTagData($tag);
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
            $this->applyFieldFilter($collection, $field, $condition);
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
            }
        }
    }

    private function formatTagData($tag): array
    {
        return [
            'tag_id' => (int)$tag->getTagId(),
            'title' => $tag->getTitle(),
            'identifier' => $tag->getIdentifier(),
            'content' => $tag->getContent(),
            'meta_title' => $tag->getMetaTitle(),
            'meta_description' => $tag->getMetaDescription(),
            'meta_keywords' => $tag->getMetaKeywords(),
            'is_active' => (bool)$tag->getIsActive(),
            'posts_count' => $this->getTagPostsCount((int)$tag->getTagId())
        ];
    }

    private function getTagPostsCount(int $tagId): int
    {
        $collection = $this->tagCollectionFactory->create();
        $select = $collection->getConnection()->select()
            ->from(['pt' => $collection->getTable('blog_post_tag')], ['COUNT(*)'])
            ->join(
                ['p' => $collection->getTable('blog_post')],
                'pt.post_id = p.post_id',
                []
            )
            ->where('pt.tag_id = ?', $tagId)
            ->where('p.is_active = ?', 1);

        return (int)$collection->getConnection()->fetchOne($select);
    }
}
