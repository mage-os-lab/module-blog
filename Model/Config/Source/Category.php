<?php
declare(strict_types=1);

namespace MageOS\Blog\Model\Config\Source;

/**
 * Used in recent post widget
 *
 */
class Category implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \MageOS\Blog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var array
     */
    protected $options;


    public function __construct(
        \MageOS\Blog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->options === null) {
            $this->options = [['label' => __('Please select'), 'value' => 0]];
            $collection = $this->categoryCollectionFactory->create();
            $collection->setOrder('position')
                ->getTreeOrderedArray();

            foreach ($collection as $item) {
                $this->options[] = [
                    'label' => $this->_getSpaces($item->getLevel()) . ' ' . $item->getTitle() .
                        ($item->getIsActive() ? '' : ' ('.__('Disabled').')'),
                    'value' => $item->getId(),
                ];
            }
        }

        return $this->options;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $array = [];
        foreach ($this->toOptionArray() as $item) {
            $array[$item['value']] = $item['label'];
        }
        return $array;
    }

    /**
     * Generate spaces
     * @param  int $n
     * @return string
     */
    protected function _getSpaces($n)
    {
        $s = '';
        for ($i = 0; $i < $n; $i++) {
            $s .= '--- ';
        }

        return $s;
    }
}
