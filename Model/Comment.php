<?php
declare(strict_types=1);

namespace MageOS\Blog\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Comment model
 *
 * @method \MageOS\Blog\Model\ResourceModel\Comment _getResource()
 * @method \MageOS\Blog\Model\ResourceModel\Comment getResource()
 * @method int getPostId()
 * @method $this setPostId(int $value)
 * @method int getCustomerId()
 * @method $this setCustomerId(int $value)
 * @method int getAdminId()
 * @method $this setAdminId(int $value)
 * @method int getParentId()
 * @method $this setParentId(int $value)
 * @method int getStatus()
 * @method $this setStatus(int $value)
 * @method string getText()
 * @method $this setText(string $value)
 * @method string getCreationTime()
 * @method $this setCreationTime(string $value)
 * @method string getUpdateTime()
 * @method $this setUpdateTime(string $value)
 * @method int getAuthorType()
 * @method $this setAuthorType(int $value)
 * @method string getAuthorNickname()
 * @method $this setAuthorNickname(string $value)
 * @method string getAuthorEmail()
 * @method $this setAuthorEmail(string $value)
 */
class Comment extends AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    /**
     * @var \Magento\Framework\DataObject
     */
    protected $author;

    /**
     * @var PostFactory
     */
    protected $postFactory;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \MageOS\Blog\Model\ResourceModel\Comment\CollectionFactory
     */
    protected $commentCollectionFactory;

    /**
     * @var \MageOS\Blog\Model\ResourceModel\Comment\Collection
     */
    protected $comments;

    /**
     * blog cache comment
     */
    const CACHE_TAG = 'rb_co';

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \MageOS\Blog\Model\PostFactory $postFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \MageOS\Blog\Model\ResourceModel\Comment\CollectionFactory $commentCollectionFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->postFactory = $postFactory;
        $this->customerFactory = $customerFactory;
        $this->commentCollectionFactory = $commentCollectionFactory;
    }

    /**
     * Retrieve identities
     *
     * @return array
     */
    public function getIdentities()
    {
        return [
            self::CACHE_TAG . '_' . $this->getId(),
            \MageOS\Blog\Model\Post::CACHE_TAG . '_' . $this->getPostId()
        ];
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\MageOS\Blog\Model\ResourceModel\Comment::class);
    }

    /**
     * Retrieve model title
     * @param  boolean $plural
     * @return string
     */
    public function getOwnTitle($plural = false)
    {
        return $plural ? 'Comments' : 'Comment';
    }

    /**
     * Retrieve true if post is active
     * @return boolean [description]
     */
    public function isActive()
    {
        return ($this->getStatus() == \MageOS\Blog\Model\Config\Source\CommentStatus::APPROVED);
    }

    /**
     * Retrieve post
     * @return \MageOS\Blog\Model\Post | false
     */
    public function getPost()
    {
        if (!$this->hasData('post')) {
            $this->setData('post', false);
            if ($postId = $this->getData('post_id')) {
                $post = $this->postFactory->create()->load($postId);
                if ($post->getId()) {
                    $this->setData('post', $post);
                }
            }
        }

        return $this->getData('post');
    }

    /**
     * Retrieve parent comment
     * @return self || false
     */
    public function getParentComment()
    {
        $k = 'parent_comment';
        if (null === $this->getData($k)) {
            $this->setData($k, false);
            if ($pId = $this->getParentId()) {
                $comment = clone $this;
                $comment->load($pId);
                if ($comment->getId()) {
                    $this->setData($k, $comment);
                }
            }
        }

        return $this->getData($k);
    }

    /**
     * Retrieve child comments
     * @return \MageOS\Blog\Model\ResourceModel\Comment\Collection
     */
    public function getChildComments()
    {
        if (null === $this->comments) {
            $this->comments = $this->commentCollectionFactory->create()
                ->addFieldToFilter('parent_id', $this->getId());
        }

        return $this->comments;
    }

    /**
     * Retrieve true if comment is reply to other comment
     * @return boolean
     */
    public function isReply()
    {
        return (bool)$this->getParentId();
    }

    /**
     * Validate comment
     * @return void
     */
    public function validate()
    {
        if (mb_strlen($this->getText()) < 3) {
            throw new \Exception('Comment text is too short.', 1);
        }
    }

    /**
     * Retrieve post publish date using format
     * @param string $format
     * @return string
     */
    public function getPublishDate(string $format = 'Y-m-d H:i:s'): string
    {
        return Config::getTranslatedDate(
            $format,
            $this->getData('creation_time')
        );
    }

    /**
     * @return array|ResourceModel\Comment\Collection
     */
    public function getRepliesCollection()
    {
        $repliesCollection = [];
        if (!$this->isReply()) {
            $cId = $this->getId();
            if (!isset($repliesCollection[$cId])) {
                $repliesCollection[$cId] = $this->getChildComments()
                    ->addActiveFilter()
                    /*->setPageSize($this->getNumberOfReplies())*/
                    //->setOrder('creation_time', 'DESC'); old sorting
                    ->setOrder('creation_time', 'ASC');
            }

            return $repliesCollection[$cId];
        } else {
            return [];
        }
    }

    /**
     * @deprecated use getDynamicData method in graphQL data provider
     * @param null $fields
     * @return array
     */
    public function getDynamicData($fields = null)
    {
        $data = $this->getData();

        if (is_array($fields) && array_key_exists('replies', $fields)) {
            $replies = [];
            foreach ($this->getRepliesCollection() as $reply) {
                $replies[] = $reply->getDynamicData(
                    isset($fields['replies']) ? $fields['replies'] : null
                );
            }
            $data['replies'] = $replies;
        }

        return $data;
    }

    /**
     * Retrieve author
     * @return \Magento\Framework\DataObject
     */
    public function getAuthor()
    {
        if (null === $this->author) {
            $this->author = new \Magento\Framework\DataObject;
            $this->author->setType(
                $this->getAuthorType()
            );

            $guestData = [
                'nickname' => $this->getAuthorNickname(),
                'email' => $this->getAuthorEmail(),
            ];

            switch ($this->getAuthorType()) {
                case \MageOS\Blog\Model\Config\Source\AuthorType::GUEST:
                    $this->author->setData($guestData);
                    break;
                case \MageOS\Blog\Model\Config\Source\AuthorType::CUSTOMER:
                    $customer = $this->customerFactory->create();
                    $customer->load($this->getCustomerId());
                    if ($customer->getId()) {
                        $this->author->setData([
                            'nickname' => $customer->getName(),
                            'email' => $this->getEmail(),
                            'customer' => $customer,
                        ]);
                    } else {
                        $this->author->setData($guestData);
                    }
                    break;
                case \MageOS\Blog\Model\Config\Source\AuthorType::ADMIN:

                        $this->author->setData([
                            'nickname' => 'Admin',
                            'email' => 'test@test.com'
                        ]);

                    break;
            }
        }

        return $this->author;
    }
}
