<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Integration\Controller;

use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\TestCase\AbstractController;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\Data\CategoryInterfaceFactory;
use MageOS\Blog\Api\Data\PostInterfaceFactory;
use MageOS\Blog\Api\Data\TagInterfaceFactory;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Model\BlogPostStatus;
use MageOS\Blog\Model\Config;
use MageOS\Blog\Test\Integration\SecondStoreTrait;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The raw id routes must honour store assignment, not just the url-key routes
 * (#34). Dispatches run on the default store view (id 1).
 *
 * An entity assigned to no store is visible everywhere: that is the rule
 * UrlRewriteBuilder and the sitemap provider already follow.
 *
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 * @magentoDataFixture Magento/Store/_files/store.php
 */
class StorefrontStoreScopeTest extends AbstractController
{
    use SecondStoreTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->_objectManager->get(MutableScopeConfigInterface::class)
            ->setValue(Config::XML_PATH_ENABLED, '1', ScopeInterface::SCOPE_STORE);
    }

    public function test_post_assigned_to_another_store_is_404(): void
    {
        $id = $this->savePost('post-other-store', [$this->secondStoreId()]);

        $this->dispatch('/blog/post/view/id/' . $id);

        $this->assertEquals(404, $this->getResponse()->getHttpResponseCode());
    }

    /**
     * @dataProvider visibleAssignments
     *
     * @param int[] $storeIds
     */
    #[DataProvider('visibleAssignments')]
    public function test_post_is_200_when_visible_on_store(array $storeIds): void
    {
        $id = $this->savePost('post-visible', $this->resolveStoreIds($storeIds));

        $this->dispatch('/blog/post/view/id/' . $id);

        $this->assertEquals(200, $this->getResponse()->getHttpResponseCode());
    }

    public function test_category_assigned_to_another_store_is_404(): void
    {
        $id = $this->saveCategory('cat-other-store', [$this->secondStoreId()]);

        $this->dispatch('/blog/category/view/id/' . $id);

        $this->assertEquals(404, $this->getResponse()->getHttpResponseCode());
    }

    /**
     * @dataProvider visibleAssignments
     *
     * @param int[] $storeIds
     */
    #[DataProvider('visibleAssignments')]
    public function test_category_is_200_when_visible_on_store(array $storeIds): void
    {
        $id = $this->saveCategory('cat-visible', $this->resolveStoreIds($storeIds));

        $this->dispatch('/blog/category/view/id/' . $id);

        $this->assertEquals(200, $this->getResponse()->getHttpResponseCode());
    }

    public function test_tag_assigned_to_another_store_is_404(): void
    {
        $id = $this->saveTag('tag-other-store', [$this->secondStoreId()]);

        $this->dispatch('/blog/tag/view/id/' . $id);

        $this->assertEquals(404, $this->getResponse()->getHttpResponseCode());
    }

    /**
     * @dataProvider visibleAssignments
     *
     * @param int[] $storeIds
     */
    #[DataProvider('visibleAssignments')]
    public function test_tag_is_200_when_visible_on_store(array $storeIds): void
    {
        $id = $this->saveTag('tag-visible', $this->resolveStoreIds($storeIds));

        $this->dispatch('/blog/tag/view/id/' . $id);

        $this->assertEquals(200, $this->getResponse()->getHttpResponseCode());
    }

    /**
     * -1 stands for the second store view, which only exists once the fixture has run.
     *
     * @return array<string, array{int[]}>
     */
    public static function visibleAssignments(): array
    {
        return [
            'current store' => [[1]],
            'all store views' => [[0]],
            'current plus another' => [[1, -1]],
            'unassigned' => [[]],
        ];
    }

    /**
     * @param int[] $storeIds
     *
     * @return int[]
     */
    private function resolveStoreIds(array $storeIds): array
    {
        return array_map(fn (int $id): int => $id === -1 ? $this->secondStoreId() : $id, $storeIds);
    }

    /**
     * @param int[] $storeIds
     */
    private function savePost(string $urlKey, array $storeIds): int
    {
        $post = $this->_objectManager->create(PostInterfaceFactory::class)->create();
        $post->setTitle($urlKey)
            ->setUrlKey($urlKey)
            ->setStatus(BlogPostStatus::Published->value)
            ->setStoreIds($storeIds);

        return (int) $this->_objectManager->get(PostRepositoryInterface::class)->save($post)->getPostId();
    }

    /**
     * @param int[] $storeIds
     */
    private function saveCategory(string $urlKey, array $storeIds): int
    {
        $category = $this->_objectManager->create(CategoryInterfaceFactory::class)->create();
        $category->setTitle($urlKey)
            ->setUrlKey($urlKey)
            ->setIsActive(true)
            ->setStoreIds($storeIds);

        return (int) $this->_objectManager->get(CategoryRepositoryInterface::class)->save($category)->getCategoryId();
    }

    /**
     * @param int[] $storeIds
     */
    private function saveTag(string $urlKey, array $storeIds): int
    {
        $tag = $this->_objectManager->create(TagInterfaceFactory::class)->create();
        $tag->setTitle($urlKey)
            ->setUrlKey($urlKey)
            ->setIsActive(true)
            ->setStoreIds($storeIds);

        return (int) $this->_objectManager->get(TagRepositoryInterface::class)->save($tag)->getTagId();
    }
}
