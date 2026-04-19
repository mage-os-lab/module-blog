<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\UrlKeyGenerator;

use Magento\Framework\App\ResourceConnection;
use MageOS\Blog\Api\UrlKeyGeneratorInterface;

final class DbCollisionChecker implements CollisionChecker
{
    /**
     * @var array<string, array{table: string, id: string, urlKey: string, storeTable: ?string, storeFk: ?string}>
     */
    private const ENTITY_MAP = [
        UrlKeyGeneratorInterface::ENTITY_POST => [
            'table' => 'mageos_blog_post',
            'id' => 'post_id',
            'urlKey' => 'url_key',
            'storeTable' => 'mageos_blog_post_store',
            'storeFk' => 'post_id',
        ],
        UrlKeyGeneratorInterface::ENTITY_CATEGORY => [
            'table' => 'mageos_blog_category',
            'id' => 'category_id',
            'urlKey' => 'url_key',
            'storeTable' => 'mageos_blog_category_store',
            'storeFk' => 'category_id',
        ],
        UrlKeyGeneratorInterface::ENTITY_TAG => [
            'table' => 'mageos_blog_tag',
            'id' => 'tag_id',
            'urlKey' => 'url_key',
            'storeTable' => 'mageos_blog_tag_store',
            'storeFk' => 'tag_id',
        ],
        UrlKeyGeneratorInterface::ENTITY_AUTHOR => [
            'table' => 'mageos_blog_author',
            'id' => 'author_id',
            'urlKey' => 'slug',
            'storeTable' => null,
            'storeFk' => null,
        ],
    ];

    public function __construct(private readonly ResourceConnection $resource)
    {
    }

    public function isTaken(string $urlKey, string $entityType, ?int $storeId, ?int $excludingEntityId = null): bool
    {
        if (!isset(self::ENTITY_MAP[$entityType])) {
            throw new \InvalidArgumentException("Unknown entity type '{$entityType}'.");
        }
        $map = self::ENTITY_MAP[$entityType];
        $connection = $this->resource->getConnection();
        $entityTable = $this->resource->getTableName($map['table']);

        $select = $connection->select()
            ->from(['e' => $entityTable], [$map['id']])
            ->where("e.{$map['urlKey']} = ?", $urlKey);

        if ($storeId !== null && $map['storeTable'] !== null) {
            $storeTable = $this->resource->getTableName($map['storeTable']);
            $select->join(
                ['s' => $storeTable],
                "s.{$map['storeFk']} = e.{$map['id']}",
                []
            )->where('s.store_id IN (?)', [$storeId, 0]);
        }

        if ($excludingEntityId !== null) {
            $select->where("e.{$map['id']} != ?", $excludingEntityId);
        }

        return (bool) $connection->fetchOne($select);
    }
}
