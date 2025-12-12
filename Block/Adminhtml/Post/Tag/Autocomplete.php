<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Adminhtml\Post\Tag;

use \Magento\Framework\Registry;
use \Magento\Backend\Block\Template;
use \Magento\Backend\Block\Template\Context;

/**
 * @TODO Refactor to get Registered Tags
 */
class Autocomplete extends Template
{
    protected Registry $registry;

    public function __construct(
        Context $context,
        Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
    }

    /**
     * @return bool|false|string
     */
    public function getLinkedTags(): bool|string
    {
        $post = $this->registry->registry('current_model');
        if ($post) {
            $tagsCollection = $post->getRelatedTags();
            $tagsTitles = [];
            foreach ($tagsCollection as $tag) {
                $tagsTitles[] = $tag->getData('title');
            }
            $tagsTitles = array_unique($tagsTitles);
        } else {
            $tagsTitles = [];
        }
        return json_encode($tagsTitles);
    }

    /**
     * @return string
     */
    public function getAutocompleteUrl(): string
    {
        return $this->getUrl('blog/tag/autocomplete');
    }
}
