<?php
declare(strict_types=1);

namespace MageOS\Blog\Model;

use Exception;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use MageOS\Blog\Model\Url;
use Magento\Framework\DataObject\IdentityInterface;
use MageOS\Blog\Api\ShortContentExtractorInterface;
use MageOS\Blog\Api\Data\BlogCategoryInterface;
use MageOS\Blog\Model\ResourceModel\Post\CollectionFactory as PostCollectionFactory;
/**
 * Category model
 *
 * @method \MageOS\Blog\Model\ResourceModel\Category _getResource()
 * @method \MageOS\Blog\Model\ResourceModel\Category getResource()
 * @method int getStoreId()
 * @method $this setStoreId(int $value)
 * @method $this setUrlKey(string $value)
 * @method string getUrlKey()
 */
class Category extends \Magento\Framework\Model\AbstractModel implements BlogCategoryInterface, IdentityInterface
{
    const CACHE_TAG = 'rb_c';
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;
    protected $_eventPrefix = 'blog_category';
    protected $_eventObject = 'blog_category';
    protected Url $_url;
    protected PostCollectionFactory $postCollectionFactory;
    private static array $loadedCategoriesRepository = [];
    protected string $controllerName;
    protected $shortContentExtractor;

    public function __construct(
        Context                                                 $context,
        Registry                                                $registry,
        Url                                                     $url,
        PostCollectionFactory                                   $postCollectionFactory,
        AbstractResource                                        $resource = null,
        AbstractDb                                              $resourceCollection = null,
        array                                                   $data = []
    ) {
        $this->_url = $url;
        $this->postCollectionFactory = $postCollectionFactory;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(\MageOS\Blog\Model\ResourceModel\Category::class);
        $this->controllerName = URL::CONTROLLER_CATEGORY;
    }

    /**
     * @inheritDoc
     */
    public function getIdentities(): array
    {
        $identities = [];

        if ($this->getId()) {
            $identities[] = self::CACHE_TAG . '_' . $this->getId();
        }

        return $identities;
    }

    /**
     * Load object data
     *
     * @param integer $modelId
     * @param null|string $field
     * @return $this
     * @deprecated
     */
    public function load($modelId, $field = null): static
    {
        $object = parent::load($modelId, $field);
        if (!isset(self::$loadedCategoriesRepository[$object->getId()])) {
            self::$loadedCategoriesRepository[$object->getId()] = $object;
        }

        return $object;
    }

    /**
     * Load category by id
     * @param int $categoryId
     * @return self
     */
    private function loadFromRepository(int $categoryId): BlogCategoryInterface
    {
        if (!isset(self::$loadedCategoriesRepository[$categoryId])) {
            $category = clone $this;
            $category->unsetData();
            $category->load($categoryId);
            $categoryId = $category->getId();
        }

        return self::$loadedCategoriesRepository[$categoryId];
    }

    /**
     * @inheritDoc
     */
    public function getControllerName(): string
    {
        return $this->controllerName;
    }

    /**
     * @inheritDoc
     */
    public function getOwnTitle(bool $plural = false): string
    {
        return $plural ? 'Categories' : 'Category';
    }

    /**
     * @inheritDoc
     */
    public function checkIdentifier(string $identifier, int $storeId): string
    {
        return $this->_getResource()->checkIdentifier($identifier, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getParentIds(): array
    {
        $k = 'parent_ids';
        if (!$this->hasData($k)) {
            $this->setData(
                $k,
                $this->getPath() ? explode('/', $this->getPath()) : []
            );
        }

        return $this->getData($k);
    }

    /**
     * Retrieve parent category id
     * @return int|array
     */
    public function getParentId()
    {
        $parentIds = $this->getParentIds();
        if ($parentIds) {
            return $parentIds[count($parentIds) - 1];
        }

        return 0;
    }

    /**
     * Retrieve parent category
     * @return BlogCategoryInterface|null | false
     */
    public function getParentCategory()
    {
        $k = 'parent_category';
        if (null === $this->getData($k)) {
            $this->setData($k, false);
            if ($pId = $this->getParentId()) {
                $category = $this->loadFromRepository((int)$pId);
                if ($category->getId()) {
                    if ($category->isVisibleOnStore($this->getStoreId())) {
                        $this->setData($k, $category);
                    }
                }
            }
        }

        return $this->getData($k);
    }

    /**
     * Check if current category is parent category
     * @param BlogCategoryInterface|array $category
     * @return boolean
     */
    public function isParent($category): bool
    {
        if (is_object($category)) {
            $category = $category->getId();
        }

        return in_array($category, $this->getParentIds());
    }

    /**
     * Retrieve children category ids
     * @param bool $grandchildren
     * @return array
     */
    public function getChildrenIds(bool $grandchildren = true): array
    {
        $k = 'children_ids';
        if (!$this->hasData($k)) {
            $categories = \Magento\Framework\App\ObjectManager::getInstance()
                ->create($this->_collectionName);

            $allIds = $ids = [];
            foreach ($categories as $category) {
                if ($category->isParent($this)) {
                    $allIds[] = $category->getId();
                    if ($category->getLevel() == $this->getLevel() + 1) {
                        $ids[] = $category->getId();
                    }
                }
            }

            $this->setData('all_' . $k, $allIds);
            $this->setData($k, $ids);
        }

        return $this->getData(
            ($grandchildren ? 'all_' : '') . $k
        );
    }

    /**
     * Check if current category is child category
     * @param BlogCategoryInterface $category
     * @return boolean
     */
    public function isChild(BlogCategoryInterface $category): bool
    {
        return $category->isParent($this);
    }

    /**
     * Retrieve category depth level
     * @return int
     */
    public function getLevel(): int
    {
        return count($this->getParentIds());
    }

    /**
     * Retrieve catgegory url route path
     * @return string
     */
    public function getUrl(): string
    {
        return $this->_url->getUrlPath($this, $this->controllerName);
    }

    /**
     * Retrieve category url
     * @return string
     */
    public function getCategoryUrl(): string
    {
        if (!$this->hasData('category_url')) {
            $url = $this->_url->getUrl($this, $this->controllerName);
            $this->setData('category_url', $url);
        }

        return $this->getData('category_url');
    }

    /**
     * Retrieve catgegory canonical url
     * @return string
     */
    public function getCanonicalUrl(): string
    {
        return $this->_url->getCanonicalUrl($this);
    }

    /**
     * Retrieve if is visible on store
     * @param int $storeId
     * @return bool
     */
    public function isVisibleOnStore(int $storeId): bool
    {
        return $this->getIsActive()
            && (null === $storeId || array_intersect([0, $storeId], $this->getStoreIds()));
    }

    /**
     * Retrieve number of posts in this category
     *
     * @return int
     */
    public function getPostsCount(): int
    {
        $key = 'posts_count';
        if (!$this->hasData($key)) {
            $posts = $this->postCollectionFactory->create()
                ->addActiveFilter()
                ->addStoreFilter($this->getStoreId())
                ->addCategoryFilter($this);

            $this->setData($key, (int)$posts->getSize());
        }

        return $this->getData($key);
    }

    /**
     * Prepare all additional data
     * @param  string $format
     * @return self
     * @deprecated replaced with getDynamicData
     */
    public function initDinamicData(): static
    {
        $keys = [
            'meta_description',
            'meta_title',
            'category_url',
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
     * @param array|null $fields
     * @return array
     *@deprecated use getDynamicData method in graphQL data provider
     * Prepare all additional data
     */
    public function getDynamicData(array $fields = []): array
    {
        $data = $this->getData();

        $keys = [
            'meta_description',
            'meta_title',
            'category_url',
        ];

        foreach ($keys as $key) {
            $method = 'get' . str_replace(
                '_',
                '',
                ucwords($key, '_')
            );
            $data[$key] = $this->$method();
        }

        if (is_array($fields) && array_key_exists('breadcrumbs', $fields)) {
            $breadcrumbs = [];

            $category = $this;
            $parentCategories = [];
            while ($parentCategory = $category->getParentCategory()) {
                $parentCategories[] = $category = $parentCategory;
            }

            for ($i = count($parentCategories) - 1; $i >= 0; $i--) {
                $category = $parentCategories[$i];

                $breadcrumbs[] = [
                    'category_id' => $category->getId(),
                    'category_name' => $category->getTitle(),
                    'category_level' => $category->getLevel(),
                    'category_url_key' => $category->getIdentifier(),
                    'category_url_path' => $category->getUrl(),
                ];
            }

            $category = $this;
            $breadcrumbs[] = [
                'category_id' => $category->getId(),
                'category_name' => $category->getTitle(),
                'category_level' => $category->getLevel(),
                'category_url_key' => $category->getIdentifier(),
                'category_url_path' => $category->getUrl(),
            ];

            $data['breadcrumbs'] = $breadcrumbs;
        }

        if (is_array($fields) && array_key_exists('parent_category_id', $fields)) {
            $data['parent_category_id'] = $this->getParentCategory() ? $this->getParentCategory()->getId() : 0;
        }

        if (is_array($fields) && array_key_exists('category_level', $fields)) {
            $data['category_level'] = $this->getLevel();
        }

        if (is_array($fields) && array_key_exists('posts_count', $fields)) {
            $data['posts_count'] = $this->getPostsCount();
        }

        if (is_array($fields) && array_key_exists('category_url_path', $fields)) {
            $data['category_url_path'] = $this->getUrl();
        }

        return $data;
    }

    /**
     * Duplicate category and return new object
     * @return self
     * @throws Exception
     */
    public function duplicate(): Category
    {
        $object = clone $this;
        $object
            ->unsetData('category_id')
            ->unsetData('identifier')
            ->setTitle($object->getTitle() . ' (' . __('Duplicated') . ')')
            ->setData('is_active', 0);

        return $object->save();
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
     * @return array|mixed|null
     */
    public function getCategoryImage()
    {
        if (!$this->hasData('category_image')) {
            if ($file = $this->getData('category_img')) {
                $image = $this->_url->getMediaUrl($file);
            } else {
                $image = false;
            }
            $this->setData('category_image', $image);
        }

        return $this->getData('category_image');
    }

    /**
     * @inheritDoc
     */
    public function getCategoryId(): ?int
    {
        return (int)$this->getData(self::CATEGORY_ID);
    }

    /**
     * @inheritDoc
     */
    public function setCategoryId(int $categoryId): BlogCategoryInterface
    {
        return $this->setData(self::CATEGORY_ID, $categoryId);
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
    public function setIsActive(int $isActive): BlogCategoryInterface
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
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
    public function setPosition(int $position): BlogCategoryInterface
    {
        return $this->setData(self::POSITION, $position);
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
    public function setTitle(string $title): BlogCategoryInterface
    {
        return $this->setData(self::TITLE, $title);
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
    public function setContentHeading(string $contentHeading): BlogCategoryInterface
    {
        return $this->setData(self::CONTENT_HEADING, $contentHeading);
    }

    /**
     * @inheritDoc
     */
    public function getIdentifier(): ?string
    {
        return $this->getData(self::IDENTIFIER);
    }

    /**
     * @inheritDoc
     */
    public function setIdentifier(string $identifier): BlogCategoryInterface
    {
        return $this->setData(self::IDENTIFIER, $identifier);
    }

    /**
     * @inheritDoc
     */
    public function getPath()
    {
        return $this->getData(self::PATH);
    }

    /**
     * @inheritDoc
     */
    public function setPath(string $path): BlogCategoryInterface
    {
        return $this->setData(self::PATH, $path);
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
    public function setPageLayout(string $pageLayout): BlogCategoryInterface
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
    public function setCustomTheme(string $customTheme): BlogCategoryInterface
    {
        return $this->setData(self::CUSTOM_THEME, $customTheme);
    }

    /**
     * @inheritDoc
     */
    public function getCustomLayout(): ?string
    {
        return $this->getData(self::CUSTOM_LAYOUT);
    }

    /**
     * @inheritDoc
     */
    public function setCustomLayout(string $customLayout): BlogCategoryInterface
    {
        return $this->setData(self::CUSTOM_LAYOUT, $customLayout);
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
    public function setLayoutUpdateXml(string $layoutUpdateXml): BlogCategoryInterface
    {
        return $this->setData(self::LAYOUT_UPDATE_XML, $layoutUpdateXml);
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
    public function setCustomLayoutUpdateXml(string $customLayoutUpdateXml): BlogCategoryInterface
    {
        return $this->setData(self::CUSTOM_LAYOUT_UPDATE_XML, $customLayoutUpdateXml);
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
    public function setContent(string $content): BlogCategoryInterface
    {
        return $this->setData(self::CONTENT, $content);
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
    public function setMetaKeywords(string $metaKeywords): BlogCategoryInterface
    {
        return $this->setData(self::META_KEYWORDS, $metaKeywords);
    }

    /**
     * Retrieve meta description
     * @inheritDoc
     * @throws Exception
     */
    public function getMetaDescription(): ?string
    {
        $desc = $this->getData(self::META_DESCRIPTION);
        if (!$desc) {
            $desc = $this->getShortContentExtractor()->execute($this->getData(self::CONTENT), 250);
        }

        $stylePattern = "~\<style(.*)\>(.*)\<\/style\>~";
        $desc = preg_replace($stylePattern, '', $desc);
        $desc = trim(strip_tags((string)$desc));
        $desc = str_replace(["\r\n", "\n\r", "\r", "\n"], ' ', $desc);

        if (mb_strlen($desc) > 200) {
            $desc = mb_substr($desc, 0, 200);
        }

        return trim($desc);
    }

    /**
     * @inheritDoc
     */
    public function setMetaDescription(string $metaDescription): BlogCategoryInterface
    {
        return $this->setData(self::META_DESCRIPTION, $metaDescription);
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
    public function setCustomThemeFrom(string $customThemeFrom): BlogCategoryInterface
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
    public function setCustomThemeTo(string $customThemeTo): BlogCategoryInterface
    {
        return $this->setData(self::CUSTOM_THEME_TO, $customThemeTo);
    }

    /**
     * @inheritDoc
     */
    public function getPostsPerPage(): ?int
    {
        return (int)$this->getData(self::POSTS_PER_PAGE);
    }

    /**
     * @inheritDoc
     */
    public function setPostsPerPage(int $postsPerPage): BlogCategoryInterface
    {
        return $this->setData(self::POSTS_PER_PAGE, $postsPerPage);
    }

    /**
     * @inheritDoc
     */
    public function getPostsListTemplate(): ?string
    {
        return $this->getData(self::POSTS_LIST_TEMPLATE);
    }

    /**
     * @inheritDoc
     */
    public function setPostsListTemplate(string $postsListTemplate): BlogCategoryInterface
    {
        return $this->setData(self::POSTS_LIST_TEMPLATE, $postsListTemplate);
    }

    /**
     * @inheritDoc
     */
    public function getPostsSortBy(): ?int
    {
        return (int)$this->getData(self::POSTS_SORT_BY);
    }

    /**
     * @inheritDoc
     */
    public function setPostsSortBy(int $postsSortBy): BlogCategoryInterface
    {
        return $this->setData(self::POSTS_SORT_BY, $postsSortBy);
    }

    /**
     * @inheritDoc
     */
    public function getDisplayMode(): ?int
    {
        return (int)$this->getData(self::DISPLAY_MODE);
    }

    /**
     * @inheritDoc
     */
    public function setDisplayMode(int $displayMode): BlogCategoryInterface
    {
        return $this->setData(self::DISPLAY_MODE, $displayMode);
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
    public function setMetaTitle(string $metaTitle): BlogCategoryInterface
    {
        return $this->setData(self::META_TITLE, $metaTitle);
    }

    /**
     * @inheritDoc
     */
    public function getIncludeInMenu(): ?int
    {
        return (int)$this->getData(self::INCLUDE_IN_MENU);
    }

    /**
     * @inheritDoc
     */
    public function setIncludeInMenu(int $includeInMenu): BlogCategoryInterface
    {
        return $this->setData(self::INCLUDE_IN_MENU, $includeInMenu);
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
    public function setMetaRobots(string $metaRobots): BlogCategoryInterface
    {
        return $this->setData(self::META_ROBOTS, $metaRobots);
    }

    /**
     * @inheritDoc
     */
    public function getIncludeInSidebarTree(): ?string
    {
        return $this->getData(self::INCLUDE_IN_SIDEBAR_TREE);
    }

    /**
     * @inheritDoc
     */
    public function setIncludeInSidebarTree(string $includeInSidebarTree): BlogCategoryInterface
    {
        return $this->setData(self::INCLUDE_IN_SIDEBAR_TREE, $includeInSidebarTree);
    }
}
