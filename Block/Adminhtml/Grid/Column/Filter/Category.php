<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Adminhtml\Grid\Column\Filter;

use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column\Filter\Select;
use Magento\Framework\DB\Helper;
use MageOS\Blog\Model\ResourceModel\Category\CollectionFactory;

/**
 * @TODO get rid of Select and use UI component
 */
class Category extends Select
{
    protected CollectionFactory $collectionFactory;

    public function __construct(
        Context $context,
        Helper $resourceHelper,
        CollectionFactory $collectionFactory,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context, $resourceHelper, $data);
    }

    /**
     * @return array
     */
    protected function _getOptions(): array
    {
        $options = [];
        $options[] = ['value' => '', 'label' => __('All Categories')];
        foreach ($this->collectionFactory->create()->load() as $item) {
            $options[] = ['value' => $item->getId(), 'label' => $item->getTitle()];
        };
        return $options;
    }
}
