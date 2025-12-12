<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Adminhtml\Comment;

use MageOS\Blog\Block\Adminhtml\GenericButton;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class ReplyButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @inheirtDoc
     */
    public function getButtonData(): array
    {
        return [
            'label' => __('Reply'),
            'on_click' => sprintf("location.href = '%s';", $this->getBackUrl()),
            'class' => 'reply',
            'sort_order' => 10
        ];
    }

    /**
     * Get URL for back (reset) button
     *
     * @return string
     */
    public function getBackUrl(): string
    {
        return $this->getUrl('*/*/addreply/', ['id' => $this->getObjectId()]);
    }
}
