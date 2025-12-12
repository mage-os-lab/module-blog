<?php
declare(strict_types=1);

namespace MageOS\Blog\Observer;

use Magento\Framework\Data\Tree\Node;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use MageOS\Blog\Helper\Menu;

class PageBlockHtmlTopMenuGetHtmlBeforeObserver implements ObserverInterface
{
    protected Menu $menuHelper;

    public function __construct(
        Menu $menuHelper
    ) {
        $this->menuHelper = $menuHelper;
    }

    /**
     * Page block html topmenu gethtml before
     *
     * @param Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer): void
    {
        /** @var Node $menu */
        $menu = $observer->getMenu();
        $tree = $menu->getTree();

        $blogNode = $this->menuHelper->getBlogNode($menu, $tree);
        if ($blogNode) {
            $menu->addChild($blogNode);
        }
    }
}
