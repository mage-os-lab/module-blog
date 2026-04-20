<?php

declare(strict_types=1);

namespace MageOS\Blog\Ui\Component\Form\Tags;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Data\OptionSourceInterface;
use MageOS\Blog\Api\Data\TagInterface;
use MageOS\Blog\Api\TagRepositoryInterface;

class Options implements OptionSourceInterface
{
    public function __construct(
        private readonly TagRepositoryInterface $tagRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function toOptionArray(): array
    {
        $criteria = $this->searchCriteriaBuilder
            ->addFilter(TagInterface::IS_ACTIVE, 1)
            ->create();
        $tags = $this->tagRepository
            ->getList($criteria)
            ->getItems();

        $options = [];
        /** @var TagInterface $tag */
        foreach ($tags as $tag) {
            $options[] = [
                'value' => (int) $tag->getTagId(),
                'label' => (string) $tag->getTitle(),
            ];
        }
        return $options;
    }
}
