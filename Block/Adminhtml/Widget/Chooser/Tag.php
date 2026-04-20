<?php

declare(strict_types=1);

namespace MageOS\Blog\Block\Adminhtml\Widget\Chooser;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Helper\Data as BackendHelper;
use Magento\Framework\Data\Form\Element\AbstractElement;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Model\ResourceModel\Tag\CollectionFactory;

class Tag extends Extended
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        BackendHelper $backendHelper,
        private readonly CollectionFactory $collectionFactory,
        private readonly TagRepositoryInterface $repository,
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);
    }

    public function prepareElementHtml(AbstractElement $element): AbstractElement
    {
        $uniqId = $this->mathRandom->getUniqueHash((string) $element->getId());
        $sourceUrl = $this->getUrl('mageos_blog/widget/chooser/tag', ['uniq_id' => $uniqId]);

        /** @var \Magento\Widget\Block\Adminhtml\Widget\Chooser $chooser */
        $chooser = $this->getLayout()->createBlock(
            \Magento\Widget\Block\Adminhtml\Widget\Chooser::class
        );
        $chooser->setElement($element)
            ->setConfig($this->getConfig())
            ->setFieldsetId($this->getFieldsetId())
            ->setSourceUrl($sourceUrl)
            ->setUniqId($uniqId);

        if ($element->getValue()) {
            try {
                $tag = $this->repository->getById((int) $element->getValue());
                $chooser->setLabel($this->escapeHtml((string) $tag->getTitle()));
            } catch (\Throwable) {
                // ignore
            }
        }
        $element->setData('after_element_html', $chooser->toHtml());
        return $element;
    }

    protected function _prepareCollection(): self
    {
        $collection = $this->collectionFactory->create();
        $this->setCollection($collection);
        parent::_prepareCollection();
        return $this;
    }

    protected function _prepareColumns(): self
    {
        $this->addColumn('tag_id', ['header' => __('ID'), 'index' => 'tag_id', 'type' => 'number']);
        $this->addColumn('title', ['header' => __('Title'), 'index' => 'title']);
        $this->addColumn('url_key', ['header' => __('URL Key'), 'index' => 'url_key']);
        parent::_prepareColumns();
        return $this;
    }

    public function getRowUrl($row): string
    {
        return '#';
    }
}
