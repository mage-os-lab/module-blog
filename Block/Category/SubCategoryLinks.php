<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Category;

use Magento\Framework\Exception\NoSuchEntityException;
use MageOS\Blog\Model\Config\Source\CategoryDisplayMode;
use MageOS\Blog\Model\ResourceModel\Category\Collection as CategoryCollection;

class SubCategoryLinks extends AbstractCategory
{
    protected $categoryCollectionFactory;

    public function __construct(
        \MageOS\Blog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        \MageOS\Blog\Model\Url $url,
        array $data = []
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        parent::__construct($context, $coreRegistry, $filterProvider, $url, $data);
    }

    /**
     * Get subcategories
     * @return CategoryCollection
     * @throws NoSuchEntityException
     */
    public function getSubCategories(): CategoryCollection
    {
        $subCategories = $this->categoryCollectionFactory->create();
        $subCategories
            ->addActiveFilter()
            ->addStoreFilter($this->_storeManager->getStore()->getId())
            ->setOrder('position')
            ->addFieldToFilter('category_id', ['in' => $this->getCategory()->getChildrenIds(false)]);

        return $subCategories;
    }

    /**
     * Retrieve true when display of this block is allowed
     *
     * @return bool
     */
    protected function canDisplay(): bool
    {
        $displayMode = $this->getCategory()->getData('display_mode');
        return ($displayMode == CategoryDisplayMode::SUBCATEGORIES_LINKS
            || $displayMode == CategoryDisplayMode::POSTS_AND_SUBCATEGORIES_LINKS);
    }

    /*
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml(): string
    {
        if (!$this->canDisplay()) {
            return '';
        }

        return parent::_toHtml();
    }
}
