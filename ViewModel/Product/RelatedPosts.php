<?php

declare(strict_types=1);

namespace MageOS\Blog\ViewModel\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Model\Config;
use MageOS\Blog\Model\Post\PostsByAssignmentProvider;
use MageOS\Blog\ViewModel\Post\PostPresenterInterface;
use MageOS\Blog\ViewModel\Post\PostPresenterTrait;

/**
 * Published blog posts linked to the product currently being viewed.
 *
 * Implements PostPresenterInterface so the shared post/card.phtml renders these
 * without a second copy of the URL / image / author helpers.
 */
class RelatedPosts implements ArgumentInterface, PostPresenterInterface
{
    use PostPresenterTrait;

    /**
     * @var PostInterface[]|null
     */
    private ?array $cached = null;

    public function __construct(
        private readonly RequestInterface $request,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly StoreManagerInterface $storeManager,
        private readonly UrlInterface $urlBuilder,
        private readonly AuthorRepositoryInterface $authorRepository,
        private readonly Config $config,
        private readonly PostsByAssignmentProvider $provider
    ) {
    }

    /**
     * @return PostInterface[]
     */
    public function getPosts(): array
    {
        if ($this->cached !== null) {
            return $this->cached;
        }

        $this->cached = $this->resolve();

        return $this->cached;
    }

    public function hasPosts(): bool
    {
        return $this->getPosts() !== [];
    }

    /**
     * Post ids of whatever is actually rendered, for the block's cache
     * identities. Without these the product page's FPC entry carries no blog
     * cache tags, so editing a linked post leaves the page stale indefinitely.
     *
     * @return int[]
     */
    public function getPostIds(): array
    {
        return array_map(
            static fn (PostInterface $post): int => (int) $post->getPostId(),
            $this->getPosts()
        );
    }

    public function getTitle(): string
    {
        return $this->config->getRelatedPostsTitle();
    }

    /**
     * @return PostInterface[]
     */
    private function resolve(): array
    {
        // Module flag first: layout XML cannot see it, so a disabled module
        // must still render nothing even when the feature flag is on.
        if (!$this->config->isEnabled() || !$this->config->isRelatedPostsEnabled()) {
            return [];
        }

        $limit = $this->config->getRelatedPostsLimit();
        if ($limit <= 0) {
            return [];
        }

        $product = $this->getCurrentProduct();
        if ($product === null) {
            return [];
        }

        $productId = (int) $product->getId();
        if ($productId <= 0) {
            return [];
        }

        return $this->provider->byProduct(
            $productId,
            (int) $this->storeManager->getStore()->getId(),
            $limit
        );
    }

    /**
     * The product being viewed, resolved through the repository rather than the
     * registry — Magento\Framework\Registry is deprecated.
     *
     * This costs no extra load. ProductRepository::getById() memoizes per
     * request in $instancesById, keyed by [editMode, storeId], and
     * Catalog\Helper\Product::initProduct() populates that cache with exactly
     * `getById($id, false, $storeManager->getStore()->getId())`. Matching those
     * arguments reuses the controller's instance; changing them would trigger a
     * second full product load on every product page render.
     */
    private function getCurrentProduct(): ?ProductInterface
    {
        $productId = (int) $this->request->getParam('id');
        if ($productId <= 0) {
            return null;
        }

        try {
            return $this->productRepository->getById(
                $productId,
                false,
                (int) $this->storeManager->getStore()->getId()
            );
        } catch (NoSuchEntityException) {
            return null;
        }
    }

    private function urlBuilder(): UrlInterface
    {
        return $this->urlBuilder;
    }

    private function authorRepository(): AuthorRepositoryInterface
    {
        return $this->authorRepository;
    }

    private function config(): Config
    {
        return $this->config;
    }
}
