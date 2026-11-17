<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\ViewModel\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Model\Config;
use MageOS\Blog\Model\Post\PostsByAssignmentProvider;
use MageOS\Blog\Test\Unit\Model\Url\PermalinkFixture;
use MageOS\Blog\ViewModel\Product\RelatedPosts;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

final class RelatedPostsTest extends TestCase
{
    private const STORE_ID = 1;
    private const PRODUCT_ID = 42;

    // Stubs answer questions; mocks additionally assert interactions. Only the
    // repository and the provider are asserted against (expects(never|once)),
    // so the other two are stubs — which also keeps PHPUnit from flagging
    // mocks that carry no expectations.
    private RequestInterface&Stub $request;
    private ProductRepositoryInterface&MockObject $productRepository;
    private Config&Stub $config;
    private PostsByAssignmentProvider&MockObject $provider;

    protected function setUp(): void
    {
        $this->request = $this->createStub(RequestInterface::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->config = $this->createStub(Config::class);
        $this->provider = $this->createMock(PostsByAssignmentProvider::class);
    }

    #[Test]
    public function returns_nothing_when_the_request_has_no_product_id(): void
    {
        $this->request->method('getParam')->willReturn(null);
        $this->enabled();
        $this->productRepository->expects(self::never())->method('getById');
        $this->provider->expects(self::never())->method('byProduct');

        $vm = $this->viewModel();

        self::assertSame([], $vm->getPosts());
        self::assertFalse($vm->hasPosts());
    }

    #[Test]
    public function returns_nothing_when_the_product_does_not_exist(): void
    {
        $this->request->method('getParam')->willReturn((string) self::PRODUCT_ID);
        $this->productRepository->method('getById')
            ->willThrowException(new NoSuchEntityException(__('nope')));
        $this->enabled();
        $this->provider->expects(self::never())->method('byProduct');

        self::assertSame([], $this->viewModel()->getPosts());
    }

    #[Test]
    public function loads_the_product_with_the_same_arguments_the_pdp_controller_uses(): void
    {
        // Catalog\Helper\Product::initProduct() calls
        //   getById($id, false, $storeManager->getStore()->getId())
        // and ProductRepository memoizes on [editMode, storeId]. Diverging here
        // would miss that cache and load the product a second time per render.
        $this->request->method('getParam')->willReturn((string) self::PRODUCT_ID);
        $this->productRepository->expects(self::once())
            ->method('getById')
            ->with(self::PRODUCT_ID, false, self::STORE_ID)
            ->willReturn($this->makeProduct());
        $this->enabled(3);
        $this->provider->method('byProduct')->willReturn([]);

        $this->viewModel()->getPosts();
    }

    #[Test]
    public function returns_nothing_when_the_module_is_disabled(): void
    {
        $this->withProduct();
        $this->config->method('isEnabled')->willReturn(false);
        $this->config->method('isRelatedPostsEnabled')->willReturn(true);
        // A positive limit is essential: without it the limit guard returns []
        // on its own and this test passes without ever exercising the flag.
        $this->config->method('getRelatedPostsLimit')->willReturn(3);
        $this->provider->expects(self::never())->method('byProduct');

        self::assertSame([], $this->viewModel()->getPosts());
    }

    #[Test]
    public function returns_nothing_when_the_feature_is_disabled(): void
    {
        $this->withProduct();
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isRelatedPostsEnabled')->willReturn(false);
        $this->config->method('getRelatedPostsLimit')->willReturn(3);
        $this->provider->expects(self::never())->method('byProduct');

        self::assertSame([], $this->viewModel()->getPosts());
    }

    #[Test]
    public function returns_nothing_when_the_limit_is_not_positive(): void
    {
        $this->withProduct();
        $this->enabled(0);
        $this->provider->expects(self::never())->method('byProduct');

        self::assertSame([], $this->viewModel()->getPosts());
    }

    #[Test]
    public function passes_product_store_and_limit_to_the_provider(): void
    {
        $this->withProduct();
        $this->enabled(3);
        $this->provider->expects(self::once())
            ->method('byProduct')
            ->with(self::PRODUCT_ID, self::STORE_ID, 3)
            ->willReturn([$this->makePost(7)]);

        $vm = $this->viewModel();
        $posts = $vm->getPosts();

        self::assertCount(1, $posts);
        self::assertSame(7, (int) $posts[0]->getPostId());
        self::assertTrue($vm->hasPosts());
    }

    #[Test]
    public function queries_the_provider_only_once_across_repeated_calls(): void
    {
        $this->withProduct();
        $this->enabled(3);
        // The template calls getPosts(), and the block calls getPostIds() for
        // its cache identities. Without memoization that is two queries per
        // product page render.
        $this->provider->expects(self::once())
            ->method('byProduct')
            ->willReturn([$this->makePost(7), $this->makePost(8)]);

        $vm = $this->viewModel();
        $vm->getPosts();
        $vm->hasPosts();
        $vm->getPostIds();
        $vm->getPosts();

        self::assertTrue($vm->hasPosts());
    }

    #[Test]
    public function exposes_post_ids_for_cache_identities(): void
    {
        $this->withProduct();
        $this->enabled(3);
        $this->provider->method('byProduct')->willReturn([$this->makePost(7), $this->makePost(8)]);

        self::assertSame([7, 8], $this->viewModel()->getPostIds());
    }

    #[Test]
    public function exposes_no_post_ids_when_there_are_no_posts(): void
    {
        $this->withProduct();
        $this->enabled(3);
        $this->provider->method('byProduct')->willReturn([]);

        self::assertSame([], $this->viewModel()->getPostIds());
        self::assertFalse($this->viewModel()->hasPosts());
    }

    #[Test]
    public function title_comes_from_config(): void
    {
        $this->config->method('getRelatedPostsTitle')->willReturn('From the blog');

        self::assertSame('From the blog', $this->viewModel()->getTitle());
    }

    private function enabled(int $limit = 3): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isRelatedPostsEnabled')->willReturn(true);
        $this->config->method('getRelatedPostsLimit')->willReturn($limit);
    }

    private function withProduct(): void
    {
        $this->request->method('getParam')->willReturn((string) self::PRODUCT_ID);
        $this->productRepository->method('getById')->willReturn($this->makeProduct());
    }

    private function makeProduct(): ProductInterface
    {
        // Stub, not mock: nothing here asserts how the product is used.
        $product = $this->createStub(ProductInterface::class);
        $product->method('getId')->willReturn(self::PRODUCT_ID);

        return $product;
    }

    private function makePost(int $id): PostInterface
    {
        $post = $this->createStub(PostInterface::class);
        $post->method('getPostId')->willReturn($id);

        return $post;
    }

    private function viewModel(): RelatedPosts
    {
        // Stubs: these collaborators only need to answer, never to be asserted
        // against. Mocks here would trip PHPUnit's "no expectations configured"
        // notice for every test.
        $store = $this->createStub(StoreInterface::class);
        $store->method('getId')->willReturn(self::STORE_ID);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        return new RelatedPosts(
            $this->request,
            $this->productRepository,
            $storeManager,
            $this->createStub(UrlInterface::class),
            $this->createStub(AuthorRepositoryInterface::class),
            $this->config,
            $this->provider,
            PermalinkFixture::create(),
        );
    }
}
