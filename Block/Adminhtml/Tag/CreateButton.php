<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Adminhtml\Tag;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use MageOS\Blog\Block\Adminhtml\GenericButton;

class CreateButton extends GenericButton implements ButtonProviderInterface
{
    const string MODEL_AUTHORIZATION = "MageOS_Blog::tag_save";

    /**
     * @return array
     */
    public function getButtonData(): array
    {
        if (!$this->authorization->isAllowed(self::MODEL_AUTHORIZATION)) {
            return [];
        }

        return [
            'label' => __('Save'),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => ['button' => ['event' => 'save']],
                'form-role' => 'save',
            ],
            'sort_order' => 90,
        ];
    }
}
