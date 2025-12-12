<?php
declare(strict_types=1);

namespace MageOS\Blog\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class PagePaginationType implements OptionSourceInterface
{

    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'page', 'label' => __('?page=N')],
            ['value' => 'p', 'label' => __('?p=N')],
            ['value' => '2', 'label' => __('/page/N')]
        ];
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
}
