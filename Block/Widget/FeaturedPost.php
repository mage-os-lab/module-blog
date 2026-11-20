<?php

declare(strict_types=1);

namespace MageOS\Blog\Block\Widget;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Block\BlogCacheIdentities;
use MageOS\Blog\Model\Url\Permalink;

class FeaturedPost extends Template implements BlockInterface, IdentityInterface
{
    use BlogCacheIdentities;

    protected $_template = 'MageOS_Blog::widget/featured-post.phtml';

    /**
     * @return string[]
     */
    public function getIdentities(): array
    {
        $post = $this->getPost();

        return $post === null ? [] : $this->postIdentities([$post]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        Template\Context $context,
        private readonly PostRepositoryInterface $repository,
        private readonly Permalink $permalink,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getPost(): ?PostInterface
    {
        $postId = (int) $this->getData('post_id');
        if ($postId <= 0) {
            return null;
        }
        try {
            return $this->repository->getById($postId);
        } catch (NoSuchEntityException) {
            return null;
        }
    }

    public function getPostUrl(PostInterface $post): string
    {
        return $this->_urlBuilder->getUrl(
            '',
            ['_direct' => $this->permalink->getPostPath($post->getUrlKey())]
        );
    }

    public function getTitleSafe(): string
    {
        return (string) $this->getData('title');
    }
}
