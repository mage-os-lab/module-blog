<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Catalog\Product;

use Magento\Framework\Exception\NoSuchEntityException;
use MageOS\Blog\Block\Post\PostList\AbstractList;
use MageOS\Blog\Model\Config;

class RelatedPosts extends AbstractList
{

    /**
     * Prepare posts collection
     *
     * @return void
     * @throws NoSuchEntityException
     */
    protected function _preparePostCollection(): void
    {
        $pageSize = (int) $this->_scopeConfig->getValue(
            Config::XML_PATH_PRODUCT_PAGE_NUMBER_OF_RELATED_POSTS,
            Config::SCOPE_STORE
        );
        if (!$pageSize) {
            $pageSize = 5;
        }
        $this->setPageSize($pageSize);

        parent::_preparePostCollection();

        $product = $this->getProduct();
        $this->_postCollection->getSelect()->joinLeft(
            ['rl' => $product->getResource()->getTable('blog_post_relatedproduct')],
            'main_table.post_id = rl.post_id',
            ['position']
        )->where(
            'rl.related_id = ?',
            $product->getId()
        );
    }

    /**
     * Retrieve true if Display Related Posts enabled
     * @return boolean
     */
    public function displayPosts(): bool
    {
        return (bool) $this->_scopeConfig->getValue(
            Config::XML_PATH_PRODUCT_PAGE_RELATED_POSTS_ENABLED,
            Config::SCOPE_STORE
        );
    }

    /**
     * Retrieve posts instance
     *
     */
    public function getProduct()
    {
        if (!$this->hasData('product')) {
            $this->setData(
                'product',
                $this->_coreRegistry->registry('current_product')
            );
        }

        return $this->getData('product');
    }
}
