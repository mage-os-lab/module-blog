<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Post\View;

use Magento\Store\Model\ScopeInterface;
use MageOS\Blog\Model\Config;

/**
 * Blog post comments block
 */
class Comments extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $_localeResolver;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * Constructor
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry                      $coreRegistry
     * @param \Magento\Framework\Locale\ResolverInterface      $localeResolver
     * @param array                                            $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_coreRegistry = $coreRegistry;
        $this->_localeResolver = $localeResolver;
    }

    /**
     * Block template file
     * @var string
     */
    protected $_template = 'post/view/comments.phtml';

    /**
     * Retrieve number of comments to display
     * @return int
     */
    public function getNumberOfComments(): int
    {
        return Config::MAX_NUMBER_OF_COMMENTS;
    }

    /**
     * Retrieve locale code
     * @return string
     */
    public function getLocaleCode()
    {
        return $this->_localeResolver->getLocale();
    }

    /**
     * Retrieve posts instance
     *
     * @return \MageOS\Blog\Model\Category
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
}
