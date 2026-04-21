<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\ViewModel\Post;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\RelatedPostsProviderInterface;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Controller\Post\View as PostViewController;
use MageOS\Blog\Model\Config;
use MageOS\Blog\ViewModel\Post\Detail;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DetailTest extends TestCase
{
    private Registry&MockObject $registry;
    private UrlInterface&MockObject $urlBuilder;
    private Config&MockObject $config;
    private StoreManagerInterface&MockObject $storeManager;
    private AuthorRepositoryInterface&MockObject $authorRepository;
    private TagRepositoryInterface&MockObject $tagRepository;
    private SearchCriteriaBuilder&MockObject $searchCriteriaBuilder;
    private RelatedPostsProviderInterface&MockObject $relatedPostsProvider;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(Registry::class);
        $this->urlBuilder = $this->createMock(UrlInterface::class);
        $this->config = $this->createMock(Config::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->authorRepository = $this->createMock(AuthorRepositoryInterface::class);
        $this->tagRepository = $this->createMock(TagRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->relatedPostsProvider = $this->createMock(RelatedPostsProviderInterface::class);
    }

    #[Test]
    public function resolve_og_prefers_og_title_over_meta_and_plain_title(): void
    {
        $post = $this->makePost([
            'og_title' => 'OG Title',
            'meta_title' => 'Meta Title',
            'title' => 'Plain Title',
        ]);
        $this->registerPost($post);

        $detail = $this->makeViewModel();

        self::assertSame('OG Title', $detail->getOgTags()['og:title']);
    }

    #[Test]
    public function resolve_og_falls_back_to_meta_title_when_og_empty(): void
    {
        $post = $this->makePost([
            'og_title' => null,
            'meta_title' => 'Meta Title',
            'title' => 'Plain Title',
        ]);
        $this->registerPost($post);

        $detail = $this->makeViewModel();

        self::assertSame('Meta Title', $detail->getOgTags()['og:title']);
    }

    #[Test]
    public function resolve_og_falls_back_to_plain_title_when_og_and_meta_empty(): void
    {
        $post = $this->makePost([
            'og_title' => null,
            'meta_title' => null,
            'title' => 'Plain Title',
        ]);
        $this->registerPost($post);

        $detail = $this->makeViewModel();

        self::assertSame('Plain Title', $detail->getOgTags()['og:title']);
    }

    #[Test]
    public function get_og_tags_defaults_type_to_article(): void
    {
        $post = $this->makePost(['og_type' => null]);
        $this->registerPost($post);
        $this->config->method('getOgDefaultType')->willReturn('');

        $detail = $this->makeViewModel();

        self::assertSame('article', $detail->getOgTags()['og:type']);
    }

    #[Test]
    public function get_twitter_tags_uses_summary_large_image_when_og_image_present(): void
    {
        $post = $this->makePost(['og_image' => 'hero.jpg']);
        $this->registerPost($post);
        $this->urlBuilder->method('getBaseUrl')->willReturn('https://shop.test/media/');

        $detail = $this->makeViewModel();

        self::assertSame('summary_large_image', $detail->getTwitterTags()['twitter:card']);
        self::assertSame(
            'https://shop.test/media/mageos_blog/hero.jpg',
            $detail->getTwitterTags()['twitter:image']
        );
    }

    #[Test]
    public function get_twitter_tags_uses_summary_when_no_image(): void
    {
        $post = $this->makePost(['og_image' => null, 'featured_image' => null]);
        $this->registerPost($post);

        $detail = $this->makeViewModel();

        self::assertSame('summary', $detail->getTwitterTags()['twitter:card']);
        self::assertArrayNotHasKey('twitter:image', $detail->getTwitterTags());
    }

    #[Test]
    public function get_twitter_tags_includes_site_handle_from_config(): void
    {
        $post = $this->makePost([]);
        $this->registerPost($post);
        $this->config->method('getTwitterSite')->willReturn('@mageos');

        $detail = $this->makeViewModel();

        self::assertSame('@mageos', $detail->getTwitterTags()['twitter:site']);
    }

    #[Test]
    public function get_json_ld_returns_empty_when_disabled(): void
    {
        $post = $this->makePost([]);
        $this->registerPost($post);
        $this->config->method('isJsonLdEnabled')->willReturn(false);

        $detail = $this->makeViewModel();

        self::assertSame('', $detail->getJsonLd());
    }

    #[Test]
    public function get_json_ld_serializes_blog_posting_with_required_fields(): void
    {
        $post = $this->makePost([
            'title' => 'Hello World',
            'publish_date' => '2026-04-10 09:00:00',
        ]);
        $this->registerPost($post);
        $this->config->method('isJsonLdEnabled')->willReturn(true);

        $store = $this->createMock(Store::class);
        $store->method('getName')->willReturn('Test Store');
        $store->method('getBaseUrl')->willReturn('https://shop.test/');
        $this->storeManager->method('getStore')->willReturn($store);

        $this->urlBuilder->method('getUrl')
            ->with('blog/hello-world')
            ->willReturn('https://shop.test/blog/hello-world');

        $detail = $this->makeViewModel();
        $json = $detail->getJsonLd();

        self::assertNotSame('', $json);
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($json, true);
        self::assertSame('https://schema.org', $decoded['@context']);
        self::assertSame('BlogPosting', $decoded['@type']);
        self::assertSame('Hello World', $decoded['headline']);
        self::assertIsArray($decoded['publisher']);
        self::assertSame('Organization', $decoded['publisher']['@type']);
        self::assertSame('Test Store', $decoded['publisher']['name']);
        self::assertIsArray($decoded['mainEntityOfPage']);
        self::assertSame('https://shop.test/blog/hello-world', $decoded['mainEntityOfPage']['@id']);
    }

    /**
     * @param array<string, mixed> $attrs
     */
    private function makePost(array $attrs): PostInterface&MockObject
    {
        $post = $this->createMock(PostInterface::class);
        $post->method('getTitle')->willReturn((string) ($attrs['title'] ?? 'Default Title'));
        $post->method('getUrlKey')->willReturn((string) ($attrs['url_key'] ?? 'hello-world'));
        $post->method('getOgTitle')->willReturn($attrs['og_title'] ?? null);
        $post->method('getOgDescription')->willReturn($attrs['og_description'] ?? null);
        $post->method('getOgImage')->willReturn($attrs['og_image'] ?? null);
        $post->method('getOgType')->willReturn($attrs['og_type'] ?? null);
        $post->method('getMetaTitle')->willReturn($attrs['meta_title'] ?? null);
        $post->method('getMetaDescription')->willReturn($attrs['meta_description'] ?? null);
        $post->method('getFeaturedImage')->willReturn($attrs['featured_image'] ?? null);
        $post->method('getShortContent')->willReturn($attrs['short_content'] ?? null);
        $post->method('getPublishDate')->willReturn($attrs['publish_date'] ?? null);

        return $post;
    }

    private function registerPost(PostInterface $post): void
    {
        $this->registry->method('registry')
            ->with(PostViewController::REGISTRY_KEY)
            ->willReturn($post);
    }

    private function makeViewModel(): Detail
    {
        return new Detail(
            $this->registry,
            $this->urlBuilder,
            $this->config,
            $this->storeManager,
            $this->authorRepository,
            $this->tagRepository,
            $this->searchCriteriaBuilder,
            $this->relatedPostsProvider
        );
    }
}
