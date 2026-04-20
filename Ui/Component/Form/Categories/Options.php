<?php

declare(strict_types=1);

namespace MageOS\Blog\Ui\Component\Form\Categories;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Data\OptionSourceInterface;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\Data\CategoryInterface;

/**
 * Options for the post-edit form's `category_ids` picker. Hierarchical optgroup
 * format — ui-select renders this with visible nesting.
 */
class Options implements OptionSourceInterface
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function toOptionArray(): array
    {
        $criteria = $this->searchCriteriaBuilder
            ->addFilter(CategoryInterface::IS_ACTIVE, 1)
            ->create();
        /** @var CategoryInterface[] $categories */
        $categories = $this->categoryRepository
            ->getList($criteria)
            ->getItems();

        /** @var array<int, CategoryInterface[]> $byParent */
        $byParent = [];
        foreach ($categories as $cat) {
            $parentId = $cat->getParentId() === null ? 0 : (int) $cat->getParentId();
            $byParent[$parentId][] = $cat;
        }

        $tree = [];
        foreach ($byParent[0] ?? [] as $root) {
            $tree[] = $this->branch($root, $byParent);
        }
        return $tree;
    }

    /**
     * @param array<int, CategoryInterface[]> $byParent
     *
     * @return array<string, mixed>
     */
    private function branch(CategoryInterface $category, array $byParent): array
    {
        $id = (int) $category->getCategoryId();
        $node = [
            'value' => $id,
            'label' => (string) $category->getTitle(),
        ];
        if (!empty($byParent[$id])) {
            $node['optgroup'] = array_map(
                fn (CategoryInterface $child) => $this->branch($child, $byParent),
                $byParent[$id],
            );
        }
        return $node;
    }
}
