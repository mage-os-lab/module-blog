<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Url;

use Magento\Framework\App\ResourceConnection;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Magento\UrlRewrite\Service\V1\Data\UrlRewriteFactory;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Model\Config;
use MageOS\Blog\Model\Url\Permalink;
use MageOS\Blog\Model\Url\RedirectBuilder;
use MageOS\Blog\Model\Url\StaleStoreRewriteCleaner;
use MageOS\Blog\Model\Url\UrlRewriteBuilder;
use MageOS\Blog\Model\Url\UrlRewriteRegenerator;
use MageOS\Blog\Test\Unit\Plugin\Repository\InMemoryUrlStorage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../Plugin/Repository/UrlRewriteFactoryStub.php';

/**
 * Rewrites of the blog home and search pages, which the front name also serves without one.
 */
final class UrlRewriteRegeneratorIndexTest extends TestCase
{
    private InMemoryUrlStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new InMemoryUrlStorage();
    }

    #[Test]
    public function every_store_gets_its_home_and_search_page_routed_to_the_full_route(): void
    {
        $this->regenerate(PermalinkFixture::create());

        self::assertEqualsCanonicalizing(
            [
                '1|blog|blog/index/index|0',
                '1|blog/search|blog/search/index|0',
                '2|blog|blog/index/index|0',
                '2|blog/search|blog/search/index|0',
            ],
            $this->rows()
        );
    }

    #[Test]
    public function a_store_with_its_own_route_redirects_the_front_name_to_it(): void
    {
        $this->regenerate($this->dutchStore());

        self::assertEqualsCanonicalizing(
            [
                '1|blog|blog/index/index|0',
                '1|blog/search|blog/search/index|0',
                '2|blog|nieuws|301',
                '2|blog/search|nieuws/zoeken|301',
                '2|nieuws|blog/index/index|0',
                '2|nieuws/zoeken|blog/search/index|0',
            ],
            $this->rows()
        );
    }

    #[Test]
    public function a_changed_route_redirects_the_previous_one_straight_to_the_new_one(): void
    {
        $this->regenerate($this->dutchStore());
        $this->regenerate(PermalinkFixture::create([], [2 => [Config::XML_PATH_PERMALINK_ROUTE => 'actueel']]));

        self::assertContains('2|nieuws|actueel|301', $this->rows());
        self::assertContains('2|blog|actueel|301', $this->rows());
        self::assertContains('2|actueel|blog/index/index|0', $this->rows());
    }

    #[Test]
    public function going_back_to_the_front_name_serves_it_again(): void
    {
        $this->regenerate($this->dutchStore());
        $this->regenerate(PermalinkFixture::create());

        self::assertContains('2|blog|blog/index/index|0', $this->rows());
        self::assertContains('2|nieuws|blog|301', $this->rows());
        self::assertNotContains('2|blog|nieuws|301', $this->rows());
    }

    #[Test]
    public function regenerating_twice_changes_nothing(): void
    {
        $this->regenerate($this->dutchStore());
        $first = $this->rows();
        $this->regenerate($this->dutchStore());

        self::assertSame($first, $this->rows());
    }

    #[Test]
    public function no_rewrite_forwards_to_a_path_that_another_rewrite_claims(): void
    {
        // The url_rewrite router runs again on the target of a forward, so that would loop.
        $this->regenerate($this->dutchStore());

        foreach ($this->storage->rows as $row) {
            if ((int) $row->getRedirectType() !== 0) {
                continue;
            }
            foreach ($this->storage->rows as $other) {
                self::assertFalse(
                    $other->getStoreId() === $row->getStoreId()
                        && $other->getRequestPath() === $row->getTargetPath()
                        && $other->getRequestPath() !== $row->getRequestPath(),
                    $row->getRequestPath() . ' forwards to ' . $row->getTargetPath() . ', which is a rewrite itself'
                );
            }
        }
    }

    #[Test]
    public function a_dry_run_counts_without_writing(): void
    {
        $result = $this->regenerator($this->dutchStore())->regenerate(UrlRewriteRegenerator::TYPE_INDEX, true);

        self::assertSame(['rewrites' => 4, 'redirects' => 2, 'errors' => []], $result);
        self::assertSame([], $this->storage->rows);
    }

    private function dutchStore(): Permalink
    {
        return PermalinkFixture::create([], [
            2 => [
                Config::XML_PATH_PERMALINK_ROUTE => 'nieuws',
                Config::XML_PATH_PERMALINK_SEARCH => 'zoeken',
            ],
        ]);
    }

    private function regenerate(Permalink $permalink): void
    {
        $result = $this->regenerator($permalink)->regenerate(UrlRewriteRegenerator::TYPE_INDEX);

        self::assertSame([], $result['errors']);
    }

    private function regenerator(Permalink $permalink): UrlRewriteRegenerator
    {
        $factory = new UrlRewriteFactory();

        return new UrlRewriteRegenerator(
            $this->createStub(ResourceConnection::class),
            new UrlRewriteBuilder($factory, $this->storeManager(), $permalink),
            new RedirectBuilder($this->storage, $factory, $this->storage),
            new StaleStoreRewriteCleaner($this->storage, $this->storage),
            $this->storage,
            $this->createStub(PostRepositoryInterface::class),
            $this->createStub(CategoryRepositoryInterface::class),
            $this->createStub(TagRepositoryInterface::class),
            $this->createStub(AuthorRepositoryInterface::class),
        );
    }

    /**
     * @return string[] "storeId|requestPath|targetPath|redirectType", sorted
     */
    private function rows(): array
    {
        $rows = array_map(
            static fn (UrlRewrite $row): string => implode('|', [
                $row->getStoreId(),
                $row->getRequestPath(),
                $row->getTargetPath(),
                (int) $row->getRedirectType(),
            ]),
            $this->storage->rows
        );
        sort($rows);

        return $rows;
    }

    private function storeManager(): StoreManagerInterface
    {
        $stores = array_map(function (int $id): StoreInterface {
            $store = $this->createStub(StoreInterface::class);
            $store->method('getId')->willReturn($id);

            return $store;
        }, [1, 2]);

        $manager = $this->createStub(StoreManagerInterface::class);
        $manager->method('getStores')->willReturn($stores);

        return $manager;
    }
}
