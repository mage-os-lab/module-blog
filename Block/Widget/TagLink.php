<?php

declare(strict_types=1);

namespace MageOS\Blog\Block\Widget;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;
use MageOS\Blog\Api\Data\TagInterface;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Model\Url\Permalink;

class TagLink extends Template implements BlockInterface
{
    protected $_template = 'MageOS_Blog::widget/tag-link.phtml';

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        Template\Context $context,
        private readonly TagRepositoryInterface $repository,
        private readonly Permalink $permalink,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getTag(): ?TagInterface
    {
        $id = (int) $this->getData('tag_id');
        if ($id <= 0) {
            return null;
        }
        try {
            return $this->repository->getById($id);
        } catch (NoSuchEntityException) {
            return null;
        }
    }

    public function getTagUrl(TagInterface $tag): string
    {
        return $this->_urlBuilder->getUrl(
            '',
            ['_direct' => $this->permalink->getTagPath($tag->getUrlKey())]
        );
    }

    public function getLabel(TagInterface $tag): string
    {
        $label = trim((string) $this->getData('label'));
        return $label !== '' ? $label : (string) $tag->getTitle();
    }
}
