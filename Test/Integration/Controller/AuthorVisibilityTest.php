<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Integration\Controller;

use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\TestCase\AbstractController;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\Data\AuthorInterfaceFactory;
use MageOS\Blog\Model\Config;

/**
 * The show_author flag must take the author archive off the storefront without
 * rendering the author's name anywhere in the 404 page.
 *
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class AuthorVisibilityTest extends AbstractController
{
    protected function setUp(): void
    {
        parent::setUp();

        $scopeConfig = $this->_objectManager->get(MutableScopeConfigInterface::class);
        $scopeConfig->setValue(Config::XML_PATH_ENABLED, '1', ScopeInterface::SCOPE_STORE);
    }

    public function test_author_page_renders_by_default(): void
    {
        $this->createAuthor('visible-author');

        $this->dispatch('/blog/author/visible-author');

        $this->assertEquals(200, $this->getResponse()->getHttpResponseCode());
        $this->assertStringContainsString('Visible Author Name', $this->getResponse()->getBody());
    }

    public function test_author_page_is_404_and_leaks_no_name_when_show_author_is_off(): void
    {
        $this->createAuthor('hidden-author');
        $scopeConfig = $this->_objectManager->get(MutableScopeConfigInterface::class);
        $scopeConfig->setValue(Config::XML_PATH_SHOW_AUTHOR, '0', ScopeInterface::SCOPE_STORE);

        $this->dispatch('/blog/author/hidden-author');

        $this->assertEquals(404, $this->getResponse()->getHttpResponseCode());
        $this->assertStringNotContainsString('Visible Author Name', $this->getResponse()->getBody());
    }

    private function createAuthor(string $slug): void
    {
        $author = $this->_objectManager->get(AuthorInterfaceFactory::class)->create();
        $author->setName('Visible Author Name')->setSlug($slug)->setIsActive(true);
        $this->_objectManager->get(AuthorRepositoryInterface::class)->save($author);
    }
}
