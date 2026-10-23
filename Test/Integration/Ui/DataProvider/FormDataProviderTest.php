<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Integration\Ui\DataProvider;

use Magento\Framework\App\RequestInterface;
use Magento\TestFramework\Helper\Bootstrap;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\Data\CategoryInterfaceFactory;
use MageOS\Blog\Api\Data\PostInterfaceFactory;
use MageOS\Blog\Api\Data\TagInterfaceFactory;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Model\BlogPostStatus;
use MageOS\Blog\Ui\DataProvider\Category\Form\DataProvider as CategoryFormProvider;
use MageOS\Blog\Ui\DataProvider\Post\Form\DataProvider as PostFormProvider;
use MageOS\Blog\Ui\DataProvider\Tag\Form\DataProvider as TagFormProvider;
use PHPUnit\Framework\TestCase;

/**
 * Opening a saved entity in the admin must load its form data. Regression for
 * #43: the store-ids join exposed a second `post_id` / `category_id` column, so
 * the form's unqualified id filter failed with SQLSTATE 1052 (ambiguous column).
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
final class FormDataProviderTest extends TestCase
{
    public function test_post_form_loads_saved_post(): void
    {
        $post = Bootstrap::getObjectManager()->create(PostInterfaceFactory::class)->create();
        $post->setTitle('Form Post')
            ->setUrlKey('form-post')
            ->setStatus(BlogPostStatus::Published->value)
            ->setStoreIds([1]);
        $saved = Bootstrap::getObjectManager()->get(PostRepositoryInterface::class)->save($post);
        $id = (int) $saved->getPostId();

        $this->request()->setParam('post_id', $id);
        $data = $this->provider(PostFormProvider::class, 'post_id')->getData();

        self::assertArrayHasKey($id, $data);
        self::assertSame('Form Post', $data[$id]['title']);
        self::assertSame(['1'], $data[$id]['store_ids']);
    }

    public function test_category_form_loads_saved_category(): void
    {
        $category = Bootstrap::getObjectManager()->create(CategoryInterfaceFactory::class)->create();
        $category->setTitle('Form Category')
            ->setUrlKey('form-category')
            ->setStoreIds([1]);
        $saved = Bootstrap::getObjectManager()->get(CategoryRepositoryInterface::class)->save($category);
        $id = (int) $saved->getCategoryId();

        $this->request()->setParam('category_id', $id);
        $data = $this->provider(CategoryFormProvider::class, 'category_id')->getData();

        self::assertArrayHasKey($id, $data);
        self::assertSame('Form Category', $data[$id]['title']);
        self::assertSame(['1'], $data[$id]['store_ids']);
    }

    public function test_tag_form_loads_saved_tag(): void
    {
        $tag = Bootstrap::getObjectManager()->create(TagInterfaceFactory::class)->create();
        $tag->setTitle('Form Tag')
            ->setUrlKey('form-tag')
            ->setStoreIds([1]);
        $saved = Bootstrap::getObjectManager()->get(TagRepositoryInterface::class)->save($tag);
        $id = (int) $saved->getTagId();

        $this->request()->setParam('tag_id', $id);
        $data = $this->provider(TagFormProvider::class, 'tag_id')->getData();

        self::assertArrayHasKey($id, $data);
        self::assertSame('Form Tag', $data[$id]['title']);
        self::assertSame(['1'], $data[$id]['store_ids']);
    }

    private function request(): RequestInterface
    {
        return Bootstrap::getObjectManager()->get(RequestInterface::class);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private function provider(string $class, string $idField): object
    {
        return Bootstrap::getObjectManager()->create($class, [
            'name' => 'form_data_source',
            'primaryFieldName' => $idField,
            'requestFieldName' => $idField,
        ]);
    }
}
