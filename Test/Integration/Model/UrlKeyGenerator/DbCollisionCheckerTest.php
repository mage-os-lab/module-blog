<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Integration\Model\UrlKeyGenerator;

use Magento\Framework\App\ResourceConnection;
use Magento\TestFramework\Helper\Bootstrap;
use MageOS\Blog\Api\UrlKeyGeneratorInterface;
use MageOS\Blog\Model\UrlKeyGenerator\CollisionChecker;
use PHPUnit\Framework\TestCase;

final class DbCollisionCheckerTest extends TestCase
{
    private CollisionChecker $checker;
    private ResourceConnection $resource;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->checker = $objectManager->get(CollisionChecker::class);
        $this->resource = $objectManager->get(ResourceConnection::class);
    }

    public function testIsTakenReturnsFalseWhenSlugIsAvailable(): void
    {
        self::assertFalse(
            $this->checker->isTaken('totally-new-slug', UrlKeyGeneratorInterface::ENTITY_POST, null)
        );
    }

    public function testIsTakenReturnsTrueWhenPostSlugExists(): void
    {
        $connection = $this->resource->getConnection();
        $connection->insert(
            $this->resource->getTableName('mageos_blog_post'),
            ['url_key' => 'existing-post', 'title' => 'Existing']
        );

        self::assertTrue(
            $this->checker->isTaken('existing-post', UrlKeyGeneratorInterface::ENTITY_POST, null)
        );
    }

    public function testIsTakenExcludesTheProvidedEntityId(): void
    {
        $connection = $this->resource->getConnection();
        $connection->insert(
            $this->resource->getTableName('mageos_blog_post'),
            ['url_key' => 'edit-me', 'title' => 'Edit Me']
        );
        $postId = (int) $connection->lastInsertId();

        self::assertFalse(
            $this->checker->isTaken(
                'edit-me',
                UrlKeyGeneratorInterface::ENTITY_POST,
                null,
                $postId
            ),
            'Excluding own id should report the slug as available.'
        );
    }

    public function testIsTakenThrowsOnUnknownEntityType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->checker->isTaken('foo', 'widget', null);
    }

    public function testIsTakenScopesByStoreIdForPost(): void
    {
        $connection = $this->resource->getConnection();
        $connection->insert(
            $this->resource->getTableName('mageos_blog_post'),
            ['url_key' => 'store-scoped', 'title' => 'Store Scoped']
        );
        $postId = (int) $connection->lastInsertId();
        $connection->insert(
            $this->resource->getTableName('mageos_blog_post_store'),
            ['post_id' => $postId, 'store_id' => 1]
        );

        self::assertTrue(
            $this->checker->isTaken('store-scoped', UrlKeyGeneratorInterface::ENTITY_POST, 1)
        );
        self::assertFalse(
            $this->checker->isTaken('store-scoped', UrlKeyGeneratorInterface::ENTITY_POST, 2)
        );
    }

    public function testIsTakenIgnoresStoreScopeForAuthor(): void
    {
        $connection = $this->resource->getConnection();
        $connection->insert(
            $this->resource->getTableName('mageos_blog_author'),
            ['slug' => 'the-author', 'name' => 'The Author']
        );

        self::assertTrue(
            $this->checker->isTaken('the-author', UrlKeyGeneratorInterface::ENTITY_AUTHOR, 99)
        );
    }
}
