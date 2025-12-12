<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Post\View;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\View\Element\AbstractBlock;
use MageOS\Blog\Model\Config;

/**
 * Blog post related posts block
 */
class RelatedPosts extends \MageOS\Blog\Block\Post\PostList\AbstractList
{
    /**
     * Prepare posts collection
     *
     * @return void
     */
    protected function _preparePostCollection(): void
    {
        $pageSize = (int) $this->_scopeConfig->getValue(
            Config::MAX_NUMBER_OF_RELATED_POSTS,
            Config::SCOPE_STORE
        );

        $this->_postCollection = $this->getPost()->getRelatedPosts()
            ->addActiveFilter()
            ->setPageSize($pageSize ?: 5);

        $this->_postCollection->getSelect()->order('rl.position', 'ASC');
    }

    /**
     * Retrieve true if Display Related Posts enabled
     * @return boolean
     */
    public function displayPosts()
    {
        return (bool) $this->_scopeConfig->getValue(
            Config::XML_PATH_POST_RELATED_POSTS,
            Config::SCOPE_STORE
        );
    }

    /**
     * Retrieve posts instance
     *
     * @return \MageOS\Blog\Model\Category
     */
    public function getPost()
    {
        if (!$this->hasData('post')) {
            $this->setData(
                'post',
                $this->_coreRegistry->registry('current_blog_post')
            );
        }
        return $this->getData('post');
    }
}
