<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Category;

use MageOS\Blog\Model\Config\Source\CategoryDisplayMode;

class PostLinks extends PostList
{
    /**
     * Disable pagination. Display all category posts on the page
     *
     * @return $this
     */
    protected function _beforeToHtml(): static
    {
        return \MageOS\Blog\Block\Post\PostList\AbstractList::_beforeToHtml();
    }

    /**
     * Retrieve true when display of this block is allowed
     *
     * @return bool
     */
    protected function canDisplay(): bool
    {
        $displayMode = $this->getCategory()->getData('display_mode');
        return ($displayMode == CategoryDisplayMode::POSTS_AND_SUBCATEGORIES_LINKS ||
            $displayMode == CategoryDisplayMode::POST_LINKS);
    }

    /**
     * Prepare breadcrumbs
     *
     * @param null $title
     * @param null $key
     * @return void
     */
    protected function _addBreadcrumbs($title = null, $key = null): void
    {
    }

    /**
     * Get relevant path to template
     *
     * @return string
     */
    public function getTemplate(): string
    {
        return \Magento\Framework\View\Element\Template::getTemplate();
    }
}
