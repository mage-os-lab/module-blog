<?php

declare(strict_types=1);

namespace MageOS\Blog\Block\Adminhtml\Author\Edit;

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
        $authorId = (int) $this->request->getParam('author_id');
        if ($authorId <= 0) {
            return [];
        }

        return [
            'label' => (string) __('Delete'),
            'class' => 'delete',
            'on_click' => \sprintf(
                "deleteConfirm('%s', '%s')",
                __('Are you sure you want to delete this author?'),
                $this->context->getUrlBuilder()->getUrl('*/*/delete', ['author_id' => $authorId])
            ),
            'sort_order' => 20,
        ];
    }
}
