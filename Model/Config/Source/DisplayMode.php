<?php
/**
 * Copyright © 2015-17 MageOS (support@mageos.com). All rights reserved.
 * Please visit MageOS.com for license details (https://mageos.com/end-user-license-agreement).
 *
 * Glory to Ukraine! Glory to the heroes!
 */

namespace MageOS\Blog\Model\Config\Source;

/**
 * Comment statuses
 */
class DisplayMode implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @const string
     */
    const PENDING = 0;

    /**
     * @const int
     */
    const APPROVED = 1;

    /**
     * @const int
     */
    const BLANK = 2;

    /**
     * Options int
     *
     * @return array
     */
    public function toOptionArray()
    {
        return  [
            ['value' => self::PENDING, 'label' => __('Recent Posts')],
            ['value' => self::APPROVED, 'label' => __('Featured Posts')],
            ['value' => self::BLANK, 'label' => __('Blank')],

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
