<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Adminhtml;

use Magento\Backend\Block\Widget\Grid\Container;

/**
 * @TODO refactor this class to use the new way of creating admin grids
 */
class Category extends Container
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_controller = 'adminhtml';
        $this->_blockGroup = 'MageOS_Blog';
        $this->_headerText = __('Category');
        $this->_addButtonLabel = __('Add New Category');
        parent::_construct();
        if (!$this->_authorization->isAllowed("MageOS_Blog::category_save")) {
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
                ['label' => __('Import Categories'), 'onclick' => $onClick]
            );
        }
        return parent::_prepareLayout();
    }
}
