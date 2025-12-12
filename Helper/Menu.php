<?php
declare(strict_types=1);

namespace MageOS\Blog\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Data\Tree\Node;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Api\Data\BlogCategoryInterface;
use MageOS\Blog\Model\Config;
use MageOS\Blog\Model\ResourceModel\Category\CollectionFactory;
use MageOS\Blog\Model\Url;

class Menu extends AbstractHelper
{
    protected Url $url;
    protected Registry $registry;
    protected CollectionFactory $categoryCollectionFactory;
    protected StoreManagerInterface $storeManager;

    public function __construct(
        Context $context,
        Url $url,
        Registry $registry,
        CollectionFactory $categoryCollectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->url = $url;
        $this->registry = $registry;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * Retrieve blog menu nodes
     * @param mixed $menu
     * @param mixed $tree
     * @return \Magento\Framework\Data\Tree\Node | null
     * @throws NoSuchEntityException
     */
    public function getBlogNode($menu = null, $tree = null)
    {
        if (!$this->scopeConfig->isSetFlag(Config::XML_PATH_MENU_DISPLAY_BLOG_LINK, ScopeInterface::SCOPE_STORE)) {
            return;
        }

        if (!$this->scopeConfig->isSetFlag(Config::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE)) {
            return;
        }

        if (null == $tree) {
            $tree = new \Magento\Framework\Data\Tree;
        }

        $addedNodes = [];

        $data = [
            'name'      => $this->scopeConfig->getValue(
                Config::XML_PATH_MENU_LINK_TEXT,
                ScopeInterface::SCOPE_STORE
            ),
            'id'        => 'mageos-blog',
            'url'       => $this->url->getBaseUrl(),
            'is_active' => ($this->_request->getModuleName() == 'blog')
        ];

        $addedNodes[0] = new Node($data, 'id', $tree, $menu);

        $includeCategories = $this->scopeConfig->getValue(
            Config::XML_PATH_MENU_INCLUDE_BLOG_CATEGORIES,
            ScopeInterface::SCOPE_STORE
        );

        if ($includeCategories) {
            $maxDepth = $this->scopeConfig->getValue(
                Config::XML_PATH_MENU_MAX_DEPTH,
                ScopeInterface::SCOPE_STORE
            );

            $items = $this->getGroupedChilds();
            $currentCategoryId = $this->getCurrentCategory()
                ? $this->getCurrentCategory()->getId()
                : 0;

            foreach ($items as $item) {
                $parentId = (int) $item->getParentId();

                if (!isset($addedNodes[$parentId])) {
                    continue;
                }

                if ($maxDepth > 0 && $item->getLevel() >= $maxDepth) {
                    continue;
                }

                $data = [
                    'name'      => $item->getTitle(),
                    'id'        => 'mageos-blog-category-' . $item->getId(),
                    'url'       => $item->getCategoryUrl(),
                    'is_active' => $currentCategoryId == $item->getId()
                ];

                $addedNodes[$item->getId()] = new Node($data, 'id', $tree, $menu);
                $addedNodes[$parentId]->addChild(
                    $addedNodes[$item->getId()]
                );
            }
        }

        return $addedNodes[0];
    }

    /**
     * Retrieve sorted array of categories
     * @return array
     * @throws NoSuchEntityException
     */
    protected function getGroupedChilds(): array
    {
        return $this->categoryCollectionFactory->create()
            ->addActiveFilter()
            ->addStoreFilter($this->storeManager->getStore()->getId())
            ->addFieldToFilter(BlogCategoryInterface::INCLUDE_IN_MENU, 1)
            ->setOrder(BlogCategoryInterface::POSITION)
            ->getTreeOrderedArray();
    }

    /**
     * Retrieve current blog category
     * @return \MageOS\Blog\Model\Category | null
     */
    protected function getCurrentCategory()
    {
        return $this->registry->registry('current_blog_category');
    }
}
