<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Sidebar;

use MageOS\Blog\Model\Config;

/**
 * Blog sidebar widget trait
 */
trait Widget
{
    /**
     * Retrieve block sort order
     * @return int
     */
    public function getSortOrder(): int
    {
        if (!$this->hasData('sort_order')) {
            $this->setData('sort_order', $this->_scopeConfig->getValue(
                Config::MODULE_SYS_KEY.'/'.Config::SYS_SIDEBAR.'/'.$this->_widgetKey.'/sort_order',
                Config::SCOPE_STORE
            ));
        }
        return (int) $this->getData('sort_order');
    }

    /**
     * Retrieve block html
     *
     * @return string
     */
    protected function _toHtml(): string
    {
        if ($this->_scopeConfig->getValue(
            Config::MODULE_SYS_KEY.'/'.Config::SYS_SIDEBAR.'/'.$this->_widgetKey.'/enabled',
            Config::SCOPE_STORE
        )) {
            return parent::_toHtml();
        }

        return '';
    }

    /**
     * Retrieve widget key
     *
     * @return string
     */
    public function getWidgetKey(): string
    {
        return $this->_widgetKey;
    }

    /**
     * Get cache key informative items
     *
     * @return array
     */
    public function getCacheKeyInfo(): array
    {
        $result = parent::getCacheKeyInfo();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $result['customer_group_id'] = $objectManager->get(\Magento\Customer\Model\Session::class)->getCustomerGroupId();
        return $result;
    }
}
