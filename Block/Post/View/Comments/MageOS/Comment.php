<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Post\View\Comments\MageOS;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use MageOS\Blog\Model\Config;

/**
 * MageOS comment block
 *
 * @method string getComment()
 * @method $this setComment(\MageOS\Blog\Model\Comment $comment)
 */
class Comment extends Template implements IdentityInterface
{
    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * Comment constructor.
     * @param Template\Context $context
     * @param TimezoneInterface $timezone
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        array $data = [],
        TimezoneInterface $timezone = null
    ) {
        $this->timezone = $timezone ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(TimezoneInterface::class);
        parent::__construct($context, $data);
    }

    /**
     * @var array
     */
    protected $repliesCollection = [];

    /**
     * Template file
     * @var string
     */
    protected $_template = 'MageOS_Blog::post/view/comments/mageos/comment.phtml';

    /**
     * Retrieve identities
     *
     * @return string
     */
    public function getIdentities()
    {
        return $this->getComment()->getIdentities();
    }

    /**
     * Retrieve sub-comments collection or empty array
     *
     * @return \MageOS\Blog\Model\ResourceModel\Comment\Collection | array
     */
    public function getRepliesCollection()
    {
        $comment = $this->getComment();
        if (!$comment->isReply()) {
            $cId = $comment->getId();
            if (!isset($this->repliesCollection[$cId])) {
                $this->repliesCollection[$cId] = $this->getComment()->getChildComments()
                    ->addActiveFilter()
                    /*->setPageSize($this->getNumberOfReplies())*/
                    //->setOrder('creation_time', 'DESC'); old sorting
                      ->setOrder('creation_time', 'ASC');
            }

            return $this->repliesCollection[$cId];
        } else {
            return [];
        }
    }

    /**
     * Retrieve number of replies to display
     *
     * @return int
     */
    public function getNumberOfReplies(): int
    {
        return Config::MAX_NUMBER_OF_REPLIES;
    }

    /**
     * @return mixed
     */
    public function getPublishDate()
    {
        $dateFormat = $this->_scopeConfig->getValue(
            Config::XML_PATH_COMMENTS_FORMAT_DATE,
            Config::SCOPE_STORE
        );

        $gmtDate = $this->getComment()->getPublishDate();
        $gmtTime = strtotime((string)$gmtDate);

        $localTime = strtotime(
            (string)$this->timezone->date($gmtTime)->format('Y-m-d H:i:s')
        );

        return Config::getTranslatedDate(
            $dateFormat,
            $localTime
        );
    }
}
