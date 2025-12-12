<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Post;

use Magento\Store\Model\ScopeInterface;
use MageOS\Blog\Model\Config;

/**
 * Blog post info block
 */
class Info extends \Magento\Framework\View\Element\Template
{
    /**
     * Block template file
     * @var string
     */
    protected $_template = 'MageOS_Blog::post/info.phtml';

    /**
     * Retrieve formated posted date
     * @var string
     * @deprecated Use $post->getPublishDate() instead
     * @return string
     */
    public function getPostedOn($format = 'Y-m-d H:i:s')
    {
        return $this->getPost()->getPublishDate($format);
    }

    /**
     * @return bool
     */
    public function viewsCountEnabled()
    {
        return (bool)$this->_scopeConfig->getValue(
            Config::XML_PATH_POST_VIEW_COUNT,
            Config::SCOPE_STORE
        );
    }

    /**
     * Retrieve 1 if display reading time is enabled
     * @return int
     */
    public function readingTimeEnabled()
    {
        return (int) $this->_scopeConfig->getValue(
            Config::XML_PATH_POST_READING_TIME,
            Config::SCOPE_STORE
        );
    }
}
