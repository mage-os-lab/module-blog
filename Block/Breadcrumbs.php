<?php

declare(strict_types=1);

namespace MageOS\Blog\Block;

use Magento\Framework\App\Request\Http;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Theme\Block\Html\Breadcrumbs as ThemeBreadcrumbs;
use MageOS\Blog\Api\Data\AuthorInterface;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\Data\TagInterface;
use MageOS\Blog\Controller\Author\View as AuthorViewController;
use MageOS\Blog\Controller\Category\View as CategoryViewController;
use MageOS\Blog\Controller\Post\View as PostViewController;
use MageOS\Blog\Controller\Tag\View as TagViewController;
use MageOS\Blog\Model\Breadcrumb\PostCategoryPicker;
use MageOS\Blog\Model\Breadcrumb\TrailBuilder;
use MageOS\Blog\Model\Category;
use MageOS\Blog\Model\Config;

/**
 * Feeds the theme's breadcrumbs block with the trail for the current blog page.
 *
 * Declared once in blog_default.xml, which every blog page handle includes. It
 * renders nothing itself. A theme without a `breadcrumbs` block, a disabled
 * module, a disabled trail or a page without an entity all leave the page as
 * it was.
 */
class Breadcrumbs extends AbstractBlock implements IdentityInterface
{
    /** @var int[] */
    private array $postCategoryIds = [];

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        private readonly Registry $registry,
        private readonly Config $config,
        private readonly StoreManagerInterface $storeManager,
        private readonly TrailBuilder $trailBuilder,
        private readonly PostCategoryPicker $categoryPicker,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    protected function _prepareLayout()
    {
        $this->registerCrumbs();

        return parent::_prepareLayout();
    }

    /**
     * The post trail embeds one category's title, and which category it picks depends on all of the
     * post's categories (active flag, position, store assignment). Tag the page with every one of
     * them, or changing a category the trail did not pick would leave the cached post stale.
     *
     * @return string[]
     */
    public function getIdentities(): array
    {
        return array_map(
            static fn (int $categoryId): string => Category::CACHE_TAG . '_' . $categoryId,
            $this->postCategoryIds
        );
    }

    private function registerCrumbs(): void
    {
        $storeId = (int) $this->storeManager->getStore()->getId();
        if (!$this->config->isEnabled($storeId) || !$this->config->isBreadcrumbsEnabled($storeId)) {
            return;
        }

        $breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
        if (!$breadcrumbs instanceof ThemeBreadcrumbs) {
            return;
        }

        foreach ($this->buildTrail($storeId) as $name => $crumb) {
            $breadcrumbs->addCrumb($name, $crumb);
        }
    }

    /**
     * @return array<string, array{label: string, title: string, link?: string}>
     */
    private function buildTrail(int $storeId): array
    {
        $post = $this->registry->registry(PostViewController::REGISTRY_KEY);
        if ($post instanceof PostInterface) {
            $this->postCategoryIds = array_map('intval', $post->getCategoryIds());

            return $this->trailBuilder->forPost($post, $this->categoryPicker->forPost($post, $storeId));
        }

        $category = $this->registry->registry(CategoryViewController::REGISTRY_KEY);
        if ($category instanceof CategoryInterface) {
            return $this->trailBuilder->forCategory($category);
        }

        $tag = $this->registry->registry(TagViewController::REGISTRY_KEY);
        if ($tag instanceof TagInterface) {
            return $this->trailBuilder->forTag($tag);
        }

        $author = $this->registry->registry(AuthorViewController::REGISTRY_KEY);
        if ($author instanceof AuthorInterface) {
            return $this->trailBuilder->forAuthor($author);
        }

        $request = $this->getRequest();
        $action = $request instanceof Http ? $request->getFullActionName() : '';
        if ($action === 'blog_search_index') {
            return $this->trailBuilder->forSearch(trim((string) $request->getParam('q', '')));
        }
        if ($action === 'blog_index_index') {
            return $this->trailBuilder->forIndex();
        }

        return [];
    }
}
