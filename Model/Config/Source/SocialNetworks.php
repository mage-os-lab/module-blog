<?php
declare(strict_types=1);

namespace MageOS\Blog\Model\Config\Source;

class SocialNetworks implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options int
     *
     * @return array
     */
    public function toOptionArray()
    {
        return  [
            ['value' => 'Facebook', 'label' => 'Facebook'],
            ['value' => 'Twitter', 'label' => 'X (Twitter)'],
            ['value' => 'Pinterest', 'label' => 'Pinterest'],
            ['value' => 'LinkedIn', 'label' => 'LinkedIn']
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
