<?php

declare(strict_types=1);

namespace MageOS\Blog\Block\Adminhtml\Tag\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton implements ButtonProviderInterface
{
    public function __construct(
        private readonly Context $context,
        private readonly RequestInterface $request
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getButtonData(): array
    {
        $tagId = (int) $this->request->getParam('tag_id');
        if ($tagId <= 0) {
            return [];
        }

        return [
            'label' => (string) __('Delete'),
            'class' => 'delete',
            'on_click' => \sprintf(
                "deleteConfirm('%s', '%s')",
                __('Are you sure you want to delete this tag?'),
                $this->context->getUrlBuilder()->getUrl('*/*/delete', ['tag_id' => $tagId])
            ),
            'sort_order' => 20,
        ];
    }
}
