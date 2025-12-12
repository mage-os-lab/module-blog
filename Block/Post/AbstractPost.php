<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Post;

use Magento\Store\Model\ScopeInterface;
use MageOS\Blog\Model\Config;

/**
 * Abstract post мшуц block
 */
abstract class AbstractPost extends \Magento\Framework\View\Element\Template
{

    /**
     * Deprecated property. Do not use it.
     * @var \Magento\Cms\Model\Template\FilterProvider
     */
    protected $_filterProvider;

    /**
     * @var \MageOS\Blog\Model\Post
     */
    protected $_post;

    /**
     * Page factory
     *
     * @var \MageOS\Blog\Model\PostFactory
     */
    protected $_postFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var string
     */
    protected $_defaultPostInfoBlock = \MageOS\Blog\Block\Post\Info::class;

    /**
     * @var \MageOS\Blog\Model\Url
     */
    protected $_url;

    /**
     * @var \MageOS\Blog\Model\Config
     */
    protected $config;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \MageOS\Blog\Model\Post $post,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        \MageOS\Blog\Model\PostFactory $postFactory,
        \MageOS\Blog\Model\Url $url,
        array $data = [],
        $config = null,
    ) {
        parent::__construct($context, $data);
        $this->_post = $post;
        $this->_coreRegistry = $coreRegistry;
        $this->_filterProvider = $filterProvider;
        $this->_postFactory = $postFactory;
        $this->_url = $url;

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->config = $config ?: $objectManager->get(
            \MageOS\Blog\Model\Config::class
        );
    }

    /**
     * Retrieve post instance
     *
     * @return \MageOS\Blog\Model\Post
     */
    public function getPost()
    {
        if (!$this->hasData('post')) {
            $this->setData(
                'post',
                $this->_coreRegistry->registry('current_blog_post')
            );
        }
        return $this->getData('post');
    }

    /**
     * Retrieve post short content
     *
     * @param  mixed $len
     * @param  mixed $endCharacters
     * @return string
     */
    public function getShorContent($len = null, $endCharacters = null)
    {
        return $this->getPost()->getShortFilteredContent($len, $endCharacters);
    }

    public function getShortFilteredContentWithoutImages($len = null, $endCharacters = null)
    {
        return $this->getPost()->getShortFilteredContentWithoutImages($len, $endCharacters);
    }

    /**
     * Retrieve post content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->getPost()->getFilteredContent();
    }

    /**
     * Retrieve post info html
     *
     * @return string
     */
    public function getInfoHtml()
    {
        return $this->getInfoBlock()->toHtml();
    }

    /**
     * Retrieve post info block
     *
     * @return \MageOS\Blog\Block\Post\Info
     */
    public function getInfoBlock()
    {
        $k = 'info_block';
        if (!$this->hasData($k)) {
            $blockName = $this->getPostInfoBlockName();
            if ($blockName) {
                $block = $this->getLayout()->getBlock($blockName);
            }

            if (empty($block)) {
                $block = $this->getLayout()->createBlock($this->_defaultPostInfoBlock, uniqid(microtime()));
            }

            $this->setData($k, $block);
        }

        return $this->getData($k)->setPost($this->getPost());
    }

    /**
     * @return bool
     */
    public function viewsCountEnabled()
    {
        return (bool)$this->_scopeConfig->getValue(
            Config::XML_PATH_POST_VIEW_COUNT,
            Config::SCOPE_STORE
        );
    }

    /**
     * @return \MageOS\Blog\ViewModel\Style
     */
    public function getStyleViewModel()
    {
        $viewModel = $this->getData('style_view_model');
        if (!$viewModel) {
            $viewModel = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\MageOS\Blog\ViewModel\Style::class);
            $this->setData('style_view_model', $viewModel);
        }

        return $viewModel;
    }

    /**
     * Check if AddThis Enabled and key exist
     *
     * @return int
     */
    public function displayAddThisToolbox(): int
    {
        $isSocialEnabled = $this->_scopeConfig->getValue(
            Config::XML_PATH_SOCIAL_SHARE_ENABLED,
            Config::SCOPE_STORE
        );

        return (int)$isSocialEnabled;
    }

    /**
     * @return array
     */
    public function getAllowedSocialNetworks(): array
    {
        $socialNetworks = (string)$this->_scopeConfig->getValue(
            Config::XML_PATH_SOCIAL_NETWORKS,
            Config::SCOPE_STORE);

        if ($socialNetworks) {
            return explode(',', $socialNetworks);
        }
        return [];
    }
}
