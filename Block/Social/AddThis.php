<?php

namespace MageOS\Blog\Block\Social;

use Magento\Framework\View\Element\Template;
use MageOS\Blog\Model\Config;

class AddThis extends Template
{
    /**
     * Retrieve AddThis status
     *
     * @return boolean
     */
    public function getAddThisEnabled(): bool
    {
        return (bool)$this->_scopeConfig->getValue(
            Config::XML_PATH_SOCIAL_SHARE_ENABLED,
            Config::SCOPE_STORE
        );
    }

    /**
     * @return string
     */
    public function toHtml(): string
    {
        if (!$this->getAddThisEnabled()) {
            return '';
        }

        return parent::toHtml();
    }
}
