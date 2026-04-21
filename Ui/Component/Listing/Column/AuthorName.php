<?php

declare(strict_types=1);

namespace MageOS\Blog\Ui\Component\Listing\Column;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Renders the post-listing `author_id` column as a linked author name.
 *
 * Pulls names + slugs in a single SELECT against `mageos_blog_author` for the
 * set of IDs present in the current grid page, then rewrites each row to
 * contain an `<a>` pointing at the author edit form.
 */
class AuthorName extends Column
{
    /**
     * @param array<string, mixed> $components
     * @param array<string, mixed> $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly ResourceConnection $resource,
        private readonly UrlInterface $urlBuilder,
        private readonly Escaper $escaper,
        array $components = [],
        array $data = [],
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array<string, mixed> $dataSource
     *
     * @return array<string, mixed>
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items']) || !\is_array($dataSource['data']['items'])) {
            return $dataSource;
        }

        $items = $dataSource['data']['items'];
        $authorIds = [];
        foreach ($items as $row) {
            $authorId = (int) ($row['author_id'] ?? 0);
            if ($authorId > 0) {
                $authorIds[$authorId] = $authorId;
            }
        }

        $authors = $authorIds !== [] ? $this->loadAuthors(array_values($authorIds)) : [];

        $fieldName = (string) $this->getData('name');
        foreach ($dataSource['data']['items'] as &$row) {
            $authorId = (int) ($row['author_id'] ?? 0);
            $row[$fieldName] = $authorId > 0 && isset($authors[$authorId])
                ? $this->renderLink($authorId, (string) $authors[$authorId])
                : '';
        }
        unset($row);

        return $dataSource;
    }

    /**
     * @param int[] $ids
     *
     * @return array<int, string> id → name
     */
    private function loadAuthors(array $ids): array
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from(
                $this->resource->getTableName('mageos_blog_author'),
                ['author_id', 'name'],
            )
            ->where('author_id IN (?)', $ids);

        $rows = $connection->fetchAll($select);
        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row['author_id']] = (string) $row['name'];
        }
        return $out;
    }

    private function renderLink(int $authorId, string $name): string
    {
        $url = $this->urlBuilder->getUrl(
            'mageos_blog/author/edit',
            ['author_id' => $authorId],
        );

        $label = $this->escaper->escapeHtml($name);
        if (\is_array($label)) {
            $label = implode(' ', $label);
        }

        return \sprintf(
            '<a href="%s">%s</a>',
            $this->escaper->escapeUrl($url),
            $label,
        );
    }
}
