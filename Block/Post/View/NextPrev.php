<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Post\View;

use Magento\Framework\View\Element\Template;
use MageOS\Blog\Model\Config;

class NextPrev extends Template
{
    protected $_template = 'MageOS_Blog::post/view/nextprev.phtml';

    /**
     * Previous post
     *
     * @var \MageOS\Blog\Model\Post
     */
    protected $_prevPost;

    /**
     * Next post
     *
     * @var \MageOS\Blog\Model\Post
     */
    protected $_nextPost;

    /**
     * @var \MageOS\Blog\Model\ResourceModel\Post\CollectionFactory
     */
    protected $_postCollectionFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \MageOS\Blog\Model\ResourceModel\Post\CollectionFactory $postCollectionFactory,
        \Magento\Framework\Registry $coreRegistry,
        array $data = [],
    ) {
        parent::__construct($context, $data);
        $this->_postCollectionFactory = $postCollectionFactory;
        $this->_coreRegistry = $coreRegistry;

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    }

    /**
     * Retrieve true if need to display next-prev links
     *
     * @return boolean
     */
    public function displayLinks()
    {
        return (bool)$this->_scopeConfig->getValue(
            Config::XML_PATH_POST_NEXTPREV,
            Config::SCOPE_STORE
        );
    }

    /**
     * Retrieve prev post
     * @return \MageOS\Blog\Model\Post || bool
     */
    public function getPrevPost()
    {
        if ($this->_prevPost === null) {
            $this->_prevPost = false;
            $currentPost = $this->getPost();
            $collection = $this->_getFrontendCollection()->addFieldToFilter(
                'publish_time',
                ['gteq' => $currentPost->getPublishTime()]
            )->setOrder('publish_time', 'ASC');

            $post = $collection->getFirstItem();

            if ($currentPost->getPublishTime() == $post->getPublishTime()) {

                $collection = $this->_postCollectionFactory->create();
                $collection->addActiveFilter()
                    ->addStoreFilter($this->_storeManager->getStore()->getId())
                    ->addFieldToFilter('publish_time', $currentPost->getPublishTime())
                    ->setOrder('post_id', 'DESC');

                if ($collection->getFirstItem()->getId() != $currentPost->getId()) {
                    foreach ($collection as $item) {
                        if ($item->getId() != $currentPost->getId()) {
                            $post = $item;
                        } else {
                            break;
                        }
                    }
                } else {
                    $collection = $this->_getFrontendCollection()->addFieldToFilter(
                        'publish_time',
                        ['gt' => $this->getPost()->getPublishTime()]
                    )->setOrder('publish_time', 'ASC');
                    $post = $collection->getFirstItem();
                }
            }

            if ($post->getId()) {
                $this->_prevPost = $post;
            }
        }

        return $this->_prevPost;
    }

    /**
     * Retrieve next post
     * @return \MageOS\Blog\Model\Post || bool
     */
    public function getNextPost()
    {
        if ($this->_nextPost === null) {
            $this->_nextPost = false;
            $currentPost = $this->getPost();
            $collection = $this->_getFrontendCollection()->addFieldToFilter(
                'publish_time',
                ['lteq' => $currentPost->getPublishTime()]
            )->setOrder('publish_time', 'DESC');

            $post = $collection->getFirstItem();

            if ($currentPost->getPublishTime() == $post->getPublishTime()) {

                $collection = $this->_postCollectionFactory->create();
                $collection->addActiveFilter()
                    ->addStoreFilter($this->_storeManager->getStore()->getId())
                    ->addFieldToFilter('publish_time', $currentPost->getPublishTime())
                    ->setOrder('post_id', 'ASC');

                if ($collection->getFirstItem()->getId() != $currentPost->getId()) {
                    foreach ($collection as $item) {
                        if ($item->getId() != $currentPost->getId()) {
                            $post = $item;
                        } else {
                            break;
                        }
                    }
                } else {
                    $collection = $this->_getFrontendCollection()->addFieldToFilter(
                        'publish_time',
                        ['lt' => $this->getPost()->getPublishTime()]
                    )->setOrder('publish_time', 'DESC');
                    $post = $collection->getFirstItem();
                }
            }

            if ($post->getId()) {
                $this->_nextPost = $post;
            }
        }
        return $this->_nextPost;
    }

    /**
     * Retrieve post collection with frontend filters and order
     * @return bool
     */
    protected function _getFrontendCollection()
    {
        $collection = $this->_postCollectionFactory->create();
        $collection->addActiveFilter()
            ->addFieldToFilter('post_id', ['neq' => $this->getPost()->getId()])
            ->addStoreFilter($this->_storeManager->getStore()->getId())
            ->setPageSize(1);
        return $collection;
    }

    /**
     * Retrieve post instance
     *
     * @return \MageOS\Blog\Model\Post
     */
    public function getPost()
    {
        return $this->_coreRegistry->registry('current_blog_post');
    }
}
