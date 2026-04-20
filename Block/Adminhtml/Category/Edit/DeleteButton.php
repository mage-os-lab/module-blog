<?php

declare(strict_types=1);

namespace MageOS\Blog\Block\Adminhtml\Category\Edit;

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
        $categoryId = (int) $this->request->getParam('category_id');
        if ($categoryId <= 0) {
            return [];
        }

        return [
            'label' => (string) __('Delete'),
            'class' => 'delete',
            'on_click' => \sprintf(
                "deleteConfirm('%s', '%s')",
                __('Are you sure you want to delete this category?'),
                $this->context->getUrlBuilder()->getUrl('*/*/delete', ['category_id' => $categoryId])
            ),
            'sort_order' => 20,
        ];
    }
}
