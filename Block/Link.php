<?php
declare(strict_types=1);

namespace MageOS\Blog\Block;

use Magento\Store\Model\ScopeInterface;
use MageOS\Blog\Model\Config;

/**
 * Class Link block
 */
class Link extends \Magento\Framework\View\Element\Html\Link
{
    /**
     * @var \MageOS\Blog\Model\Url
     */
    protected $_url;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \MageOS\Blog\Model\Url $url
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \MageOS\Blog\Model\Url $url,
        array $data = []
    ) {
        $this->_url = $url;
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function getHref()
    {
        return $this->_url->getBaseUrl();
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->_scopeConfig->getValue(
            Config::XML_PATH_INDEX_PAGE_TITLE,
            Config::SCOPE_STORE
        );
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->_scopeConfig->getValue(
            Config::XML_PATH_ENABLED,
            Config::SCOPE_STORE
        )) {
            return '';
        }

        return parent::_toHtml();
    }
}
