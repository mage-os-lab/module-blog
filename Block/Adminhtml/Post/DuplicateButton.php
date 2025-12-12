<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Adminhtml\Post;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use MageOS\Blog\Block\Adminhtml\GenericButton;

class DuplicateButton extends GenericButton implements ButtonProviderInterface
{
    const string MODEL_AUTHORIZATION = "MageOS_Blog::post_save";

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
                'label' => __('Duplicate'),
                'class' => 'duplicate',
                'on_click' => 'window.location=\'' . $this->getDuplicateUrl() . '\'',
                'sort_order' => 40,
            ];
        }
        return $data;
    }

    /**
     * @return string
     */
    public function getDuplicateUrl(): string
    {
        return $this->getUrl('*/*/duplicate', ['id' => $this->getObjectId()]);
    }
}
