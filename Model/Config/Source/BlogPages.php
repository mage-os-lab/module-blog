<?php
declare(strict_types=1);
namespace MageOS\Blog\Model\Config\Source;

class BlogPages implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options int
     *
     * @return array
     */
    public function toOptionArray()
    {
        return  [
            ['value' => \MageOS\Blog\Model\Config::CANONICAL_PAGE_TYPE_NONE, 'label' => __('Please select')],
            ['value' => \MageOS\Blog\Model\Config::CANONICAL_PAGE_TYPE_ALL, 'label' => __('All Blog Pages')],
            ['value' => \MageOS\Blog\Model\Config::CANONICAL_PAGE_TYPE_INDEX, 'label' => __('Blog Index Page')],
            ['value' => \MageOS\Blog\Model\Config::CANONICAL_PAGE_TYPE_POST, 'label' => __('Blog Post Page')],
            ['value' => \MageOS\Blog\Model\Config::CANONICAL_PAGE_TYPE_CATEGORY, 'label' => __('Blog Category Page')],
            ['value' => \MageOS\Blog\Model\Config::CANONICAL_PAGE_TYPE_TAG, 'label' => __('Blog Tag Page')],
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $array = [];
        foreach ($this->toOptionArray() as $item) {
            $array[$item['value']] = $item['label'];
        }
        return $array;
    }
}
