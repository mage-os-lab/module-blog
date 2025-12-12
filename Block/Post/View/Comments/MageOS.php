<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Post\View\Comments;

use Magento\Store\Model\ScopeInterface;
use MageOS\Blog\Model\Config;

/**
 * Blog post MageOS comments block
 */
class MageOS extends \MageOS\Blog\Block\Post\View\Comments implements \Magento\Framework\DataObject\IdentityInterface
{

    /**
     * @var \MageOS\Blog\Model\ResourceModel\Comment\Collection
     */
    protected $commentCollection;

    /**
     * @var string
     */
    protected $defaultCommentBlock = \MageOS\Blog\Block\Post\View\Comments\MageOS\Comment::class;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Customer\Model\Url
     */
    protected $customerUrl;

    /**
     * Constructor
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry                      $coreRegistry
     * @param \Magento\Framework\Locale\ResolverInterface      $localeResolver
     * @param \Magento\Customer\Model\Session                  $customerSession
     * @param \Magento\Customer\Model\Url                      $customerUrl
     * @param array                                            $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\Url $customerUrl,
        array $data = []
    ) {
        parent::__construct($context, $coreRegistry, $localeResolver, $data);
        $this->customerSession = $customerSession;
        $this->customerUrl = $customerUrl;
    }

    /**
     * Retrieve comment block
     *
     * @return \MageOS\Blog\Block\Post\View\Comments\MageOS\Comment
     */
    public function getCommentBlock()
    {
        $k = 'comment_block';
        if (!$this->hasData($k)) {
            $blockName = $this->getCommentBlockName();
            if ($blockName) {
                $block = $this->getLayout()->getBlock($blockName);
            }

            if (empty($block)) {
                $block = $this->getLayout()->createBlock($this->defaultCommentBlock, uniqid(microtime()));
            }

            $this->setData($k, $block);
        }

        return $this->getData($k);
    }

    /**
     * Retrieve comment html
     *
     * @return string
     */
    public function getCommentHtml(\MageOS\Blog\Model\Comment $comment)
    {
        return $this->getCommentBlock()
            ->setComment($comment)
            ->toHtml();
    }

    /**
     * Prepare posts collection
     *
     * @return void
     */
    protected function prepareCommentCollection()
    {

        $this->commentCollection = $this->getPost()->getComments()
            ->addActiveFilter()
            ->addFieldToFilter('parent_id', 0)
            /*->setPageSize($this->getNumberOfComments())*/
            ->setOrder('creation_time', 'DESC');
    }

    /**
     * Prepare posts collection
     *
     * @return \MageOS\Blog\Model\ResourceModel\Comment\Collection
     */
    public function getCommentsCollection()
    {
        if (null === $this->commentCollection) {
            $this->prepareCommentCollection();
        }

        return $this->commentCollection;
    }

    /**
     * Retrieve customer session
     * @return \Magento\Customer\Model\Session
     */
    public function getCustomerSession()
    {
        return $this->customerSession;
    }

    /**
     * Retrieve customer url model
     * @return \Magento\Customer\Model\Url
     */
    public function getCustomerUrl()
    {
        return $this->customerUrl;
    }

    /**
     * Retrieve true if customer can post new comment or reply
     *
     * @return string
     */
    public function canPost()
    {
        return $this->_scopeConfig->getValue(
            Config::XML_PATH_COMMENTS_GUEST_COMMENTS,
            Config::SCOPE_STORE
        ) || $this->getCustomerSession()->getCustomerGroupId();
    }

    /**
     * Retrieve number of comments to display
     *
     * @return int
     */
    public function getNumberOfComments(): int
    {
        return Config::MAX_NUMBER_OF_COMMENTS;

    }

    /**
     * Retrieve form url
     * @return string
     */
    public function getFormUrl()
    {
        return $this->getUrl('blog/comment/post');
    }

    /**
     * Retrieve identities
     *
     * @return string
     */
    public function getIdentities()
    {
        $identities = [];
        foreach ($this->getCommentsCollection() as $item) {
            $identities = array_merge($identities, $item->getIdentities());
        }

        return array_unique($identities);
    }
}
