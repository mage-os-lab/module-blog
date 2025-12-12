<?php
declare(strict_types=1);

namespace MageOS\Blog\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Page\Config;
use MageOS\Blog\Model\Config as BlogConfig;

class LayoutGenerateBlocksAfter implements ObserverInterface
{
    private Config $pageConfig;
    private BlogConfig $blogConfig;

    public function __construct(
        Config $pageConfig,
        BlogConfig $blogConfig
    ) {
        $this->pageConfig = $pageConfig;
        $this->blogConfig = $blogConfig;
    }

    /**
     * Add rel prev and rel next
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $availableActions = [
            'blog_category_view',
            'blog_index_index',
            'blog_tag_view',
            'blog_post_view',
        ];
        $actionName = $observer->getEvent()->getFullActionName();
        if (!in_array($actionName, $availableActions)) {
            return;
        }

        if ('blog_index_index' == $actionName) {
            $displayMode = $this->blogConfig->getConfig(
                BlogConfig::XML_PATH_INDEX_PAGE_DISPLAY_MODE
            );

            if (2 == $displayMode) {
                return;
            }
        }
        $productListBlock = $observer->getEvent()->getLayout()->getBlock('blog.posts.list');
        if (!$productListBlock) {
            return;
        }

        $toolbar = $productListBlock->getToolbarBlock();
        $toolbar->setCollection($productListBlock->getPostCollection());

        $pagerBlock = $toolbar->getPagerBlock();
        if (!($pagerBlock instanceof \Magento\Framework\DataObject)) {
            return;
        }

        if (1 < $pagerBlock->getCurrentPage()) {
            $this->pageConfig->addRemotePageAsset(
                $pagerBlock->getPageUrl(
                    $pagerBlock->getCollection()->getCurPage(-1)
                ),
                'link_rel',
                ['attributes' => ['rel' => 'prev']]
            );
        }
        if ($pagerBlock->getCurrentPage() < $pagerBlock->getLastPageNum()) {
            $this->pageConfig->addRemotePageAsset(
                $pagerBlock->getPageUrl(
                    $pagerBlock->getCollection()->getCurPage(+1)
                ),
                'link_rel',
                ['attributes' => ['rel' => 'next']]
            );
        }
    }
}
