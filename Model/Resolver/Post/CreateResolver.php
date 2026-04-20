<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\Post;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Model\BlogPostStatus;
use MageOS\Blog\Model\PostFactory;
use MageOS\Blog\Model\Resolver\AdminAuthorization;
use MageOS\Blog\Model\Resolver\Mapper\PostMapper;

class CreateResolver implements ResolverInterface
{
    public function __construct(
        private readonly PostFactory $postFactory,
        private readonly PostRepositoryInterface $postRepository,
        private readonly PostMapper $postMapper,
        private readonly AdminAuthorization $adminAuthorization,
    ) {
    }

    /**
     * @param array<string, mixed>|null $value
     * @param array<string, mixed>|null $args
     * @return array<string, mixed>
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null,
    ): array {
        $this->adminAuthorization->assertAuthorized($context, 'MageOS_Blog::post');

        $input = $args['input'] ?? null;
        if (!\is_array($input)) {
            throw new GraphQlInputException(__('Missing "input" argument.'));
        }

        $post = $this->postFactory->create();

        if (\array_key_exists('url_key', $input)) {
            $post->setUrlKey((string) $input['url_key']);
        }
        if (\array_key_exists('title', $input)) {
            $post->setTitle((string) $input['title']);
        }
        if (\array_key_exists('content', $input)) {
            $post->setContent($input['content'] === null ? null : (string) $input['content']);
        }
        if (\array_key_exists('short_content', $input)) {
            $post->setShortContent($input['short_content'] === null ? null : (string) $input['short_content']);
        }
        if (\array_key_exists('featured_image', $input)) {
            $post->setFeaturedImage($input['featured_image'] === null ? null : (string) $input['featured_image']);
        }
        if (\array_key_exists('featured_image_alt', $input)) {
            $post->setFeaturedImageAlt(
                $input['featured_image_alt'] === null ? null : (string) $input['featured_image_alt'],
            );
        }
        if (\array_key_exists('author_id', $input)) {
            $post->setAuthorId($input['author_id'] === null ? null : (int) $input['author_id']);
        }
        if (\array_key_exists('publish_date', $input)) {
            $post->setPublishDate($input['publish_date'] === null ? null : (string) $input['publish_date']);
        }
        if (\array_key_exists('status', $input) && $input['status'] !== null) {
            $statusName = ucfirst(strtolower((string) $input['status']));
            $case = BlogPostStatus::cases();
            $resolved = null;
            foreach ($case as $enumCase) {
                if ($enumCase->name === $statusName) {
                    $resolved = $enumCase;
                    break;
                }
            }
            if ($resolved === null) {
                throw new GraphQlInputException(__('Invalid status value "%1".', (string) $input['status']));
            }
            $post->setStatus($resolved->value);
        }
        if (\array_key_exists('meta_title', $input)) {
            $post->setMetaTitle($input['meta_title'] === null ? null : (string) $input['meta_title']);
        }
        if (\array_key_exists('meta_description', $input)) {
            $post->setMetaDescription($input['meta_description'] === null ? null : (string) $input['meta_description']);
        }
        if (\array_key_exists('meta_keywords', $input)) {
            $post->setMetaKeywords($input['meta_keywords'] === null ? null : (string) $input['meta_keywords']);
        }
        if (\array_key_exists('meta_robots', $input)) {
            $post->setMetaRobots($input['meta_robots'] === null ? null : (string) $input['meta_robots']);
        }
        if (\array_key_exists('og_title', $input)) {
            $post->setOgTitle($input['og_title'] === null ? null : (string) $input['og_title']);
        }
        if (\array_key_exists('og_description', $input)) {
            $post->setOgDescription($input['og_description'] === null ? null : (string) $input['og_description']);
        }
        if (\array_key_exists('og_image', $input)) {
            $post->setOgImage($input['og_image'] === null ? null : (string) $input['og_image']);
        }
        if (\array_key_exists('og_type', $input)) {
            $post->setOgType($input['og_type'] === null ? null : (string) $input['og_type']);
        }
        if (\array_key_exists('store_ids', $input) && \is_array($input['store_ids'])) {
            $post->setStoreIds(array_map('intval', $input['store_ids']));
        }
        if (\array_key_exists('category_ids', $input) && \is_array($input['category_ids'])) {
            $post->setCategoryIds(array_map('intval', $input['category_ids']));
        }
        if (\array_key_exists('tag_ids', $input) && \is_array($input['tag_ids'])) {
            $post->setTagIds(array_map('intval', $input['tag_ids']));
        }

        try {
            $saved = $this->postRepository->save($post);
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()), $e);
        }

        return $this->postMapper->toArray($saved);
    }
}
