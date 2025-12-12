<?php
declare(strict_types=1);

/**
 * Blog post gallery
 */
namespace MageOS\Blog\Block\Adminhtml\Post\Helper\Form;

use Magento\Framework\Data\Form;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\AbstractBlock;
use MageOS\Blog\Block\Adminhtml\Post\Helper\Form\Gallery\Content;

/**
 * @TODO refactor to use custom registry
 */
class Gallery extends AbstractBlock
{
    protected string $fieldNameSuffix = 'post';
    protected string $htmlId = 'media_gallery';
    protected string $name = 'media_gallery';
    protected string $image = 'image';
    protected string $formName = 'blog_post_form';
    protected Form $form;
    protected Registry $registry;

    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        Registry $registry,
        Form $form,
        $data = []
    ) {
        $this->registry = $registry;
        $this->form = $form;
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function getElementHtml(): string
    {
        return $this->getContentHtml();
    }

    /**
     * Get product images
     *
     * @return array|null
     */
    public function getImages(): ?array
    {
        $result = [];
        $gallery = $this->registry->registry('current_model')->getGalleryImages();

        if (count($gallery)) {
            $result['images'] = [];
            $position = 1;
            foreach ($gallery as $image) {
                $result['images'][] = [
                    'value_id' => $image->getFile(),
                    'file' => $image->getFile(),
                    'label' => basename($image->getFile() ?: ''),
                    'position' => $position,
                    'url' => $image->getUrl(),
                ];
                $position++;
            }
        }

        return $result;
    }

    /**
     * Prepares content block
     *
     * @return string
     * @throws LocalizedException
     */
    public function getContentHtml(): string
    {
        $content = $this->getChildBlock('content');
        if (!$content) {
            $content = $this->getLayout()->createBlock(
                Content::class,
                '',
                [
                    'config' => [
                        'parentComponent' => 'blog_post_form.blog_post_form.block_gallery.block_gallery'
                    ]
                ]
            );
        }

        $content
            ->setId($this->getHtmlId() . '_content')
            ->setElement($this)
            ->setFormName($this->formName);
        $galleryJs = $content->getJsObjectName();
        $content->getUploader()->getConfig()->setMegiaGallery($galleryJs);
        return $content->toHtml();
    }

    /**
     * @return string
     */
    protected function getHtmlId(): string
    {
        return $this->htmlId;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getFieldNameSuffix(): string
    {
        return $this->fieldNameSuffix;
    }

    /**
     * @return string
     */
    public function getDataScopeHtmlId(): string
    {
        return $this->image;
    }

    /**
     * @return string
     */
    public function toHtml(): string
    {
        return $this->getElementHtml();
    }
}
