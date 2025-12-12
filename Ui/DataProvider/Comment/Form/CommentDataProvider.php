<?php
declare(strict_types=1);

namespace MageOS\Blog\Ui\DataProvider\Comment\Form;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use MageOS\Blog\Model\ResourceModel\Comment\Collection as CommentCollection;
use MageOS\Blog\Model\ResourceModel\Comment\CollectionFactory;

class CommentDataProvider extends AbstractDataProvider
{
    /**
     * @var CommentCollection
     */
    protected $collection;
    protected DataPersistorInterface $dataPersistor;
    protected array $loadedData = [];
    protected UrlInterface $url;
    protected Escaper $escaper;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $commentCollectionFactory,
        DataPersistorInterface $dataPersistor,
        UrlInterface $url,
        array $meta = [],
        array $data = [],
        Escaper $escaper = null
    ) {
        $this->collection = $commentCollectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->meta = $this->prepareMeta($this->meta);
        $this->url = $url;

        $this->escaper = $escaper ?: ObjectManager::getInstance()->create(
            Escaper::class
        );
    }

    /**
     * Prepares Meta
     *
     * @param array $meta
     * @return array
     */
    public function prepareMeta(array $meta): array
    {
        return $meta;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData) && !empty($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();
        /** @var $comment \MageOS\Blog\Model\Comment */
        foreach ($items as $comment) {
            $this->loadedData[$comment->getId()] = $comment->getData();

            $post = $comment->getPost();
            $this->loadedData[$comment->getId()]['post_url'] = [
                'url' => $this->url->getUrl('blog/post/edit', ['id' => $post->getId()]),
                'title' => $post->getTitle(),
                'text' => '#' . $post->getId() . '. ' . $post->getTitle(),
            ];

            $author = $comment->getAuthor();
            $guestData = [
                'url' => 'mailto:' . $author->getEmail(),
                'title' => $author->getNickname(),
                'text' => $author->getNickname() .
                    ' - ' . $author->getEmail() .
                    ' (' . __('Guest')  . ')',
            ];

            switch ($comment->getAuthorType()) {
                case \MageOS\Blog\Model\Config\Source\AuthorType::GUEST:
                    $this->loadedData[$comment->getId()]['author_url'] = $guestData;
                    break;
                case \MageOS\Blog\Model\Config\Source\AuthorType::CUSTOMER:
                    if ($author->getCustomer()) {
                        $this->loadedData[$comment->getId()]['author_url'] = [
                            'url' => $this->url->getUrl(
                                'customer/index/edit',
                                ['id' => $comment->getCustomerId()]
                            ),
                            'title' => $author->getNickname(),
                            'text' => '#' . $comment->getCustomerId() .
                                '. ' . $author->getNickname() .
                                ' (' . __('Customer') . ')',
                        ];
                    } else {
                        $this->loadedData[$comment->getId()]['author_url'] = $guestData;
                    }

                    break;
                case \MageOS\Blog\Model\Config\Source\AuthorType::ADMIN:
                    if ($author->getAdmin()) {
                        $this->loadedData[$comment->getId()]['author_url'] = [
                            'url' => $this->url->getUrl(
                                'admin/user/edit',
                                ['id' => $comment->getAdminId()]
                            ),
                            'title' => $author->getNickname(),
                            'text' => '#' . $comment->getAdminId() .
                                '. ' . $author->getNickname() .
                                ' (' . __('Admin') . ')',
                        ];
                    } else {
                        $this->loadedData[$comment->getId()]['author_url'] = $guestData;
                    }
                    break;
            }

            if ($comment->getParentId()
                && ($parentComment = $comment->getParentComment())
            ) {
                $text = (mb_strlen($parentComment->getText()) > 200) ?
                    (mb_substr($parentComment->getText(), 0, 200) . '...') :
                    $parentComment->getText();
                $text = $this->escaper->escapeHtml($text);
                $this->loadedData[$comment->getId()]['parent_url'] = [
                    'url' => $this->url->getUrl('blog/comment/edit', ['id' => $parentComment->getId()]),
                    'title' => $this->escaper->escapeHtml($parentComment->getText()),
                    'text' => '#' . $parentComment->getId() . '. ' . $text,
                ];
            } else {
                $this->loadedData[$comment->getId()]['parent_url'] = [
                    'url' => '',
                    'title' => '',
                    'text' => '',
                ];
            }
        }

        $data = $this->dataPersistor->get('blog_comment_form_data');
        if (!empty($data)) {
            $comment = $this->collection->getNewEmptyItem();
            $comment->setData($data);
            $this->loadedData[$comment->getId()] = $comment->getData();
            $this->dataPersistor->clear('blog_comment_form_data');
        }

        return $this->loadedData;
    }
}
