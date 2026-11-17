<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Console\Command;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Console\Command\RegenerateUrlRewrites;
use MageOS\Blog\Model\Url\UrlRewriteBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

final class RegenerateUrlRewritesTest extends TestCase
{
    #[Test]
    public function an_unknown_entity_name_is_echoed_literally_not_as_console_markup(): void
    {
        $output = $this->runCommand(['--entity' => '<info>boom</info>'], $this->createStub(AuthorRepositoryInterface::class));

        self::assertStringContainsString('Unknown entity type "<info>boom</info>".', $output);
    }

    #[Test]
    public function an_exception_message_is_echoed_literally_not_as_console_markup(): void
    {
        $authors = $this->createStub(AuthorRepositoryInterface::class);
        $authors->method('getById')->willThrowException(new NoSuchEntityException(__('<info>Mallory</info> is missing')));

        $output = $this->runCommand(['--entity' => 'author'], $authors);

        self::assertStringContainsString('author 7: <info>Mallory</info> is missing', $output);
    }

    /**
     * @param array<string, string> $options
     */
    private function runCommand(array $options, AuthorRepositoryInterface $authors): string
    {
        $select = $this->createStub(Select::class);
        $select->method('from')->willReturnSelf();
        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->method('fetchCol')->willReturn(['7']);
        $resource = $this->createStub(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTableName')->willReturnArgument(0);

        $command = new RegenerateUrlRewrites(
            $resource,
            $this->createStub(UrlRewriteBuilder::class),
            $this->createStub(UrlPersistInterface::class),
            $this->createStub(PostRepositoryInterface::class),
            $this->createStub(CategoryRepositoryInterface::class),
            $this->createStub(TagRepositoryInterface::class),
            $authors
        );
        $output = new BufferedOutput();
        $command->run(new ArrayInput($options), $output);

        return $output->fetch();
    }
}
