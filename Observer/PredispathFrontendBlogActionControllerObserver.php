<?php
declare(strict_types=1);

namespace MageOS\Blog\Observer;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use MageOS\Blog\Model\Config;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use MageOS\Blog\Model\NoSlashUrlRedirect;
use MageOS\Blog\Model\SlashUrlRedirect;

class PredispathFrontendBlogActionControllerObserver implements ObserverInterface
{
    protected NoSlashUrlRedirect $noSlashUrlRedirect;
    protected SlashUrlRedirect $slashUrlRedirect;
    protected ScopeConfigInterface $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        NoSlashUrlRedirect $noSlashUrlRedirect,
        SlashUrlRedirect $slashUrlRedirect = null
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->noSlashUrlRedirect = $noSlashUrlRedirect;
        $this->slashUrlRedirect = $slashUrlRedirect ?: ObjectManager::getInstance()
            ->get(SlashUrlRedirect::class);
    }

    /**
     * @param Observer $observer
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer): void
    {
        $redirectToNoSlash = $this->scopeConfig->getValue(
            Config::XML_PATH_PERMALINK_REDIRECT_TO_NO_SLASH,
            ScopeInterface::SCOPE_STORE
        );

        if ($redirectToNoSlash) {
            $this->noSlashUrlRedirect->execute($observer);
        } else {
            $this->slashUrlRedirect->execute($observer);
        }
    }
}
