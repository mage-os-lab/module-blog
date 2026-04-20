<?php

declare(strict_types=1);

namespace MageOS\Blog\Controller\Rss;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use MageOS\Blog\Model\Config;
use MageOS\Blog\Model\Rss\BlogFeed;

class Index implements HttpGetActionInterface
{
    public function __construct(
        private readonly ResultFactory $resultFactory,
        private readonly Config $config,
        private readonly BlogFeed $feed
    ) {
    }

    public function execute(): ResultInterface
    {
        if (!$this->config->isEnabled() || !$this->config->isRssEnabled()) {
            /** @var Forward $forward */
            $forward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
            return $forward->forward('noroute');
        }

        $data = $this->feed->getRssData();
        $xml = $this->renderRssXml($data);

        /** @var Raw $raw */
        $raw = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $raw->setHeader('Content-Type', 'application/rss+xml; charset=utf-8');
        $raw->setContents($xml);
        return $raw;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderRssXml(array $data): string
    {
        $xml = new \DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        $rss = $xml->createElement('rss');
        $rss->setAttribute('version', '2.0');
        $rss->setAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
        $xml->appendChild($rss);

        $channel = $xml->createElement('channel');
        $rss->appendChild($channel);

        $channel->appendChild($xml->createElement(
            'title',
            htmlspecialchars((string) ($data['title'] ?? ''), ENT_XML1)
        ));
        $channel->appendChild($xml->createElement(
            'description',
            htmlspecialchars((string) ($data['description'] ?? ''), ENT_XML1)
        ));
        $channel->appendChild($xml->createElement('link', (string) ($data['link'] ?? '')));

        foreach ((array) ($data['entries'] ?? []) as $entry) {
            $item = $xml->createElement('item');
            $item->appendChild($xml->createElement(
                'title',
                htmlspecialchars((string) ($entry['title'] ?? ''), ENT_XML1)
            ));
            $item->appendChild($xml->createElement('link', (string) ($entry['link'] ?? '')));

            $descNode = $xml->createElement('description');
            $cdata = $xml->createCDATASection((string) ($entry['description'] ?? ''));
            $descNode->appendChild($cdata);
            $item->appendChild($descNode);

            if (!empty($entry['pubDate'])) {
                $item->appendChild($xml->createElement('pubDate', (string) $entry['pubDate']));
            }

            $item->appendChild($xml->createElement('guid', (string) ($entry['link'] ?? '')));
            $channel->appendChild($item);
        }

        return $xml->saveXML() ?: '';
    }
}
