<?php
declare(strict_types=1);

namespace MageOS\Blog\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Registry;
use Magento\Framework\App\RequestInterface;
use MageOS\Blog\Model\Config;

class LayoutLoadBeforeObserver implements ObserverInterface
{
    protected Registry $registry;
    protected RequestInterface $request;
    protected Config $config;

    public function __construct(
        Registry $registry,
        RequestInterface $request,
        Config $config
    ) {
        $this->registry = $registry;
        $this->request = $request;
        $this->config = $config;
    }

    /**
     * Page block html topmenu gethtml before
     *
     * @param Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(Observer $observer): void
    {
        if ($this->config->isEnabled()) {

            $post = $this->registry->registry('current_blog_post');
            $layout = $observer->getLayout();

            if ($post && $post->getIsPreviewMode()) {
                $layout->getUpdate()->addHandle('blog_non_cacheable');
            }

            if (!$this->config->isBlogCssIncludeOnAll()) {
                if ($this->config->isBlogCssIncludeOnHome() && $this->request->getFullActionName() === 'cms_index_index') {
                    $layout->getUpdate()->addHandle('blog_css');
                }

                if ($this->config->isBlogCssIncludeOnProduct() && $this->request->getFullActionName() === 'catalog_product_view') {
                    $layout->getUpdate()->addHandle('blog_css');
                }
            } else {
                $layout->getUpdate()->addHandle('blog_css');
            }

        }
    }
}
