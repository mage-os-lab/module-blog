<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Plugin\Repository;

use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewriteFactory;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\Data\TagInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Model\Url\Permalink;
use MageOS\Blog\Model\Url\RedirectBuilder;
use MageOS\Blog\Model\Url\StaleStoreRewriteCleaner;
use MageOS\Blog\Model\Url\UrlRewriteBuilder;
use MageOS\Blog\Plugin\Repository\Category\UrlRewritePlugin as CategoryPlugin;
use MageOS\Blog\Plugin\Repository\Post\UrlRewritePlugin as PostPlugin;
use MageOS\Blog\Plugin\Repository\Tag\UrlRewritePlugin as TagPlugin;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/UrlRewriteFactoryStub.php';

final class UrlRewritePluginStoreScopeTest extends TestCase
{
    private const STORES = [1, 2, 3];

    /**
     * @return array<string, array{string}>
     */
    public static function kinds(): array
    {
        return ['post' => ['post'], 'category' => ['category'], 'tag' => ['tag']];
    }

    #[Test]
    #[DataProvider('kinds')]
    public function all_stores_to_one_store_removes_the_other_stores(string $kind): void
    {
        $storage = new InMemoryUrlStorage();
        $this->save($kind, $storage, 'slug', [0]);
        $this->save($kind, $storage, 'slug', [1]);

        self::assertSame(['1|' . $this->path($kind, 'slug') . '|0'], $storage->snapshot());
    }

    #[Test]
    #[DataProvider('kinds')]
    public function two_stores_to_one_keeps_only_the_remaining_store(string $kind): void
    {
        $storage = new InMemoryUrlStorage();
        $this->save($kind, $storage, 'slug', [1, 2]);
        $this->save($kind, $storage, 'slug', [1]);

        self::assertSame(['1|' . $this->path($kind, 'slug') . '|0'], $storage->snapshot());
    }

    #[Test]
    #[DataProvider('kinds')]
    public function one_store_to_all_stores_adds_rows_and_deletes_nothing(string $kind): void
    {
        $storage = new InMemoryUrlStorage();
        $this->save($kind, $storage, 'slug', [1]);
        $this->save($kind, $storage, 'slug', [0]);

        $p = $this->path($kind, 'slug');
        self::assertSame(['1|' . $p . '|0', '2|' . $p . '|0', '3|' . $p . '|0'], $storage->snapshot());
        self::assertSame([], $storage->deleteCalls);
    }

    #[Test]
    #[DataProvider('kinds')]
    public function unassigned_entity_is_treated_as_all_stores(string $kind): void
    {
        $storage = new InMemoryUrlStorage();
        $this->save($kind, $storage, 'slug', [1]);
        $this->save($kind, $storage, 'slug', []);

        self::assertCount(3, $storage->snapshot());
        self::assertSame([], $storage->deleteCalls);
    }

    #[Test]
    #[DataProvider('kinds')]
    public function unchanged_store_set_deletes_nothing(string $kind): void
    {
        $storage = new InMemoryUrlStorage();
        $this->save($kind, $storage, 'slug', [1, 2]);
        $this->save($kind, $storage, 'slug', [1, 2]);

        self::assertSame([], $storage->deleteCalls);
        self::assertCount(2, $storage->snapshot());
    }

    #[Test]
    #[DataProvider('kinds')]
    public function slug_change_keeps_redirects_on_remaining_stores_only(string $kind): void
    {
        $storage = new InMemoryUrlStorage();
        $this->save($kind, $storage, 'old', [1, 2]);
        $this->save($kind, $storage, 'new', [1]);

        self::assertSame(
            [
                '1|' . $this->path($kind, 'new') . '|0',
                '1|' . $this->path($kind, 'old') . '|301',
            ],
            $storage->snapshot()
        );
    }

    #[Test]
    #[DataProvider('kinds')]
    public function removed_store_redirects_from_an_earlier_slug_change_are_deleted(string $kind): void
    {
        $storage = new InMemoryUrlStorage();
        $this->save($kind, $storage, 'old', [1, 2]);
        $this->save($kind, $storage, 'new', [1, 2]);
        $this->save($kind, $storage, 'new', [2]);

        self::assertSame([], array_filter($storage->snapshot(), static fn (string $row): bool => !str_starts_with($row, '2|')));
    }

    #[Test]
    #[DataProvider('kinds')]
    public function slug_change_on_unchanged_stores_still_produces_redirects(string $kind): void
    {
        $storage = new InMemoryUrlStorage();
        $this->save($kind, $storage, 'old', [1, 2]);
        $this->save($kind, $storage, 'new', [1, 2]);

        self::assertSame([], $storage->deleteCalls);
        self::assertCount(4, $storage->snapshot());
    }

    /**
     * @param int[] $storeIds
     */
    private function save(string $kind, InMemoryUrlStorage $storage, string $slug, array $storeIds): void
    {
        $factory = new UrlRewriteFactory();
        $builder = new UrlRewriteBuilder($factory, $this->storeManager(), new Permalink());
        $cleaner = new StaleStoreRewriteCleaner($storage, $storage);
        $redirects = new RedirectBuilder($storage, $factory, $storage);

        if ($kind === 'post') {
            $entity = $this->createStub(PostInterface::class);
            $entity->method('getPostId')->willReturn(7);
            $entity->method('getUrlKey')->willReturn($slug);
            $entity->method('getStoreIds')->willReturn($storeIds);
            (new PostPlugin($builder, $storage, $cleaner, $redirects))
                ->afterSave($this->createStub(PostRepositoryInterface::class), $entity);
        } elseif ($kind === 'category') {
            $entity = $this->createStub(CategoryInterface::class);
            $entity->method('getCategoryId')->willReturn(7);
            $entity->method('getUrlKey')->willReturn($slug);
            $entity->method('getStoreIds')->willReturn($storeIds);
            (new CategoryPlugin($builder, $storage, $cleaner, $redirects))
                ->afterSave($this->createStub(CategoryRepositoryInterface::class), $entity);
        } else {
            $entity = $this->createStub(TagInterface::class);
            $entity->method('getTagId')->willReturn(7);
            $entity->method('getUrlKey')->willReturn($slug);
            $entity->method('getStoreIds')->willReturn($storeIds);
            (new TagPlugin($builder, $storage, $cleaner, $redirects))
                ->afterSave($this->createStub(TagRepositoryInterface::class), $entity);
        }
    }

    private function path(string $kind, string $slug): string
    {
        return match ($kind) {
            'post' => 'blog/' . $slug,
            'category' => 'blog/category/' . $slug,
            default => 'blog/tag/' . $slug,
        };
    }

    private function storeManager(): StoreManagerInterface
    {
        $stores = array_map(function (int $id): StoreInterface {
            $store = $this->createStub(StoreInterface::class);
            $store->method('getId')->willReturn($id);

            return $store;
        }, self::STORES);

        $manager = $this->createStub(StoreManagerInterface::class);
        $manager->method('getStores')->willReturn($stores);

        return $manager;
    }
}
