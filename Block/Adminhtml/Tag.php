<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Adminhtml;

use Magento\Backend\Block\Widget\Grid\Container;

/**
 * @TODO remove this class
 */
class Tag extends Container
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_controller = 'adminhtml_tag';
        $this->_blockGroup = 'MageOS_Blog';
        $this->_headerText = __('Tag');
        $this->_addButtonLabel = __('Add New Tag');

        parent::_construct();
        if (!$this->_authorization->isAllowed("MageOS_Blog::tag_save")) {
            $this->removeButton('add');
        }
    }

    /**
     * @return $this
     */
    protected function _prepareLayout(): static
    {
        if ($this->_authorization->isAllowed("MageOS_Blog::import")) {
            $onClick = "setLocation('" . $this->getUrl('*/import') . "')";

            $this->getToolbar()->addChild(
                'options_button',
                \Magento\Backend\Block\Widget\Button::class,
                ['label' => __('Import Tags'), 'onclick' => $onClick]
            );
        }
        return parent::_prepareLayout();
    }
}
