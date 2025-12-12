<?php
declare(strict_types=1);
namespace MageOS\Blog\Block\Post\View;

use Magento\Store\Model\ScopeInterface;

/**
 * Blog post view opengraph
 */
class Opengraph extends \MageOS\Blog\Block\Post\AbstractPost
{
    /**
     * Retrieve page type
     *
     * @return string
     */
    public function getType()
    {
        return $this->stripTags(
            $this->getPost()->getOgType()
        );
    }

    /**
     * Retrieve page title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->stripTags(
            $this->getPost()->getOgTitle()
        );
    }

    /**
     * Retrieve page short description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->stripTags(
            $this->getPost()->getOgDescription()
        );
    }

    /**
     * Retrieve page url
     *
     * @return string
     */
    public function getPageUrl()
    {
        return $this->stripTags(
            $this->getPost()->getPostUrl()
        );
    }

    /**
     * Retrieve page main image
     *
     * @return string | null
     */
    public function getImage()
    {
        $image = $this->getPost()->getOgImage();

        if (!$image) {
            $image = $this->getPost()->getFeaturedImage();
        }

        if (!$image) {
            $image = $this->getPost()->getFirstImage();
        }

        if ($image) {
            return $this->stripTags($image);
        }
    }
}
