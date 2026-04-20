<?php

declare(strict_types=1);

namespace MageOS\Blog\Ui\Component\Form\RelatedPosts;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\OptionSourceInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Model\BlogPostStatus;

/**
 * Options for the post-edit form's `related_post_ids` picker. Excludes the
 * currently-edited post so a post can't relate to itself. Caps at 500 —
 * paginated search is v1.1 work.
 */
class Options implements OptionSourceInterface
{
    private const HARD_LIMIT = 500;

    public function __construct(
        private readonly PostRepositoryInterface $postRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly RequestInterface $request,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function toOptionArray(): array
    {
        $criteria = $this->searchCriteriaBuilder
            ->addFilter(PostInterface::STATUS, BlogPostStatus::Published->value)
            ->setPageSize(self::HARD_LIMIT)
            ->create();

        $posts = $this->postRepository->getList($criteria)->getItems();

        $currentId = (int) $this->request->getParam('post_id');
        $options = [];
        /** @var PostInterface $post */
        foreach ($posts as $post) {
            $id = (int) $post->getPostId();
            if ($id === $currentId) {
                continue;
            }
            $options[] = [
                'value' => $id,
                'label' => (string) $post->getTitle(),
            ];
        }
        return $options;
    }
}
