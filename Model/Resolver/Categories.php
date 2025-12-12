<?php
declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MageOS\Blog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;

class Categories implements ResolverInterface
{
    private CategoryCollectionFactory $categoryCollectionFactory;

    public function __construct(
        CategoryCollectionFactory $categoryCollectionFactory
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
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

        $collection = $this->categoryCollectionFactory->create();
        $collection->addFieldToSelect('*');

        // Add store filter
        $storeId = $context->getExtensionAttributes()->getStore()->getId();
        $collection->addStoreFilter($storeId);

        // Apply filters
        if (isset($args['filter'])) {
            $this->applyFilters($collection, $args['filter']);
        }

        // Default sorting by position
        $collection->setOrder('position', 'ASC');

        // Apply pagination
        $collection->setPageSize($pageSize);
        $collection->setCurPage($currentPage);

        $items = [];
        foreach ($collection as $category) {
            $items[] = $this->formatCategoryData($category);
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

    private function formatCategoryData($category): array
    {
        return [
            'category_id' => (int)$category->getCategoryId(),
            'title' => $category->getTitle(),
            'identifier' => $category->getIdentifier(),
            'content' => $category->getContent(),
            'content_heading' => $category->getContentHeading(),
            'meta_title' => $category->getMetaTitle(),
            'meta_description' => $category->getMetaDescription(),
            'meta_keywords' => $category->getMetaKeywords(),
            'is_active' => (bool)$category->getIsActive(),
            'position' => (int)$category->getPosition(),
            'include_in_menu' => (bool)$category->getIncludeInMenu(),
            'posts_count' => $this->getCategoryPostsCount($category->getCategoryId())
        ];
    }

    private function getCategoryPostsCount(int $categoryId): int
    {
        $collection = $this->categoryCollectionFactory->create();
        $select = $collection->getConnection()->select()
            ->from(['pc' => $collection->getTable('blog_post_category')], ['COUNT(*)'])
            ->join(
                ['p' => $collection->getTable('blog_post')],
                'pc.post_id = p.post_id',
                []
            )
            ->where('pc.category_id = ?', $categoryId)
            ->where('p.is_active = ?', 1);

        return (int)$collection->getConnection()->fetchOne($select);
    }
}
