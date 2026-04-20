<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\Mapper;

use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Model\BlogPostStatus;

class PostMapper
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(PostInterface $post): array
    {
        return [
            'id' => $post->getPostId(),
            'url_key' => $post->getUrlKey(),
            'title' => $post->getTitle(),
            'content' => $post->getContent(),
            'short_content' => $post->getShortContent(),
            'featured_image' => $post->getFeaturedImage(),
            'featured_image_alt' => $post->getFeaturedImageAlt(),
            'author_id' => $post->getAuthorId(),
            'publish_date' => $post->getPublishDate(),
            'reading_time' => $post->getReadingTime(),
            'views_count' => $post->getViewsCount(),
            'status' => strtoupper(BlogPostStatus::from($post->getStatus())->name),
            'meta_title' => $post->getMetaTitle(),
            'meta_description' => $post->getMetaDescription(),
            'meta_keywords' => $post->getMetaKeywords(),
            'meta_robots' => $post->getMetaRobots(),
            'og_title' => $post->getOgTitle(),
            'og_description' => $post->getOgDescription(),
            'og_image' => $post->getOgImage(),
            'og_type' => $post->getOgType(),
            'store_ids' => $post->getStoreIds(),
            'category_ids' => $post->getCategoryIds(),
            'tag_ids' => $post->getTagIds(),
        ];
    }
}
