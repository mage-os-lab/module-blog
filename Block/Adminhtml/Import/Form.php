<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Adminhtml\Import;

use Magento\Backend\Block\Widget\Form\Container;

/**
 * @TODO refactor class
 */
class Form extends Container
{

    protected function _construct(): void
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'MageOS_Blog';
        $this->_controller = 'adminhtml_import';
        $this->_mode = 'form';

        parent::_construct();

        if (!$this->_isAllowedAction('MageOS_Blog::import')) {
            $this->buttonList->remove('save');
        } else {
            $this->updateButton(
                'save',
                'label',
                __('Start Import')
            );
        }

        $this->buttonList->remove('delete');
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction(string $resourceId): bool
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    /**
     * Get form save URL
     *
     * @see getFormActionUrl()
     * @return string
     */
    public function getSaveUrl(): string
    {
        $type = $this->getRequest()->getParam('type');

        if ($type === 'csv') {
            return $this->getUrl('blog/import/mapping');
        }

        return $this->getUrl('*/*/run', ['_current' => true]);
    }
}
