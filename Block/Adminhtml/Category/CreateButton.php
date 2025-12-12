<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Adminhtml\Category;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use MageOS\Blog\Block\Adminhtml\GenericButton;

class CreateButton extends GenericButton implements ButtonProviderInterface
{
    const string MODEL_AUTHORIZATION = "MageOS_Blog::category_save";

    /**
     * @return array
     */
    public function getButtonData(): array
    {
        if (!$this->authorization->isAllowed(self::MODEL_AUTHORIZATION)) {
            return [];
        }
        return [
            'label' => __('Back'),
            'on_click' => sprintf("location.href = '%s';", $this->getBackUrl()),
            'class' => 'back',
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
        return $this->getUrl('*/*/');
    }
}
