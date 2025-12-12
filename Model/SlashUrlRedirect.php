<?php
declare(strict_types=1);
namespace MageOS\Blog\Model;

use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class SlashUrlRedirect Model
 */
class SlashUrlRedirect
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var UrlInterface
     */
    protected $urlInterface;

    /**
     * @var \Magento\Framework\App\ActionFlag
     */
    protected $actionFlag;

    /**
     * NoSlashUrlRedirect constructor.
     * @param UrlInterface $urlInterface
     * @param ActionFlag $actionFlag
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        UrlInterface $urlInterface,
        ActionFlag $actionFlag,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->actionFlag = $actionFlag;
        $this->urlInterface = $urlInterface;
    }

    /**
     * @param Observer $observer
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer): void
    {
        $moduleEnabled = $this->scopeConfig->getValue(Config::XML_PATH_ENABLED, Config::SCOPE_STORE);

        if ($moduleEnabled) {
            $currentUrl = $this->urlInterface->getCurrentUrl();
            $result = explode('?', $currentUrl);


            foreach ([
                Url::CONTROLLER_POST,
                Url::CONTROLLER_CATEGORY,
                Url::CONTROLLER_TAG
            ] as $controllerName) {

                $controllerSufix = $this->scopeConfig->getValue(
                    Config::MODULE_SYS_KEY.'/'.Config::SYS_PERMALINK.'/'.$controllerName . '_sufix',
                    Config::SCOPE_STORE
                );
                if ($controllerSufix) {
                    if (strpos($result[0], $controllerSufix) == strlen($result[0]) - strlen($controllerSufix)) {
                        return;
                    }
                }
            }

            $result[0] = trim($result[0], '/') . '/';
            $urlSlash = implode('?', $result);

            if ($urlSlash != $currentUrl) {
                $controller = $observer->getEvent()->getData('controller_action');
                if ($controller->getRequest()->isXmlHttpRequest()
                    || $controller->getRequest()->isPost()
                ) {
                    return;
                }
                $this->actionFlag->set('', \Magento\Framework\App\ActionInterface::FLAG_NO_DISPATCH, true);
                $controller->getResponse()->setRedirect($urlSlash, 301)->sendResponse();
            }
        }
    }
}
