<?php
declare(strict_types=1);

namespace MageOS\Blog\Cron;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Event\ManagerInterface;
use Psr\Log\LoggerInterface;
use MageOS\Blog\Model\ResourceModel\Post\CollectionFactory as PostCollectionFactory;
use MageOS\Blog\Model\Config;
use MageOS\Blog\Model\ResourceModel\Post\Collection as PostCollection;
use MageOS\Blog\Api\Data\BlogPostInterface;

class PublishScheduledPosts
{
    private Config $config;
    private PostCollectionFactory $postCollectionFactory;
    private DateTime $date;
    private ManagerInterface $eventManager;
    private LoggerInterface $logger;

    public function __construct(
        Config $config,
        PostCollectionFactory $postCollectionFactory,
        DateTime $date,
        ManagerInterface $eventManager,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->postCollectionFactory = $postCollectionFactory;
        $this->date = $date;
        $this->eventManager = $eventManager;
        $this->logger = $logger;
    }

    /**
     * Set Posts visible in frontend when full page cache is enabled
     * @return void
     */
    public function execute(): void
    {
        if (!$this->config->isEnabled()) {
            // Disable this cron job if the module is disabled
            return;
        }

        foreach ($this->getPostCollectionToProcess() as $post) {
            /**
             * @var BlogPostInterface $post
             */
            try {
                $this->processPost($post);
            } catch (\Exception $e) {
                $this->logger->error('Error while setting flag for post with ID ' . $post->getId());
                $this->logger->error($e->getMessage());
            }
        }
    }

    /**
     * Get Posts Collection To Process
     * Active = 1 (Published)
     * PublishTime is in the range:
     * PublishTime >= CurrentTime - 2 minutes
     * PublishTime <= CurrentTime
     * @return PostCollection
     */
    public function getPostCollectionToProcess(): PostCollection
    {
        return $this->postCollectionFactory->create()
            ->addActiveFilter()
            ->addFieldToFilter('publish_time', ['gteq' => $this->date->gmtDate('Y-m-d H:i:s', strtotime('-2 minutes'))])
            ->addFieldToFilter('publish_time', ['lteq' => $this->date->gmtDate()]);
    }

    /**
     * Resave Post
     * @param BlogPostInterface $post
     * @return void
     */
    public function processPost(BlogPostInterface $post): void
    {
        $post->setAllIdentifiersFlag(1);
        $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $post]);
    }
}
