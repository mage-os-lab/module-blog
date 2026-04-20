<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\Author;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Model\Resolver\AdminAuthorization;

class DeleteResolver implements ResolverInterface
{
    public function __construct(
        private readonly AuthorRepositoryInterface $authorRepository,
        private readonly AdminAuthorization $adminAuthorization,
    ) {
    }

    /**
     * @param array<string, mixed>|null $value
     * @param array<string, mixed>|null $args
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null,
    ): bool {
        $this->adminAuthorization->assertAuthorized($context, 'MageOS_Blog::author');

        if (!isset($args['id'])) {
            throw new GraphQlInputException(__('Missing "id" argument.'));
        }

        try {
            $this->authorRepository->deleteById((int) $args['id']);
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }

        return true;
    }
}
