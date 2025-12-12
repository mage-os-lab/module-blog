<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Widget;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Widget\Block\BlockInterface;
use MageOS\Blog\Block\Post\PostList\AbstractList;
use MageOS\Blog\Block\Sidebar\Widget;

class Featured extends AbstractList implements BlockInterface
{
    use Widget;

    /**
     * @var string
     */
    protected $_widgetKey = 'featured_posts';

    /**
     * Prepare posts collection
     *
     * @return void
     * @throws NoSuchEntityException
     */
    protected function _preparePostCollection(): void
    {
        parent::_preparePostCollection();
        $this->_postCollection->addPostsFilter(
            $this->getPostIdsConfigValue()
        );

        $ids = [];
        foreach (explode(',', $this->getPostIdsConfigValue()) as $id) {
            $id = (int)trim($id);
            if ($id) {
                $ids[] = $id;
            }
        }

        if ($ids) {
            $ids = implode(',', $ids);
            $this->_postCollection->getSelect()->order(
                new \Zend_Db_Expr('FIELD(`main_table`.`post_id`,' . $ids .')')
            );
        }
    }



    /**
     * Set blog template
     *
     * @return this
     */
    public function _toHtml(): string
    {
        $this->setTemplate(
            $this->getData('custom_template') ?: 'MageOS_Blog::widget/recent.phtml'
        );

        return \Magento\Framework\View\Element\Template::_toHtml();
    }

    /**
     * Retrieve block title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->getData('title') ?: '';
    }

    /**
     * Retrieve post ids string
     *
     * @return string
     */
    protected function getPostIdsConfigValue(): string
    {
        return (string)$this->getData('posts_ids');
    }

    /**
     * Retrieve post short content
     *
     * @param  \MageOS\Blog\Model\Post $post
     * @param  mixed $len
     * @param  mixed $endCharacters
     * @return string
     */
    public function getShorContent($post, $len = null, $endCharacters = null)
    {
        return $post->getShortFilteredContent($len, $endCharacters);
    }

    /**
     * Get relevant path to template
     * Skip parent one as it use template for sidebar block
     *
     * @return string
     */
    public function getTemplate(): string
    {
        return AbstractList::getTemplate();
    }
}
