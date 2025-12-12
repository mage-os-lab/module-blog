<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Tag;

use \Magento\Framework\View\Element\Template;
use \Magento\Framework\DataObject\IdentityInterface;

/**
 * Blog tag abstract block
 */
abstract class AbstractTag extends Template implements IdentityInterface
{
    /**
     * @var \Magento\Cms\Model\Template\FilterProvider
     */
    protected $_filterProvider;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \MageOS\Blog\Model\Url
     */
    protected $_url;

    /**
     * Construct
     *
     * @param \Magento\Framework\View\Element\Context $context

     * @param \Magento\Framework\Registry $coreRegistry,
     * @param \Magento\Cms\Model\Template\FilterProvider $filterProvider
     * @param \MageOS\Blog\Model\Url $url
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        \MageOS\Blog\Model\Url $url,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_coreRegistry = $coreRegistry;
        $this->_filterProvider = $filterProvider;
        $this->_url = $url;
    }

    /**
     * Retrieve tag instance
     *
     * @return \MageOS\Blog\Model\Tag
     */
    public function getTag()
    {
        return $this->_coreRegistry->registry('current_blog_tag');
    }

    /**
     * Retrieve tag content
     *
     * @return string
     */
    public function getContent()
    {
        $tag = $this->getTag();
        $key = 'filtered_content';
        if (!$tag->hasData($key)) {
            $cotent = $this->_filterProvider->getPageFilter()->filter(
                (string) $tag->getContent() ?: ''
            );
            $tag->setData($key, $cotent);
        }
        return $tag->getData($key);
    }

    public function getIdentities()
    {
        return $this->getTag()->getIdentities();
    }
}
