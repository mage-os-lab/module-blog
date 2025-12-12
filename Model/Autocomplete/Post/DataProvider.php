<?php

namespace MageOS\Blog\Model\Autocomplete\Post;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Search\Model\Autocomplete\DataProviderInterface;
use Magento\Search\Model\Autocomplete\ItemFactory;
use Magento\Search\Model\QueryFactory;
use MageOS\Blog\Model\Config;
use MageOS\Blog\Model\Post;
use MageOS\Blog\Model\ResourceModel\Post\CollectionFactory;

class DataProvider implements DataProviderInterface
{

    protected ScopeConfigInterface $scopeConfig;
    protected ItemFactory $itemFactory;
    protected QueryFactory $queryFactory;
    private  CollectionFactory $postCollectionFactory;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ItemFactory $itemFactory,
        QueryFactory $queryFactory,
        CollectionFactory $postCollectionFactory
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->itemFactory = $itemFactory;
        $this->queryFactory = $queryFactory;
        $this->postCollectionFactory = $postCollectionFactory;
        //add store manager
    }

    // this will return in the ajax call
    public function getItems(): array
    {
        $maxAutocompleteResults = 5; //set config for this
        $result = [];
        $enableBlogSearch = $this->scopeConfig->getValue(
            Config::XML_PATH_SEARCH_ENABLE,
            Config::SCOPE_STORE,
            //$storeId use store manager
        );
        if ($enableBlogSearch) {
            $postCollection = $this->getBlogPostCollection();
            $i = 0;
            if ($postCollection) {
                /** @var Post $post */
                foreach ($postCollection as $post) {
                    $result[] = $this->itemFactory->create([
                        'title' => $post->getTitle(),
                        'url'   => $post->getPostUrl(),
                        'type' => 'post']);
                    $i++;
                    if ($i == $maxAutocompleteResults) {
                        break;
                    }
                }
            }
            //extend this for categories and tags
        }

        return $result;
    }

    private function getSearchTerms()
    {
        return $this->queryFactory->get()->getQueryText();
    }

    private function hasSearchTerms(): bool
    {
        return !empty($this->getSearchTerms());
    }

    private function getBlogPostCollection()
    {
        if ($this->hasSearchTerms()) {
            $postCollection = $this->postCollectionFactory->create();
            $postCollection->addFieldToFilter('title', ['like' => '%' . $this->getSearchTerms() . '%']);
            // may need to limit the collection
            // filter by store use store manager
            return $postCollection;
        }

        return false;
    }


}
