<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Post\PostList\Toolbar;

use Magento\Theme\Block\Html\Pager as HtmlPager;
use MageOS\Blog\Model\Config;

class Pager extends HtmlPager
{
    /**
     * Retrieve url of all pages
     *
     * @return array
     */
    public function getPagesUrls(): array
    {
        $urls = [];
        for ($page = $this->getCurrentPage() + 1; $page <= $this->getLastPageNum(); $page++) {
            $urls[$page] = $this->getPageUrl($page);
        }

        return $urls;
    }

    /**
     * Retrieve true olny if can use lazyload
     *
     * @return bool
     */
    public function useLazyload(): bool
    {
        $lastPage = $this->getLastPageNum();
        $currentPage = $this->getCurrentPage();

        return $this->getCollection()->getSize()
            && $lastPage > 1
            && $currentPage < $lastPage;
    }

    /**
     * Retrieve lazyload json config string
     * @param array $config
     *
     * @return string
     */
    public function getLazyloadConfig(array $config = []): string
    {
        $config = array_merge([
            'page_url' => $this->getPagesUrls(),
            'current_page' => $this->getCurrentPage(),
            'last_page' => $this->getLastPageNum(),
            'padding' => 200,
            'list_wrapper' => $this->getListWrapper(),
            'auto_trigger' => false,
        ], $config);

        return json_encode($config);
    }

    /**
     * Retrieve page URL by defined parameters
     *
     * @param array $params
     *
     * @return string
     */
    public function getPagerUrl($params = []): string
    {
        $urlParams = [];
        $urlParams['_current'] = true;
        $urlParams['_escape'] = true;
        $urlParams['_use_rewrite'] = true;
        $urlParams['_fragment'] = $this->getFragment();
        $urlParams['_query'] = $params;

        $pageNumber = $params['page'] ?? ($params['p'] ?? null);

        unset($urlParams['_current']);
        unset($urlParams['_query']);
        unset($urlParams['_fragment']);
        unset($urlParams['_escape']);

        $page = '';
        if ($pageNumber) {
            $page = '/page/' . $params['page'];
        }
        $url = $this->getUrl($this->getPath(), $urlParams);
        if ($parsed = explode('/', parse_url($url)['path'])) {
            $key = array_search('page', $parsed);
            if ($key && isset($parsed[$key + 1]) && intval($parsed[$key + 1])) {
                $url = str_replace('/page/' . $parsed[$key + 1], $page, $url);
            } else {
                $url = $url . $page;
            }
        }

        return $url;
    }
}
