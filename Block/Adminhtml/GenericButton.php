<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Adminhtml;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\AuthorizationInterface;

class GenericButton
{
    protected AuthorizationInterface $authorization;

    /**
     * GenericButton constructor.
     * @param Context $context
     * @param AuthorizationInterface|null $authorization
     */
    public function __construct (
        protected Context $context,
        ?AuthorizationInterface $authorization = null
    ) {
        $this->authorization = $authorization
            ?: \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\AuthorizationInterface::class
            );
    }

    /**
     * Return CMS block ID
     *
     * @return int|null
     */
    public function getObjectId(): ?int
    {
        return (int)$this->context->getRequest()->getParam('id');
    }

    /**
     * Generate url by route and parameters
     *
     * @param string $route
     * @param array $params
     * @return  string
     */
    public function getUrl(string $route = '', array $params = []): string
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
