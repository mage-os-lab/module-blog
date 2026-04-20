<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\Author;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Model\AuthorFactory;
use MageOS\Blog\Model\Resolver\AdminAuthorization;
use MageOS\Blog\Model\Resolver\Mapper\AuthorMapper;

class CreateResolver implements ResolverInterface
{
    public function __construct(
        private readonly AuthorFactory $authorFactory,
        private readonly AuthorRepositoryInterface $authorRepository,
        private readonly AuthorMapper $authorMapper,
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
        $this->adminAuthorization->assertAuthorized($context, 'MageOS_Blog::author');

        $input = $args['input'] ?? null;
        if (!\is_array($input)) {
            throw new GraphQlInputException(__('Missing "input" argument.'));
        }

        $author = $this->authorFactory->create();

        if (\array_key_exists('slug', $input)) {
            $author->setSlug((string) $input['slug']);
        }
        if (\array_key_exists('name', $input)) {
            $author->setName((string) $input['name']);
        }
        if (\array_key_exists('bio', $input)) {
            $author->setBio($input['bio'] === null ? null : (string) $input['bio']);
        }
        if (\array_key_exists('avatar', $input)) {
            $author->setAvatar($input['avatar'] === null ? null : (string) $input['avatar']);
        }
        if (\array_key_exists('email', $input)) {
            $author->setEmail($input['email'] === null ? null : (string) $input['email']);
        }
        if (\array_key_exists('twitter', $input)) {
            $author->setTwitter($input['twitter'] === null ? null : (string) $input['twitter']);
        }
        if (\array_key_exists('linkedin', $input)) {
            $author->setLinkedin($input['linkedin'] === null ? null : (string) $input['linkedin']);
        }
        if (\array_key_exists('website', $input)) {
            $author->setWebsite($input['website'] === null ? null : (string) $input['website']);
        }
        if (\array_key_exists('is_active', $input) && $input['is_active'] !== null) {
            $author->setIsActive((bool) $input['is_active']);
        }

        try {
            $saved = $this->authorRepository->save($author);
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()), $e);
        }

        return $this->authorMapper->toArray($saved);
    }
}
