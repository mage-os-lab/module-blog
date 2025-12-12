<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Sidebar;

use MageOS\Blog\Block\Post\PostList\AbstractList;
use MageOS\Blog\Model\Config;

/**
 * Blog sidebar categories block
 */
class Popular extends AbstractList
{
    use Widget;

    /**
     * @var string
     */
    protected $_widgetKey = 'popular_posts';

    /**
     * @return $this
     */
    public function _construct()
    {
        $this->setPageSize(
            Config::MAX_NUMBER_OF_RECENT_POSTS
        );
        return parent::_construct();
    }

    /**
     * Retrieve collection order field
     *
     * @return string
     */
    public function getCollectionOrderField(): string
    {
        return 'views_count';
    }
}
