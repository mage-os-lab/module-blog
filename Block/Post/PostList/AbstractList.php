<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Post\PostList;

use Magento\Cms\Model\Page;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Api\SortOrder;
use \Magento\Framework\View\Element\Template;
use \Magento\Framework\DataObject\IdentityInterface;
use MageOS\Blog\Model\Config;
use MageOS\Blog\Model\ResourceModel\Post\Collection;
use MageOS\Blog\Model\ResourceModel\Post\CollectionFactory;
use MageOS\Blog\Model\Url;
use MageOS\Blog\ViewModel\Style;

abstract class AbstractList extends Template implements IdentityInterface
{
    protected FilterProvider $_filterProvider;
    protected Page $_post;
    protected Registry $_coreRegistry;
    protected CollectionFactory $_postCollectionFactory;
    protected $_postCollection;
    protected Url $_url;

    /**
     * @var Config
     */
    protected mixed $config;

    const string POSTS_SORT_FIELD_BY_PUBLISH_TIME = 'main_table.publish_time';
    const string POSTS_SORT_FIELD_BY_POSITION = 'position';
    const string POSTS_SORT_FIELD_BY_TITLE = 'main_table.title';

    public function __construct(
        Context $context,
        Registry $coreRegistry,
        FilterProvider $filterProvider,
        CollectionFactory $postCollectionFactory,
        Url $url,
        array $data = [],
        $config = null
    ) {
        parent::__construct($context, $data);
        $this->_coreRegistry = $coreRegistry;
        $this->_filterProvider = $filterProvider;
        $this->_postCollectionFactory = $postCollectionFactory;
        $this->_url = $url;

        $objectManager = ObjectManager::getInstance();
        $this->config = $config ?: $objectManager->get(
            Config::class
        );
    }

    /**
     * Prepare posts collection
     *
     * @return void
     * @throws NoSuchEntityException
     */
    protected function _preparePostCollection(): void
    {
        $this->_postCollection = $this->_postCollectionFactory->create()
            ->addActiveFilter()
            ->addStoreFilter($this->_storeManager->getStore()->getId())
            ->setOrder($this->getCollectionOrderField(), $this->getCollectionOrderDirection());

        if ($this->getPageSize()) {
            $this->_postCollection->setPageSize($this->getPageSize());
        }
    }

    /**
     * Retrieve collection order field
     *
     * @return string
     */
    public function getCollectionOrderField(): string
    {
        return self::POSTS_SORT_FIELD_BY_PUBLISH_TIME;
    }

    /**
     * Retrieve collection order direction
     *
     * @return string
     */
    public function getCollectionOrderDirection(): string
    {
        return SortOrder::SORT_DESC;
    }

    /**
     * Prepare posts collection
     *
     * @return Collection
     * @throws NoSuchEntityException
     */
    public function getPostCollection(): Collection
    {
        if (null === $this->_postCollection) {
            $this->_preparePostCollection();
        }

        return $this->_postCollection;
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml(): string
    {
        if (!$this->_scopeConfig->getValue(
            Config::XML_PATH_ENABLED,
            Config::SCOPE_STORE
        )) {
            return '';
        }

        return parent::_toHtml();
    }

    /**
     * Retrieve identities
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getIdentities(): array
    {
        $identities = [];
        $identities[] = \MageOS\Blog\Model\Post::CACHE_TAG . '_' . 0;
        foreach ($this->getPostCollection() as $item) {
            $identities = array_merge($identities, $item->getIdentities());
        }

        return array_unique($identities);
    }

    /**
     * Get cache key informative items
     *
     * @return array
     */
    public function getCacheKeyInfo(): array
    {
        return array_merge(
            parent::getCacheKeyInfo(),
            [$this->getNameInLayout()]
        );
    }

    /**
     * @return bool
     */
    public function viewsCountEnabled(): bool
    {
        return (bool)$this->_scopeConfig->getValue(
            Config::XML_PATH_POST_VIEW_COUNT,
            Config::SCOPE_STORE
        );
    }

    /**
     * @return Style
     */
    public function getStyleViewModel(): Style
    {
        $viewModel = $this->getData('style_view_model');
        if (!$viewModel) {
            $viewModel = ObjectManager::getInstance()
                ->get(Style::class);
            $this->setData('style_view_model', $viewModel);
        }

        return $viewModel;
    }

    /**
     * This will help for extending configuration
     * @return string
     */
    public function getPageParamName(): string
    {
        return 'page';
    }
}
