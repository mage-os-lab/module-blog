<?php
declare(strict_types=1);

namespace MageOS\Blog\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class PageLayout implements OptionSourceInterface
{
    protected array $_options = [
        '' => '-- Please Select --',
        '1column' => '1 column',
        '2columns-left' => '2 columns with left bar',
        '2columns-right' => '2 columns with right bar'
    ];

    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->_options as $key => $value) {
            $options[] = ['value' => $key, 'label' => $value];
        }
        return $options;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->_options;
    }
}
