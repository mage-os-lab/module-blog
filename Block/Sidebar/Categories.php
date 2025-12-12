<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Sidebar;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\DataObject\IdentityInterface;

/**
 * Blog sidebar categories block
 */
class Categories extends Template implements IdentityInterface
{
    use Widget;

    /**
     * @var string
     */
    protected $_widgetKey = 'categories';

    /**
     * @var \MageOS\Blog\Model\ResourceModel\Category\Collection
     */
    protected $_categoryCollection;
    /**
     * Construct
     *
     * @param \Magento\Framework\View\Element\Context $context
     * @param \MageOS\Blog\Model\ResourceModel\Category\Collection $categoryCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \MageOS\Blog\Model\ResourceModel\Category\Collection $categoryCollection,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_categoryCollection = $categoryCollection;
    }

    /**
     * Get grouped categories
     * @return \MageOS\Blog\Model\ResourceModel\Category\Collection
     */
    public function getGroupedChilds()
    {
        $k = 'grouped_childs';
        if (!$this->hasData($k)) {
            $array = $this->_categoryCollection
                ->addActiveFilter()
                ->addStoreFilter($this->_storeManager->getStore()->getId())
                ->addFieldToFilter('include_in_sidebar_tree', 1)
                ->setOrder('position')
                ->getTreeOrderedArray();
            foreach ($array as $key => $item) {
                $maxDepth = $this->maxDepth();
                if ($maxDepth > 0 && $item->getLevel() >= $maxDepth) {
                    unset($array[$key]);
                }
            }

            $this->setData($k, $array);
        }

        return $this->getData($k);
    }

    /**
     * Retrieve true if need to show posts count
     * @return int
     */
    public function showPostsCount()
    {
        $key = 'show_posts_count';
        if (!$this->hasData($key)) {
            $this->setData($key, true);//may be set as sys config
        }
        return $this->getData($key);
    }

    /**
     * Retrieve categories maximum depth
     * @return int
     */
    public function maxDepth(): int
    {
        return 3; //may be set as sys config
    }

    /**
     * Retrieve block identities
     *
     * @return array
     */
    public function getIdentities()
    {
        $identities = [];
        foreach ($this->getGroupedChilds() as $item) {
            $identities = array_merge($identities, $item->getIdentities());
        }

        return array_unique($identities);
    }
}
