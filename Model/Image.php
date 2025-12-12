<?php
declare(strict_types=1);

namespace MageOS\Blog\Model;

use MageOS\Blog\Model\Url;
use MageOS\Blog\Helper\Image as ImageHelper;

/**
 * Image model
 *
 * @method string getFile()
 * @method $this setFile(string $value)
 */
class Image extends \Magento\Framework\DataObject
{

    /**
     * @var \MageOS\Blog\Model\Url
     */
    protected $url;

    /**
     * @var \MageOS\Blog\Helper\Image
     */
    protected $imageHelper;

    /**
     * Initialize dependencies.
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \MageOS\Blog\Model\Url $url
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        Url $url,
        ImageHelper $imageHelper,
        array $data = []
    ) {
        parent::__construct($data);
        $this->url = $url;
        $this->imageHelper = $imageHelper;
    }

    /**
     * Retrieve image url
     * @return string
     */
    public function getUrl()
    {
        if ($this->getFile()) {
            return $this->url->getMediaUrl($this->getFile());
        }

        return null;
    }

    /**
     * Resize image
     * @param int $width
     * @param int $height
     * @return string
     */
    public function resize($width, $height = null)
    {
        return $this->imageHelper->init($this->getFile())
            ->resize($width, $height);
    }

    /**
     * Retrieve image url
     * @return string
     */
    public function __toString()
    {
        return $this->getUrl();
    }
}
