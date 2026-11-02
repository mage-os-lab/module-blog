<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Plugin\Repository;

use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

/**
 * Mimics the DbStorage semantics the plugins rely on: replace() clears only the entity/store pairs
 * it is given, deleteByData() matches every given column (scalar or list).
 */
final class InMemoryUrlStorage implements UrlPersistInterface, UrlFinderInterface
{
    /** @var UrlRewrite[] */
    public array $rows = [];

    /** @var array<int, array<string, mixed>> */
    public array $deleteCalls = [];

    public function replace(array $urls): array
    {
        foreach ($urls as $url) {
            $this->deleteWhere([
                UrlRewrite::ENTITY_TYPE => $url->getEntityType(),
                UrlRewrite::ENTITY_ID => $url->getEntityId(),
                UrlRewrite::STORE_ID => $url->getStoreId(),
            ]);
        }
        foreach ($urls as $url) {
            $this->rows[] = $url;
        }

        return $urls;
    }

    public function deleteByData(array $data): void
    {
        $this->deleteCalls[] = $data;
        $this->deleteWhere($data);
    }

    public function findAllByData(array $data): array
    {
        return array_values(array_filter($this->rows, fn (UrlRewrite $row): bool => $this->matches($row, $data)));
    }

    public function findOneByData(array $data): ?UrlRewrite
    {
        return $this->findAllByData($data)[0] ?? null;
    }

    /**
     * @return array<int, string> "storeId|requestPath|redirectType" per row, sorted
     */
    public function snapshot(): array
    {
        $out = array_map(
            static fn (UrlRewrite $r): string => $r->getStoreId() . '|' . $r->getRequestPath() . '|' . $r->getRedirectType(),
            $this->rows
        );
        sort($out);

        return $out;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function deleteWhere(array $data): void
    {
        $this->rows = array_values(array_filter($this->rows, fn (UrlRewrite $row): bool => !$this->matches($row, $data)));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function matches(UrlRewrite $row, array $data): bool
    {
        foreach ($data as $column => $value) {
            $actual = $row->getByKey($column);
            $accepted = \is_array($value) ? $value : [$value];
            if (!\in_array($actual, $accepted)) {
                return false;
            }
        }

        return true;
    }
}
