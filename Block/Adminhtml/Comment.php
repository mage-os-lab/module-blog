<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Adminhtml;

use Magento\Backend\Block\Widget\Grid\Container;

/**
 * @TODO refactor this class to use the new way of creating admin grids
 */
class Comment extends Container
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_controller = 'adminhtml_comment';
        $this->_blockGroup = 'MageOS_Blog';
        //$this->_addButtonLabel = __('Add New Comment');
        parent::_construct();
        $this->removeButton('add');
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
                ['label' => __('Import Comments'), 'onclick' => $onClick]
            );
        }
        return parent::_prepareLayout();
    }
}
