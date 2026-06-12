<?php

declare(strict_types=1);

namespace MageOS\Blog\Block\Sidebar;

use Magento\Framework\View\Element\Template;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Model\ResourceModel\Category\CollectionFactory;

class Categories extends Template
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        Template\Context $context,
        private readonly CollectionFactory $collectionFactory,
        private readonly StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return CategoryInterface[]
     */
    public function getCategories(): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(CategoryInterface::IS_ACTIVE, 1);
        $collection->addFieldToFilter(CategoryInterface::INCLUDE_IN_SIDEBAR, 1);
        $collection->setOrder(CategoryInterface::POSITION, 'ASC');
        $collection->setOrder(CategoryInterface::TITLE, 'ASC');
        $collection->getSelect()
            ->join(
                ['store_link' => $collection->getTable('mageos_blog_category_store')],
                'store_link.category_id = main_table.category_id',
                []
            )
            ->where('store_link.store_id IN (?)', [(int) $this->storeManager->getStore()->getId(), 0])
            ->distinct(true);

        /** @var CategoryInterface[] $items */
        $items = $collection->getItems();

        return $items;
    }

    public function getCategoryUrl(CategoryInterface $category): string
    {
        return $this->getUrl('blog/category/' . $category->getUrlKey());
    }
}
