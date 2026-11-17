<?php

declare(strict_types=1);

namespace MageOS\Blog\Block\Widget;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Model\Url\Permalink;

class CategoryLink extends Template implements BlockInterface
{
    protected $_template = 'MageOS_Blog::widget/category-link.phtml';

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        Template\Context $context,
        private readonly CategoryRepositoryInterface $repository,
        private readonly Permalink $permalink,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getCategory(): ?CategoryInterface
    {
        $id = (int) $this->getData('category_id');
        if ($id <= 0) {
            return null;
        }
        try {
            return $this->repository->getById($id);
        } catch (NoSuchEntityException) {
            return null;
        }
    }

    public function getCategoryUrl(CategoryInterface $category): string
    {
        return $this->_urlBuilder->getUrl(
            '',
            ['_direct' => $this->permalink->getCategoryPath($category->getUrlKey())]
        );
    }

    public function getLabel(CategoryInterface $category): string
    {
        $label = trim((string) $this->getData('label'));
        return $label !== '' ? $label : (string) $category->getTitle();
    }
}
