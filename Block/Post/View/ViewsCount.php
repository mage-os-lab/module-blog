<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Post\View;

use MageOS\Blog\Block\Post\AbstractPost;
use MageOS\Blog\Model\Config;

class ViewsCount extends AbstractPost
{
    /**
     * Retrieve counter controller url
     * @return string
     */
    public function getCounterUrl()
    {
        return $this->getUrl('blog/post/viewscount', [
            'id' => $this->getPost()->getId()
        ]);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->_scopeConfig->getValue(
            Config::XML_PATH_POST_VIEW_COUNT,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        )) {
            return parent::_toHtml();
        }
        return '';
    }
}
