<?php
declare(strict_types=1);

namespace MageOS\Blog\Test\Integration\Model;

use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\Data\AuthorInterface;
use MageOS\Blog\Api\Data\AuthorInterfaceFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

final class AuthorRepositoryTest extends TestCase
{
    private AuthorRepositoryInterface $repository;
    private AuthorInterfaceFactory $authorFactory;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private ResourceConnection $resource;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->repository = $objectManager->get(AuthorRepositoryInterface::class);
        $this->authorFactory = $objectManager->get(AuthorInterfaceFactory::class);
        $this->searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);
        $this->resource = $objectManager->get(ResourceConnection::class);
    }

    public function test_save_and_load_roundtrip(): void
    {
        $author = $this->authorFactory->create();
        $author->setName('Jane Doe')
            ->setSlug('jane-doe')
            ->setBio('Writer and editor')
            ->setAvatar('mageos_blog/avatar/jane.jpg')
            ->setEmail('jane@example.com')
            ->setTwitter('@janedoe')
            ->setLinkedin('jane-doe')
            ->setWebsite('https://janedoe.example.com')
            ->setIsActive(true);

        $saved = $this->repository->save($author);
        self::assertNotNull($saved->getAuthorId());

        $loaded = $this->repository->getById((int) $saved->getAuthorId());
        self::assertSame('Jane Doe', $loaded->getName());
        self::assertSame('jane-doe', $loaded->getSlug());
        self::assertSame('Writer and editor', $loaded->getBio());
        self::assertSame('mageos_blog/avatar/jane.jpg', $loaded->getAvatar());
        self::assertSame('jane@example.com', $loaded->getEmail());
        self::assertSame('@janedoe', $loaded->getTwitter());
        self::assertSame('jane-doe', $loaded->getLinkedin());
        self::assertSame('https://janedoe.example.com', $loaded->getWebsite());
        self::assertTrue($loaded->getIsActive());
    }

    public function test_get_by_slug_returns_author(): void
    {
        $author = $this->authorFactory->create();
        $author->setName('John Smith')->setSlug('john-smith');
        $this->repository->save($author);

        $found = $this->repository->getBySlug('john-smith');
        self::assertSame('John Smith', $found->getName());
    }

    public function test_get_by_slug_throws_on_missing(): void
    {
        $this->expectException(NoSuchEntityException::class);
        $this->repository->getBySlug('definitely-not-a-slug');
    }

    public function test_delete_removes_row(): void
    {
        $author = $this->authorFactory->create();
        $author->setName('Gone')->setSlug('gone-author');
        $saved = $this->repository->save($author);
        $authorId = (int) $saved->getAuthorId();

        self::assertTrue($this->repository->deleteById($authorId));

        $connection = $this->resource->getConnection();
        $count = $connection->fetchOne(
            $connection->select()
                ->from($this->resource->getTableName('mageos_blog_author'), ['COUNT(*)'])
                ->where('author_id = ?', $authorId)
        );
        self::assertSame(0, (int) $count);
    }

    public function test_get_list_filters_by_slug(): void
    {
        foreach (['alpha', 'beta', 'gamma'] as $slug) {
            $author = $this->authorFactory->create();
            $author->setName(ucfirst($slug))->setSlug('author-list-' . $slug);
            $this->repository->save($author);
        }

        $criteria = $this->searchCriteriaBuilder
            ->addFilter(AuthorInterface::SLUG, 'author-list-%', 'like')
            ->create();

        $results = $this->repository->getList($criteria);
        self::assertGreaterThanOrEqual(3, $results->getTotalCount());
    }

    public function test_get_by_id_throws_on_missing(): void
    {
        $this->expectException(NoSuchEntityException::class);
        $this->repository->getById(9999999);
    }
}
