<?php
declare(strict_types=1);

namespace MageOS\Blog\Model\Config\Source;

use Magento\Config\Model\Config\Source\Design\Robots;

/**
 * Class Tag Robots Model
 */
class TagRobots extends Robots
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = parent::toOptionArray();
        array_unshift($options, ['value' => '', 'label' => 'Use config settings']);
        return $options;
    }
}
