<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Adminhtml\Category;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use MageOS\Blog\Block\Adminhtml\GenericButton;

class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    const string MODEL_AUTHORIZATION = "MageOS_Blog::category_delete";

    /**
     * @return array
     */
    public function getButtonData(): array
    {
        $data = [];

        if (!$this->authorization->isAllowed(self::MODEL_AUTHORIZATION)) {
            return $data;
        }

        if ($this->getObjectId()) {
            $data = [
                'label' => __('Delete'),
                'class' => 'delete',
                'on_click' => 'deleteConfirm(\'' . __(
                        'Are you sure you want to do this?'
                    ) . '\', \'' . $this->getDeleteUrl() . '\')',
                'sort_order' => 20,
            ];
        }
        return $data;
    }

    /**
     * @return string
     */
    public function getDeleteUrl(): string
    {
        return $this->getUrl('*/*/delete', ['id' => $this->getObjectId()]);
    }
}
