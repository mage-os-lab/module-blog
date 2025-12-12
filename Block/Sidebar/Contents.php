<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Sidebar;

use Magento\Framework\View\Element\Template;
use Magento\Framework\Registry;
use MageOS\Blog\Model\Config;
use MageOS\Blog\Model\Post;

/**
 * Blog contents sidebar block
 */
class Contents extends Template
{
    use Widget;

    /**
     * @var string
     */
    protected $_widgetKey = 'contents';



    /**
     * @var Registry
     */
    private $coreRegistry;

    public function __construct(
        Template\Context $context,
        Registry $coreRegistry,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->coreRegistry = $coreRegistry;
    }

    /**
     * Retrieve content items from post model
     *
     * @return array
     */
    public function getContentItems(): array
    {
        if (!$this->_scopeConfig->getValue(
            Config::XML_PATH_SIDEBAR_CONTENTS_ENABLED,
            Config::SCOPE_STORE
        )) {
            return [];
        }

        if ($post = $this->getPost()) {
            /** @var Post $post */
            return $post->getContentItems();
        }

        return [];
    }

    /**
     * Retrieve posts instance
     *
     * @return \MageOS\Blog\Model\Post
     */
    public function getPost()
    {
        if (!$this->hasData('post')) {
            $this->setData(
                'post',
                $this->coreRegistry->registry('current_blog_post')
            );
        }
        return $this->getData('post');
    }

}
