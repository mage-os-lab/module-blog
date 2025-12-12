<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Sidebar;

use Magento\Framework\Exception\NoSuchEntityException;
use MageOS\Blog\Block\Post\PostList\AbstractList;
use MageOS\Blog\Model\Config;

class Recent extends AbstractList
{
    use Widget;

    protected string $_widgetKey = 'recent_posts';

    public function _construct()
    {
        $this->setPageSize(
            Config::MAX_NUMBER_OF_RECENT_POSTS
        );
        return parent::_construct();
    }

    /**
     * Prepare posts collection
     *
     * @return void
     * @throws NoSuchEntityException
     */
    protected function _preparePostCollection(): void
    {
        parent::_preparePostCollection();
        $this->_postCollection->addRecentFilter();
    }
}
