<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Adminhtml\Post;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use MageOS\Blog\Block\Adminhtml\GenericButton;

class SaveAndContinueButton extends GenericButton implements ButtonProviderInterface
{
    const string MODEL_AUTHORIZATION = "MageOS_Blog::post_save";

    /**
     * @return array
     */
    public function getButtonData(): array
    {
        if (!$this->authorization->isAllowed(self::MODEL_AUTHORIZATION)) {
            return [];
        }

        return [
            'label' => __('Save and Continue Edit'),
            'class' => 'save',
            'data_attribute' => [
                'mage-init' => [
                    'button' => ['event' => 'saveAndContinueEdit'],
                ],
            ],
            'sort_order' => 80,
        ];
    }
}
