<?php

namespace MageOS\Blog\App\Action;

use Magento\Framework\App\Config\ScopeConfigInterface;
use MageOS\Blog\Model\Config;

/**
 * Abstract Blog frontend action controller
 */
abstract class Action extends \Magento\Framework\App\Action\Action
{
    /**
     * Retrieve true if blog extension is enabled.
     *
     * @return bool
     */
    protected function moduleEnabled(): bool
    {
        return (bool) $this->getConfigValue(
            Config::XML_PATH_ENABLED,
            Config::SCOPE_STORE
        );
    }

    /**
     * Retrieve store config value
     *
     * @return string | null | bool
     */
    protected function getConfigValue($path): bool|string|null
    {
        $config = $this->_objectManager->get(ScopeConfigInterface::class);
        return $config->getValue(
            $path,
            Config::SCOPE_STORE
        );
    }

    /**
     * Throw control to cms_index_noroute action.
     *
     * @return void
     */
    protected function _forwardNoroute(): void
    {
        $this->_forward('index', 'noroute', 'cms');
    }
}
