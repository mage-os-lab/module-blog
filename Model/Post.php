<?php
declare(strict_types=1);

namespace MageOS\Blog\Model;

use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Math\Random;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\ShortContentExtractorInterface;
use MageOS\Blog\Api\Data\BlogPostInterface;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\Store\Model\ScopeInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use MageOS\Blog\Model\ResourceModel\Tag\CollectionFactory as TagCollectionFactory;
use MageOS\Blog\Model\ResourceModel\Comment\CollectionFactory as CommentCollectionFactory;
use MageOS\Blog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use MageOS\Blog\Model\ResourceModel\Category\Collection as CategoryCollection;
use MageOS\Blog\Model\ResourceModel\Tag\Collection as TagCollection;
use MageOS\Blog\Model\ResourceModel\Comment\Collection as CommentCollection;
use MageOS\Blog\Model\ResourceModel\Post\Collection as PostCollection;
use MageOS\Blog\Model\Url;
use MageOS\Blog\Model\ImageFactory;

/**
 * Post model
 *
 * @method \MageOS\Blog\Model\ResourceModel\Post _getResource()
 * @method \MageOS\Blog\Model\ResourceModel\Post getResource()
 * @method int getStoreId()
 * @method $this setStoreId(int $value)
 */
class Post extends AbstractModel implements BlogPostInterface, IdentityInterface
{
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;
    const CACHE_TAG = 'rb_p';
    const GALLERY_IMAGES_SEPARATOR = ';';
    const BASE_MEDIA_PATH = 'mageos_blog';
    protected $_eventPrefix = 'blog_post';
    /**
     * Parameter name in event
     *
     * In observe method you can use $observer->getEvent()->getObject() in this case
     *
     * @var string
     */
    protected $_eventObject = 'blog_post';
    protected Random $random;
    protected FilterProvider $filterProvider;
    protected ScopeConfigInterface $scopeConfig;
    protected Url $_url;
    protected CategoryCollectionFactory $_categoryCollectionFactory;
    protected TagCollectionFactory $_tagCollectionFactory;
    protected CommentCollectionFactory $_commentCollectionFactory;
    protected ProductCollectionFactory $_productCollectionFactory;
    protected $_parentCategories;
    protected $_relatedTags;
    protected $comments;
    protected $_relatedPostsCollection;
    protected ImageFactory $imageFactory;
    protected string $controllerName;
    protected $shortContentExtractor;
    /**
     * @var array
     */
    private $contentItems = [];

    /**
     * @var CategoryRepositoryInterface|mixed
     */
    protected mixed $categoryRepository;

    public function __construct(
        Context $context,
        Registry $registry,
        Random $random,
        FilterProvider $filterProvider,
        ScopeConfigInterface $scopeConfig,
        Url $url,
        ImageFactory $imageFactory,
        CategoryCollectionFactory $categoryCollectionFactory,
        TagCollectionFactory $tagCollectionFactory,
        CommentCollectionFactory $commentCollectionFactory,
        ProductCollectionFactory $productCollectionFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [],
        CategoryRepositoryInterface $categoryRepository = null
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->filterProvider = $filterProvider;
        $this->random = $random;
        $this->scopeConfig = $scopeConfig;
        $this->_url = $url;
        $this->imageFactory = $imageFactory;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_tagCollectionFactory = $tagCollectionFactory;
        $this->_commentCollectionFactory = $commentCollectionFactory;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_relatedPostsCollection = clone($this->getCollection());
        $this->categoryRepository = $categoryRepository ?: \Magento\Framework\App\ObjectManager::getInstance()->get(
            CategoryRepositoryInterface::class
        );
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\MageOS\Blog\Model\ResourceModel\Post::class);
        $this->controllerName = URL::CONTROLLER_POST;
    }

    /**
     * Retrieve identities
     *
     * @return array
     */
    public function getIdentities()
    {
        $identities = [];

        $allIdentitiesFlag = (bool)$this->getAllIdentifiersFlag();

        if ($this->getId()) {
            $identities[] = self::CACHE_TAG . '_' . $this->getId();
        }

        $oldCategories = $this->getOrigData('categories');
        if (!is_array($oldCategories)) {
            $oldCategories = [];
        }

        $newCategories = $this->getData('categories');
        if (!is_array($newCategories)) {
            $newCategories = [];
        }

        if ($allIdentitiesFlag
            || ($this->getData('is_active') && $this->getData('is_active') != $this->getOrigData('is_active'))
        ) {
            $identities[] = self::CACHE_TAG . '_' . 0;
        }

        $isChangedCategories = count(array_diff($oldCategories, $newCategories));

        if ($allIdentitiesFlag || $isChangedCategories) {
            $changedCategories = array_unique(
                array_merge($oldCategories, $newCategories)
            );
            foreach ($changedCategories as $categoryId) {
                $identities[] = \MageOS\Blog\Model\Category::CACHE_TAG . '_' . $categoryId;
            }
        }

        $links = $this->getData('links');
        if (!empty($links['product'])) {
            foreach ($links['product'] as $productId => $linkData) {
                $identities[] = \Magento\Catalog\Model\Product::CACHE_TAG . '_' . $productId;
            }
        }

        return $identities;
    }


    /**
     * Retrieve controller name
     * @return string
     */
    public function getControllerName()
    {
        return $this->controllerName;
    }

    /**
     * Retrieve model title
     * @param  boolean $plural
     * @return string
     */
    public function getOwnTitle($plural = false)
    {
        return $plural ? 'Posts' : 'Post';
    }

    /**
     * Retrieve true if post is active
     * @return boolean [description]
     */
    public function isActive()
    {
        return ($this->getIsActive() == self::STATUS_ENABLED);
    }

    /**
     * Retrieve available post statuses
     * @return array
     */
    public function getAvailableStatuses()
    {
        return [self::STATUS_DISABLED => __('Disabled'), self::STATUS_ENABLED => __('Enabled')];
    }

    /**
     * Check if post identifier exist for specific store
     * return post id if post exists
     *
     * @param string $identifier
     * @param int $storeId
     * @return int
     */
    public function checkIdentifier($identifier, $storeId)
    {
        return $this->_getResource()->checkIdentifier($identifier, $storeId);
    }

    /**
     * Retrieve post url path
     * @return string
     */
    public function getUrl()
    {
        return $this->_url->getUrlPath($this, $this->controllerName);
    }

    /**
     * Retrieve post url
     * @return string
     */
    public function getPostUrl()
    {
        if (!$this->hasData('post_url')) {
            $url = $this->_url->getUrl($this, $this->controllerName);
            $this->setData('post_url', $url);
        }

        return $this->getData('post_url');
    }

    /**
     * Retrieve post canonical url
     * @return string
     */
    public function getCanonicalUrl()
    {
        return $this->_url->getCanonicalUrl($this);
    }

    /**
     * Retrieve featured image url
     * @return string
     */
    public function getFeaturedImage()
    {
        if (!$this->hasData('featured_image')) {
            if ($file = $this->getData('featured_img')) {
                $image = $this->_url->getMediaUrl($file);
            } else {
                $image = false;
            }
            $this->setData('featured_image', $image);
        }

        return $this->getData('featured_image');
    }

    /**
     * Retrieve featured link image url
     * @return mixed
     */
    public function getFeaturedListImage()
    {
        if (!$this->hasData('featured_list_image')) {
            if ($file = $this->getData('featured_list_img')) {
                $image = $this->_url->getMediaUrl($file);
            } else {
                $image = false;
            }
            $this->setData('featured_list_image', $image);
        }

        return $this->getData('featured_list_image');
    }

    /**
     * Set media gallery images url
     *
     * @param array $images
     * @return $this
     */
    public function setGalleryImages(array $images)
    {
        $this->setData(
            'media_gallery',
            implode(
                self::GALLERY_IMAGES_SEPARATOR,
                $images
            )
        );

        /* Reinit Media Gallery Images */
        $this->unsetData('gallery_images');
        $this->getGalleryImages();

        return $this;
    }

    /**
     * Retrieve media gallery images url
     * @return string
     */
    public function getGalleryImages()
    {
        if (!$this->hasData('gallery_images')) {
            $images = [];
            $gallery = $this->getData('media_gallery');
            if ($gallery && !is_array($gallery)) {
                $gallery = explode(
                    self::GALLERY_IMAGES_SEPARATOR,
                    $gallery
                );
            }
            if (!empty($gallery)) {
                foreach ($gallery as $file) {
                    if ($file) {
                        $images[] = $this->imageFactory->create()
                            ->setFile($file);
                    }
                }
            }
            $this->setData('gallery_images', $images);
        }

        return $this->getData('gallery_images');
    }

    /**
     * Retrieve first image url
     * @return string
     */
    public function getFirstImage()
    {
        if (!$this->hasData('first_image')) {
            $image = $this->getFeaturedImage();
            if (!$image) {
                $content = $this->getFilteredContent();
                $match = null;
                preg_match('/<img.+src=[\'"](?P<src>.+?)[\'"].*>/i', (string)$content, $match);
                if (!empty($match['src'])) {
                    $image = $match['src'];
                }
            }
            $this->setData('first_image', $image);
        }

        return $this->getData('first_image');
    }

    /**
     * Retrieve filtered content
     *
     * @return string
     */
    public function getFilteredContent()
    {
        $key = 'filtered_content';
        if (!$this->hasData($key)) {
            $content = $this->filterProvider->getPageFilter()->filter(
                (string) $this->getContent() ?: ''
            );
            $content = $this->processHeaders($content);
            $this->setData($key, $content);
        }
        return $this->getData($key);
    }

    /**
     * Process headers (h1-h3) to add IDs and generate content items
     *
     * @param string $content
     * @return string
     */
    private function processHeaders(string $content): string
    {
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $content);
        $xpath = new \DOMXPath($dom);
        $headers = $xpath->query('//h1 | //h2 | //h3');

        foreach ($headers as $header) {
            $titleText = trim($header->textContent);
            $id = strtolower(preg_replace('/[^a-z0-9]+/', '-', $titleText));
            $id = trim($id, '-');

            if (!$header->hasAttribute('id')) {
                $header->setAttribute('id', $id);
            }

            $this->contentItems[] = new \Magento\Framework\DataObject([
                'title' => $titleText,
                'link_tag' => '#' . $id
            ]);
        }

        return $dom->saveHTML();
    }

    /**
     * Get processed content items for sidebar
     *
     * @return array
     */
    public function getContentItems(): array
    {
        return $this->contentItems;
    }



    /**
     * Retrieve short filtered content
     * @param  mixed $len
     * @param  mixed $endCharacters
     * @return string
     */
    public function getShortFilteredContent($len = null, $endCharacters = null)
    {
        /* Fix for custom themes that send wrong parameters to this function, and that brings the error */
        if (is_object($len)) {
            $len = null;
        }
        /* End fix */

        $key = 'short_filtered_content' . $len;
        if (!$this->hasData($key)) {

            if ($this->getShortContent()) {
                $content = (string)$this->getShortContent() ?: '';
            } else {
                //$content = $this->getFilteredContent();
                $content = (string)$this->getContent() ?: '';
            }

            $content = $this->getShortContentExtractor()->execute($content, $len, $endCharacters);

            $this->setData($key, $content);
        }

        return $this->getData($key);
    }

    /**
     * Retrieve short filtered content,escaping imgs
     * @param  mixed $len
     * @param  mixed $endCharacters
     * @return string
     */
    public function getShortFilteredContentWithoutImages($len = null, $endCharacters = null)
    {
        return preg_replace('<img([\w\W]+?)/>', '', $this->getShortFilteredContent($len, $endCharacters));
    }

    /**
     * Retrieve og image url
     * @return string
     */
    public function getOgImage()
    {
        if (!$this->hasData('og_image')) {
            if ($file = $this->getData('og_img')) {
                $image = $this->_url->getMediaUrl($file);
            } else {
                $image = false;
            }
            $this->setData('og_image', $image);
        }

        return $this->getData('og_image');
    }

    /**
     * Retrieve post parent categories
     * @return array
     */
    public function getParentCategories()
    {
        if (null === $this->_parentCategories) {
            $this->_parentCategories = [];
            if ($this->getCategories()) {
                foreach ($this->getCategories() as $categoryId) {
                    try {
                        $category = $this->categoryRepository->getById($categoryId);
                        if ($category->getId() && $category->isVisibleOnStore((int)$this->getStoreId())) {
                            $this->_parentCategories[$categoryId] = $category;
                        }
                    } catch (NoSuchEntityException $e) {

                    }
                }
                uasort($this->_parentCategories, [$this, 'sortByPositionDesc']);
            }
        }

        return $this->_parentCategories;
    }

    /**
     * Sort by position param
     * @param $a
     * @param $b
     * @return int
     */
    public function sortByPositionDesc($a, $b): int
    {
        return strcmp((string)$b->getPosition(), (string)$a->getPosition());
    }

    /**
     * Retrieve parent category
     * @return \MageOS\Blog\Model\Category || false
     */
    public function getParentCategory()
    {
        $k = 'parent_category';
        if (null === $this->getData($k)) {
            $this->setData($k, false);
            foreach ($this->getParentCategories() as $category) {
                if ($category->isVisibleOnStore((int)$this->getStoreId())) {
                    $this->setData($k, $category);
                    break;
                }
            }
        }

        return $this->getData($k);
    }

    /**
     * Retrieve post parent categories count
     * @return int
     */
    public function getCategoriesCount()
    {
        return count($this->getParentCategories());
    }

    /**
     * Retrieve post tags
     * @return \MageOS\Blog\Model\ResourceModel\Tag\Collection
     */
    public function getRelatedTags()
    {
        if (null === $this->_relatedTags) {
            $this->_relatedTags = $this->_tagCollectionFactory->create()
                ->addFieldToFilter('tag_id', ['in' => $this->getTags()])
                ->addStoreFilter((int)$this->getStoreId())
                ->addActiveFilter()
                ->setOrder('title');
        }

        return $this->_relatedTags;
    }

    /**
     * Retrieve post tags count
     * @return int
     */
    public function getTagsCount()
    {
        return count($this->getRelatedTags());
    }

    /**
     * Retrieve post comments
     * @param  boolean $active
     * @return \MageOS\Blog\Model\ResourceModel\Comment\Collection
     */
    public function getComments($active = true)
    {
        if (null === $this->comments) {
            $this->comments = $this->_commentCollectionFactory->create()
                ->addFieldToFilter('post_id', $this->getId());
        }

        return $this->comments;
    }

    /**
     * Retrieve post related posts
     * @return \MageOS\Blog\Model\ResourceModel\Post\Collection
     */
    public function getRelatedPosts()
    {
        if (!$this->hasData('related_posts')) {
            $collection = $this->_relatedPostsCollection
                ->addFieldToFilter('post_id', ['neq' => $this->getId()])
                ->addStoreFilter((int)$this->getStoreId());
            $collection->getSelect()->joinLeft(
                ['rl' => $this->getResource()->getTable('blog_post_relatedpost')],
                'main_table.post_id = rl.related_id',
                ['position']
            )->where(
                'rl.post_id = ?',
                $this->getId()
            );
            $this->setData('related_posts', $collection);
        }
        return $this->getData('related_posts');
    }

    /**
     * Retrieve post related products
     * @return \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    public function getRelatedProducts()
    {
        if (!$this->hasData('related_products')) {
            $collection = $this->_productCollectionFactory->create();

            if ($this->getStoreId()) {
                $collection->addStoreFilter((int)$this->getStoreId());
            }

            $collection->getSelect()->joinLeft(
                ['rl' => $this->getResource()->getTable('blog_post_relatedproduct')],
                'e.entity_id = rl.related_id',
                ['position']
            )->where(
                'rl.post_id = ?',
                $this->getId()
            );

            $this->setData('related_products', $collection);
        }

        return $this->getData('related_products');
    }

    /**
     * Retrieve if is visible on store
     * @return bool
     */
    public function isVisibleOnStore($storeId)
    {
        return $this->getIsActive()
            && $this->getData(self::PUBLISH_TIME) <= $this->getResource()->getDate()->gmtDate()
            && (null === $storeId || array_intersect([0, $storeId], $this->getStoreIds()));
    }

    /**
     * Retrieve if is preview secret is valid
     * @return bool
     * @throws LocalizedException
     */
    public function isValidSecret($secret): bool
    {
        return ($secret && $this->getSecret() === $secret);
    }

    /**
     * Retrieve post publish date using format
     * @param string $format
     * @return string
     */
    public function getPublishDate(string $format = ''): string
    {
        if (!$format) {
            $format = $this->scopeConfig->getValue(
                Config::XML_PATH_DESIGN_FORMAT_DATE,
                Config::SCOPE_STORE
            );

            if (!$format) {
                $format = 'Y-m-d H:i:s';
            }
        }

        return Config::getTranslatedDate(
            $format,
            $this->getData(self::PUBLISH_TIME)
        );
    }

    /**
     * Retrieve true if post publish date display is enabled
     * @return bool
     */
    public function isPublishDateEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(
            Config::XML_PATH_DESIGN_PUBLICATION_DATE,
            Config::SCOPE_STORE
        );
    }

    /**
     * Retrieve post publish date using format
     * @param string $format
     * @return string
     */
    public function getUpdateDate(string $format = 'Y-m-d H:i:s'): string
    {
        return Config::getTranslatedDate(
            $format,
            $this->getData(self::UPDATE_TIME)
        );
    }

    /**
     * Temporary method to get images from some custom blog version. Do not use this method.
     * @return string
     */
    public function getPostImage(): string
    {
        $image = $this->getData(self::FEATURED_IMG);
        if (!$image) {
            $image = $this->getData('post_image');
        }
        return $image;
    }

    /**
     * Prepare all additional data
     * @return self
     * @deprecated replaced with getDynamicData
     */
    public function initDinamicData()
    {
        $keys = [
            'og_image',
            'og_type',
            'og_description',
            'og_title',
            'meta_description',
            'meta_title',
            'short_filtered_content',
            'filtered_content',
            'first_image',
            'featured_image',
            'post_url',
        ];

        foreach ($keys as $key) {
            $method = 'get' . str_replace(
                '_',
                '',
                ucwords($key, '_')
            );
            $this->$method();
        }

        return $this;
    }

    /**
     * Prepare all additional data
     * @param array|null $fields
     * @return array
     */
    public function getDynamicData(array $fields = null): array
    {
        $data = $this->getData();

        $keys = [
            'og_image',
            slef::OG_TYPE,
            self::OG_DESCRIPTION,
            self::OG_TITLE,
            slef::META_DESCRIPTION,
            self::META_TITLE,
            'short_filtered_content',
            'filtered_content',
            'first_image',
            'featured_image',
            'post_url',
        ];

        foreach ($keys as $key) {
            if (null === $fields || array_key_exists($key, $fields)) {
                $method = 'get' . str_replace(
                    '_',
                    '',
                    ucwords($key, '_')
                );
                $data[$key] = $this->$method();
            }
        }

        if (null === $fields || array_key_exists('tags', $fields)) {
            $tags = [];
            foreach ($this->getRelatedTags() as $tag) {
                $tags[] = $tag->getDynamicData(
                // isset($fields['tags']) ? $fields['tags'] : null
                );
            }
            $data['tags'] = $tags;
        }

        /* Do not use check for null === $fields here
         * this checks is used for API, and related data was not provided via reset */
        if (is_array($fields) && array_key_exists('related_posts', $fields)) {
            $relatedPosts = [];
            foreach ($this->getRelatedPosts() as $relatedPost) {
                $relatedPosts[] = $relatedPost->getDynamicData(
                    isset($fields['related_posts']) ? $fields['related_posts'] : null
                );
            }
            $data['related_posts'] = $relatedPosts;
        }

        /* Do not use check for null === $fields here */
        if (is_array($fields) && array_key_exists('related_products', $fields)) {
            $relatedProducts = [];
            foreach ($this->getRelatedProducts() as $relatedProduct) {
                $relatedProducts[] = $relatedProduct->getSku();
            }
            $data['related_products'] = $relatedProducts;
        }

        if (null === $fields || array_key_exists('categories', $fields)) {
            $categories = [];
            foreach ($this->getParentCategories() as $category) {
                $categories[] = $category->getDynamicData(
                    isset($fields['categories']) ? $fields['categories'] : null
                );
            }
            $data['categories'] = $categories;
        }

        return $data;
    }

    /**
     *  Duplicate post and return new object
     *
     * @return $this|Post
     * @throws LocalizedException
     * @throws AlreadyExistsException
     */
    public function duplicate(): Post
    {
        $object = clone $this;
        $object
            ->unsetData(self::POST_ID)
            ->unsetData(self::CREATION_TIME)
            ->unsetData(self::UPDATE_TIME)
            ->unsetData(self::PUBLISH_TIME)
            ->unsetData(self::IDENTIFIER)
            ->unsetData(self::COMMENTS_COUNT)
            ->setTitle($object->getTitle() . ' (' . __('Duplicated') . ')')
            ->setIsActive(0);

        $relatedProductIds = $this->getRelatedProducts()->getAllIds();
        $relatedPpostIds = $this->getRelatedPosts()->getAllIds();

        $object->setData(
            'links',
            [
                'product' => array_combine($relatedProductIds, $relatedProductIds),
                'post' => array_combine($relatedPpostIds, $relatedPpostIds),
            ]
        );
         $this->_getResource()->save($object);
         return $object;
    }

    /**
     * @return ShortContentExtractorInterface
     */
    public function getShortContentExtractor(): ShortContentExtractorInterface
    {
        if (null === $this->shortContentExtractor) {
            $this->shortContentExtractor = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(ShortContentExtractorInterface::class);
        }

        return $this->shortContentExtractor;
    }

    /**
     * For Abstraction Classes
     */
    public function getId()
    {
        return $this->getData(self::POST_ID);
    }

    /**
     * For Abstraction Classes
     */
    public function setId(mixed $postId): BlogPostInterface
    {
        return $this->setData(self::POST_ID, $postId);
    }

    /**
     * @inheritDoc
     */
    public function getPostId(): ?int
    {
        return (int)$this->getData(self::POST_ID);
    }

    /**
     * @inheritDoc
     */
    public function setPostId(int $postId): BlogPostInterface
    {
        return $this->setData(self::POST_ID, $postId);
    }

    /**
     * Retrieve active comments count
     * @inheritDoc
     */
    public function getCommentsCount(): ?int
    {
        $enableComments = $this->getEnableComments();
        if ($enableComments || $enableComments === null) {
            /*
            if (!$this->hasData('comments_count')) {
                $comments = $this->_commentCollectionFactory->create()
                    ->addFieldToFilter('post_id', $this->getId())
                    ->addActiveFilter()
                    ->addFieldToFilter('parent_id', 0);
                $this->setData('comments_count', (int)$comments->getSize());
            }
            */
            return (int)$this->getData(self::COMMENTS_COUNT);
        } else {
            return 0;
        }
    }

    /**
     * @inheritDoc
     */
    public function setCommentsCount(int $commentsCount): BlogPostInterface
    {
        return $this->setData(self::COMMENTS_COUNT, $commentsCount);
    }

    /**
     * @inheritDoc
     */
    public function getReadingTime(): ?int
    {
        if (!$this->getData(self::READING_TIME)) {
            $wpm = 250;
            $contentHtml = $this->getFilteredContent();
            $numberOfImages = substr_count(strtolower($contentHtml), '<img ');
            $additionalWordsForImages = (int)($numberOfImages * 12) / $wpm;
            $wordCount = count(preg_split('/\s+/', strip_tags($contentHtml)));

            $readingTime = 1;

            if (!$wordCount && !$additionalWordsForImages) {
                return $readingTime;
            }
            $readingTime = ceil(($wordCount + $additionalWordsForImages) / $wpm);

            $this->setData(self::READING_TIME, $readingTime);
        }

        return (int)$this->getData(self::READING_TIME);
    }

    /**
     * @inheritDoc
     */
    public function setReadingTime(int $readingTime): BlogPostInterface
    {
        return $this->setData(self::READING_TIME, $readingTime);
    }

    /**
     * @inheritDoc
     */
    public function getViewsCount(): ?int
    {
        return (int)$this->getData(self::VIEWS_COUNT);
    }

    /**
     * @inheritDoc
     */
    public function setViewsCount(int $viewsCount): BlogPostInterface
    {
        return $this->setData(self::VIEWS_COUNT, $viewsCount);
    }

    /**
     * @inheritDoc
     */
    public function getIsRecentPostsSkip(): ?int
    {
        return (int)$this->getData(self::IS_RECENT_POSTS_SKIP);
    }

    /**
     * @inheritDoc
     */
    public function setIsRecentPostsSkip(int $isRecentPostsSkip): BlogPostInterface
    {
        return $this->setData(self::IS_RECENT_POSTS_SKIP, $isRecentPostsSkip);
    }

    /**
     * @inheritDoc
     */
    public function getIsActive(): ?int
    {
        return (int)$this->getData(self::IS_ACTIVE);
    }

    /**
     * @inheritDoc
     */
    public function setIsActive(int $isActive): BlogPostInterface
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }

    /**
     * @inheritDoc
     */
    public function getStructureDataType(): ?int
    {
        return (int)$this->getData(self::STRUCTURE_DATA_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setStructureDataType(int $structureDataType): BlogPostInterface
    {
        return $this->setData(self::STRUCTURE_DATA_TYPE, $structureDataType);
    }

    /**
     * @inheritDoc
     */
    public function getPosition(): ?int
    {
        return (int)$this->getData(self::POSITION);
    }

    /**
     * @inheritDoc
     */
    public function setPosition(int $position): BlogPostInterface
    {
        return $this->setData(self::POSITION, $position);
    }

    /**
     * @inheritDoc
     */
    public function getIncludeInRecent(): ?int
    {
        return (int)$this->getData(self::INCLUDE_IN_RECENT);
    }

    /**
     * @inheritDoc
     */
    public function setIncludeInRecent(int $includeInRecent): BlogPostInterface
    {
        return $this->setData(self::INCLUDE_IN_RECENT, $includeInRecent);
    }

    /**
     * @inheritDoc
     */
    public function getTitle(): ?string
    {
        return $this->getData(self::TITLE);
    }

    /**
     * @inheritDoc
     */
    public function setTitle(string $title): BlogPostInterface
    {
        return $this->setData(self::TITLE, $title);
    }

    /**
     * Retrieve block identifier
     * @inheritDoc
     */
    public function getIdentifier(): ?string
    {
        return (string)$this->getData(self::IDENTIFIER);
    }

    /**
     * @inheritDoc
     */
    public function setIdentifier(string $identifier): BlogPostInterface
    {
        return $this->setData(self::IDENTIFIER, $identifier);
    }

    /**
     * @inheritDoc
     */
    public function getContentHeading(): ?string
    {
        return $this->getData(self::CONTENT_HEADING);
    }

    /**
     * @inheritDoc
     */
    public function setContentHeading(string $contentHeading): BlogPostInterface
    {
        return $this->setData(self::CONTENT_HEADING, $contentHeading);
    }

    /**
     * @inheritDoc
     */
    public function getFeaturedImg(): ?string
    {
        return $this->getData(self::FEATURED_IMG);
    }

    /**
     * @inheritDoc
     */
    public function setFeaturedImg(string $featuredImg): BlogPostInterface
    {
        return $this->setData(self::FEATURED_IMG, $featuredImg);
    }

    /**
     * @inheritDoc
     */
    public function getPageLayout(): ?string
    {
        return $this->getData(self::PAGE_LAYOUT);
    }

    /**
     * @inheritDoc
     */
    public function setPageLayout(string $pageLayout): BlogPostInterface
    {
        return $this->setData(self::PAGE_LAYOUT, $pageLayout);
    }

    /**
     * @inheritDoc
     */
    public function getCustomTheme(): ?string
    {
        return $this->getData(self::CUSTOM_THEME);
    }

    /**
     * @inheritDoc
     */
    public function setCustomTheme(string $customTheme): BlogPostInterface
    {
        return $this->setData(self::CUSTOM_THEME, $customTheme);
    }

    /**
     * Retrieve meta title
     * @inheritDoc
     */
    public function getMetaTitle(): ?string
    {
        $title = $this->getData(self::META_TITLE);
        if (!$title) {
            $title = $this->getData(self::TITLE);
        }

        return trim($title ?: '');
    }

    /**
     * @inheritDoc
     */
    public function setMetaTitle(string $metaTitle): BlogPostInterface
    {
        return $this->setData(self::META_TITLE, $metaTitle);
    }

    /**
     * Retrieve og type
     * @inheritDoc
     */
    public function getOgType(): ?string
    {
        $type = $this->getData(self::OG_TYPE);
        if (!$type) {
            $type = 'article';
        }

        return trim($type);
    }

    /**
     * @inheritDoc
     */
    public function setOgType(string $ogType): BlogPostInterface
    {
        return $this->setData(self::OG_TYPE, $ogType);
    }

    /**
     * @inheritDoc
     */
    public function getOgImg(): ?string
    {
        return $this->getData(self::OG_IMG);
    }

    /**
     * @inheritDoc
     */
    public function setOgImg(string $ogImg): BlogPostInterface
    {
        return $this->setData(self::OG_IMG, $ogImg);
    }

    /**
     * Retrieve og description
     * @inheritDoc
     */
    public function getOgDescription(): ?string
    {
        $desc = $this->getData(self::OG_DESCRIPTION);
        if (!$desc) {
            $desc = $this->getMetaDescription();
        } else {
            $desc = strip_tags($desc);
            if (mb_strlen($desc) > 300) {
                $desc = mb_substr($desc, 0, 300);
            }
        }
        return trim(html_entity_decode($desc));
    }

    /**
     * @inheritDoc
     */
    public function setOgDescription(string $ogDescription): BlogPostInterface
    {
        return $this->setData(self::OG_DESCRIPTION, $ogDescription);
    }

    /**
     * Retrieve og title
     * @inheritDoc
     */
    public function getOgTitle(): ?string
    {
        $title = $this->getData(self::OG_TITLE);
        if (!$title) {
            $title = $this->getMetaTitle();
        }

        return trim($title);
    }

    /**
     * @inheritDoc
     */
    public function setOgTitle(string $ogTitle): BlogPostInterface
    {
        return $this->setData(self::OG_TITLE, $ogTitle);
    }

    /**
     * Retrieve secret key of post, it can be used during preview
     * @inheritDoc
     * @throws LocalizedException
     */
    public function getSecret(): ?string
    {
        if ($this->getId() && !$this->getData(self::SECRET)) {
            $this->setData(
                self::SECRET,
                $this->random->getRandomString(32)
            );
            $this->_getResource()->save($this);
        }

        return $this->getData(self::SECRET);
    }

    /**
     * @inheritDoc
     */
    public function setSecret(string $secret): BlogPostInterface
    {
        return $this->setData(self::SECRET, $secret);
    }

    /**
     * @inheritDoc
     */
    public function getFeaturedImgAlt(): ?string
    {
        return $this->getData(self::FEATURED_IMG_ALT);
    }

    /**
     * @inheritDoc
     */
    public function setFeaturedImgAlt(string $featuredImgAlt): BlogPostInterface
    {
        return $this->setData(self::FEATURED_IMG_ALT, $featuredImgAlt);
    }

    /**
     * @inheritDoc
     */
    public function getCustomLayoutUpdateXml(): ?string
    {
        return $this->getData(self::CUSTOM_LAYOUT_UPDATE_XML);
    }

    /**
     * @inheritDoc
     */
    public function setCustomLayoutUpdateXml(string $customLayoutUpdateXml): BlogPostInterface
    {
        return $this->setData(self::CUSTOM_LAYOUT_UPDATE_XML, $customLayoutUpdateXml);
    }

    /**
     * @inheritDoc
     */
    public function getLayoutUpdateXml(): ?string
    {
        return $this->getData(self::LAYOUT_UPDATE_XML);
    }

    /**
     * @inheritDoc
     */
    public function setLayoutUpdateXml(string $layoutUpdateXml): BlogPostInterface
    {
        return $this->setData(self::LAYOUT_UPDATE_XML, $layoutUpdateXml);
    }

    /**
     * @inheritDoc
     */
    public function getContent(): ?string
    {
        return $this->getData(self::CONTENT);
    }

    /**
     * @inheritDoc
     */
    public function setContent(string $content): BlogPostInterface
    {
        return $this->setData(self::CONTENT, $content);
    }

    /**
     * @inheritDoc
     */
    public function getShortContent(): ?string
    {
        return $this->getData(self::SHORT_CONTENT);
    }

    /**
     * @inheritDoc
     */
    public function setShortContent(string $shortContent): BlogPostInterface
    {
        return $this->setData(self::SHORT_CONTENT, $shortContent);
    }

    /**
     * @inheritDoc
     */
    public function getMediaGallery(): ?string
    {
        return $this->getData(self::MEDIA_GALLERY);
    }

    /**
     * @inheritDoc
     */
    public function setMediaGallery(string $mediaGallery): BlogPostInterface
    {
        return $this->setData(self::MEDIA_GALLERY, $mediaGallery);
    }

    /**
     * @inheritDoc
     */
    public function getMetaKeywords(): ?string
    {
        return $this->getData(self::META_KEYWORDS);
    }

    /**
     * @inheritDoc
     */
    public function setMetaKeywords(string $metaKeywords): BlogPostInterface
    {
        return $this->setData(self::META_KEYWORDS, $metaKeywords);
    }

    /**
     * Retrieve meta description
     * @inheritDoc
     */
    public function getMetaDescription(): ?string
    {
        $key = 'filtered_'.self::META_DESCRIPTION;
        if (!$this->hasData($key)) {
            $desc = $this->getData(self::META_DESCRIPTION);
            if (!$desc) {
                $desc = $this->getShortFilteredContent(250);
            }

            $stylePattern = "~\<style(.*)\>(.*)\<\/style\>~";
            $desc = preg_replace($stylePattern, '', $desc);
            $desc = trim(strip_tags((string)$desc));
            $desc = str_replace(["\r\n", "\n\r", "\r", "\n"], ' ', $desc);

            if (mb_strlen($desc) > 200) {
                $desc = mb_substr($desc, 0, 200);
            }

            $desc = trim($desc);
            $this->setData($key, $desc);
        }

        return $this->getData($key);
    }

    /**
     * @inheritDoc
     */
    public function setMetaDescription(string $metaDescription): BlogPostInterface
    {
        return $this->setData(self::META_DESCRIPTION, $metaDescription);
    }

    /**
     * @inheritDoc
     */
    public function getMetaRobots(): ?string
    {
        return $this->getData(self::META_ROBOTS);
    }

    /**
     * @inheritDoc
     */
    public function setMetaRobots(string $metaRobots): BlogPostInterface
    {
        return $this->setData(self::META_ROBOTS, $metaRobots);
    }

    /**
     * @inheritDoc
     */
    public function getCreationTime(): ?string
    {
        return $this->getData(self::CREATION_TIME);
    }

    /**
     * @inheritDoc
     */
    public function setCreationTime(string $creationTime): BlogPostInterface
    {
        return $this->setData(self::CREATION_TIME, $creationTime);
    }

    /**
     * @inheritDoc
     */
    public function getUpdateTime(): ?string
    {
        return $this->getData(self::UPDATE_TIME);
    }

    /**
     * Retrieve updated at time
     * @return mixed
     */
    public function getUpdatedAt(): mixed
    {
        return $this->getUpdateTime();
    }

    /**
     * @inheritDoc
     */
    public function setUpdateTime(string $updateTime): BlogPostInterface
    {
        return $this->setData(self::UPDATE_TIME, $updateTime);
    }

    /**
     * @inheritDoc
     */
    public function getPublishTime(): ?string
    {
        return $this->getData(self::PUBLISH_TIME);
    }

    /**
     * @inheritDoc
     */
    public function setPublishTime(string $publishTime): BlogPostInterface
    {
        return $this->setData(self::PUBLISH_TIME, $publishTime);
    }

    /**
     * @inheritDoc
     */
    public function getCustomThemeFrom(): ?string
    {
        return $this->getData(self::CUSTOM_THEME_FROM);
    }

    /**
     * @inheritDoc
     */
    public function setCustomThemeFrom(string $customThemeFrom): BlogPostInterface
    {
        return $this->setData(self::CUSTOM_THEME_FROM, $customThemeFrom);
    }

    /**
     * @inheritDoc
     */
    public function getCustomThemeTo(): ?string
    {
        return $this->getData(self::CUSTOM_THEME_TO);
    }

    /**
     * @inheritDoc
     */
    public function setCustomThemeTo(string $customThemeTo): BlogPostInterface
    {
        return $this->setData(self::CUSTOM_THEME_TO, $customThemeTo);
    }







}
