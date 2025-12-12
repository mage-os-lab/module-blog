<?php

namespace MageOS\Blog\Block\Adminhtml\Grid\Column\Render;

use MageOS\Blog\Model\Url;
use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;

class Image extends AbstractRenderer
{
    public function __construct(
        Url $url,
        Context $context,
        array $data = []
    ) {
        $this->_url = $url;
        parent::__construct($context, $data);
    }

    /**
     * @param DataObject $row
     * @return string
     */
    public function render(DataObject $row): string
    {
        $imagePath = $row->getData($this->getColumn()->getIndex());
        if ($imagePath) {
            return '<img src="' .  $this->_escaper->escapeHtml($this->_url->getMediaUrl($imagePath)) . '" alt="" width="75"/>';
        }
        return '';
    }
}
