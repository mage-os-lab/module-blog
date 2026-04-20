<?php

declare(strict_types=1);

namespace MageOS\Blog\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Sql\Expression;
use MageOS\Blog\Api\PostManagementInterface;
use MageOS\Blog\Api\PostRepositoryInterface;

class PostManagement implements PostManagementInterface
{
    private const WORDS_PER_MINUTE = 200;

    public function __construct(
        private readonly PostRepositoryInterface $repository,
        private readonly ResourceConnection $resource
    ) {
    }

    public function publish(int $postId): void
    {
        $post = $this->repository->getById($postId);
        $post->setStatus(BlogPostStatus::Published->value);
        $this->repository->save($post);
    }

    public function incrementViews(int $postId): void
    {
        $connection = $this->resource->getConnection();
        $connection->update(
            $this->resource->getTableName('mageos_blog_post'),
            ['views_count' => new Expression('views_count + 1')],
            ['post_id = ?' => $postId]
        );
    }

    public function computeReadingTime(string $content): int
    {
        $plainText = trim(strip_tags($content));
        if ($plainText === '') {
            return 0;
        }
        $wordCount = str_word_count($plainText);
        if ($wordCount === 0) {
            return 0;
        }

        return max(1, (int) ceil($wordCount / self::WORDS_PER_MINUTE));
    }
}
