<?php

declare(strict_types=1);

namespace MageOS\Blog\Block\Sidebar;

use Magento\Framework\View\Element\Template;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Api\Data\TagInterface;
use MageOS\Blog\Model\ResourceModel\Tag\CollectionFactory;

class Tags extends Template
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
     * @return TagInterface[]
     */
    public function getTags(): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(TagInterface::IS_ACTIVE, 1);
        $collection->setOrder(TagInterface::TITLE, 'ASC');
        $collection->getSelect()
            ->join(
                ['store_link' => $collection->getTable('mageos_blog_tag_store')],
                'store_link.tag_id = main_table.tag_id',
                []
            )
            ->where('store_link.store_id IN (?)', [(int) $this->storeManager->getStore()->getId(), 0])
            ->distinct(true);

        /** @var TagInterface[] $items */
        $items = $collection->getItems();

        return $items;
    }

    public function getTagUrl(TagInterface $tag): string
    {
        return $this->getUrl('blog/tag/' . $tag->getUrlKey());
    }
}
