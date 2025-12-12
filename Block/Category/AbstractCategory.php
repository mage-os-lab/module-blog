<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Category;

use Exception;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use MageOS\Blog\Api\Data\BlogCategoryInterface;
use MageOS\Blog\Model\Url;

/**
 * @TODO refactor class
 */
abstract class AbstractCategory extends Template
{
    protected FilterProvider $_filterProvider;
    protected Registry $_coreRegistry;
    protected Url $_url;

    public function __construct(
        Context $context,
        Registry $coreRegistry,
        FilterProvider $filterProvider,
        Url $url,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_coreRegistry = $coreRegistry;
        $this->_filterProvider = $filterProvider;
        $this->_url = $url;
    }

    /**
     * Retrieve category instance
     *
     * @return BlogCategoryInterface
     */
    public function getCategory(): BlogCategoryInterface
    {
        return $this->_coreRegistry->registry('current_blog_category');
    }

    /**
     * Retrieve post content
     *
     * @return string
     * @throws Exception
     */
    public function getContent(): string
    {
        $category = $this->getCategory();
        $key = 'filtered_content';
        if (!$category->hasData($key)) {
            $content = $this->_filterProvider->getPageFilter()->filter(
                (string) $category->getContent() ?: ''
            );
            $category->setData($key, $content);
        }
        return $category->getData($key);
    }
}
