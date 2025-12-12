<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Adminhtml\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use MageOS\Blog\Block\Adminhtml\GenericButton;

class PreviewButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @return array
     */
    public function getButtonData(): array
    {
        $data = [];
        if ($this->getObjectId()) {
            $data = [
                'label' => __('Preview'),
                'class' => 'preview',
                'on_click' => 'window.open(\'' . $this->getPreviewUrl() . '\');',
                'sort_order' => 35,
            ];
        }
        return $data;
    }

    /**
     * @return string
     */
    public function getPreviewUrl(): string
    {
        return $this->getUrl('*/*/preview', ['id' => $this->getObjectId()]);
    }
}
