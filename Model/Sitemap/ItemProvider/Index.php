<?php
declare(strict_types=1);

namespace MageOS\Blog\Model\Sitemap\ItemProvider;

use MageOS\Blog\Model\Config;
use MageOS\Blog\Model\Url;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sitemap\Model\ItemProvider\ItemProviderInterface;
use Magento\Sitemap\Model\SitemapItemInterfaceFactory;
use Magento\Store\Model\ScopeInterface;
use MageOS\Blog\Api\SitemapConfigInterface;

class Index implements ItemProviderInterface
{
    /**
     * Sitemap config
     *
     * @var SitemapConfigInterface
     */
    private $sitemapConfig;

    /**
     * Sitemap item factory
     *
     * @var SitemapItemInterfaceFactory
     */
    private $itemFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Url
     */
    private $blogUrl;

    /**
     * @param SitemapConfigInterface $sitemapConfig
     * @param SitemapItemInterfaceFactory $itemFactory
     */
    public function __construct(
        SitemapConfigInterface $sitemapConfig,
        SitemapItemInterfaceFactory $itemFactory,
        ScopeConfigInterface $scopeConfig,
        Url $blogUrl
    ) {
        $this->sitemapConfig = $sitemapConfig;
        $this->itemFactory = $itemFactory;
        $this->scopeConfig = $scopeConfig;
        $this->blogUrl = $blogUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function getItems($storeId)
    {
        if (!$this->sitemapConfig->isEnabledSitemap(SitemapConfigInterface::HOME_PAGE, $storeId)) {
            return [];
        }

        $url = $this->blogUrl->getBasePath();

        $redirectToNoSlash = $this->scopeConfig->getValue(
            Config::XML_PATH_PERMALINK_REDIRECT_TO_NO_SLASH,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if (!$redirectToNoSlash) {
            $url = trim($url, '/') . '/';
        }

        $items[] = $this->itemFactory->create([
            'url' => $url,
            'priority' => $this->sitemapConfig->getPriority(SitemapConfigInterface::HOME_PAGE, $storeId),
            'changeFrequency' => $this->sitemapConfig->getFrequency(SitemapConfigInterface::POSTS_PAGE, $storeId)
        ]);

        return $items;
    }
}
