<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Link;

use Magento\Framework\App\ResourceConnection;

abstract class AbstractPivotLinkManager
{
    public function __construct(protected readonly ResourceConnection $resource)
    {
    }

    abstract protected function pivotTable(): string;

    abstract protected function leftColumn(): string;

    abstract protected function rightColumn(): string;

    /**
     * @return int[]
     */
    public function getLinkedIds(int $leftId): array
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from($this->resource->getTableName($this->pivotTable()), [$this->rightColumn()])
            ->where($this->leftColumn() . ' = ?', $leftId);

        return array_map('intval', $connection->fetchCol($select));
    }

    /**
     * @param int[] $rightIds
     */
    public function sync(int $leftId, array $rightIds): void
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName($this->pivotTable());
        $connection->delete($table, [$this->leftColumn() . ' = ?' => $leftId]);

        $rightIds = array_values(array_unique(array_map('intval', $rightIds)));
        if ($rightIds === []) {
            return;
        }

        $rows = [];
        foreach ($rightIds as $rightId) {
            $rows[] = [
                $this->leftColumn() => $leftId,
                $this->rightColumn() => $rightId,
            ];
        }
        $connection->insertMultiple($table, $rows);
    }
}
