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
     * One query for any number of left ids. Every requested id is a key of the result, linked or not.
     *
     * @param int[] $leftIds
     * @return array<int, int[]>
     */
    public function getLinkedIdsForMany(array $leftIds): array
    {
        $leftIds = array_values(array_unique(array_map('intval', $leftIds)));
        $linked = array_fill_keys($leftIds, []);
        if ($leftIds === []) {
            return $linked;
        }

        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from($this->resource->getTableName($this->pivotTable()), [$this->leftColumn(), $this->rightColumn()])
            ->where($this->leftColumn() . ' IN (?)', $leftIds);

        foreach ($connection->fetchAll($select) as $row) {
            $linked[(int) $row[$this->leftColumn()]][] = (int) $row[$this->rightColumn()];
        }

        return $linked;
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
