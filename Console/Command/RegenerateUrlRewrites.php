<?php

declare(strict_types=1);

namespace MageOS\Blog\Console\Command;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Console\Cli;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Model\Url\UrlRewriteBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Backfill for entities saved while store assignment wiped the pivot and no rewrite was written.
 */
class RegenerateUrlRewrites extends Command
{
    private const OPTION_ENTITY = 'entity';
    private const OPTION_DRY_RUN = 'dry-run';
    private const ENTITY_ALL = 'all';

    /**
     * Entity type => primary table, id column, store pivot table (null when the entity has none).
     *
     * @var array<string, array{table: string, id: string, pivot: string|null}>
     */
    private const ENTITIES = [
        'post' => [
            'table' => 'mageos_blog_post',
            'id' => 'post_id',
            'pivot' => 'mageos_blog_post_store',
        ],
        'category' => [
            'table' => 'mageos_blog_category',
            'id' => 'category_id',
            'pivot' => 'mageos_blog_category_store',
        ],
        'tag' => [
            'table' => 'mageos_blog_tag',
            'id' => 'tag_id',
            'pivot' => 'mageos_blog_tag_store',
        ],
        'author' => [
            'table' => 'mageos_blog_author',
            'id' => 'author_id',
            'pivot' => null,
        ],
    ];

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly UrlRewriteBuilder $urlRewriteBuilder,
        private readonly UrlPersistInterface $urlPersist,
        private readonly PostRepositoryInterface $postRepository,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly TagRepositoryInterface $tagRepository,
        private readonly AuthorRepositoryInterface $authorRepository,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('mageos:blog:url-rewrite:regenerate')
            ->setDescription('Regenerate blog URL rewrites and repair missing store assignments')
            ->addOption(
                self::OPTION_ENTITY,
                null,
                InputOption::VALUE_REQUIRED,
                'post, category, tag, author or all',
                self::ENTITY_ALL
            )
            ->addOption(
                self::OPTION_DRY_RUN,
                null,
                InputOption::VALUE_NONE,
                'Report what would change without writing'
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $requested = (string) $input->getOption(self::OPTION_ENTITY);
        if ($requested !== self::ENTITY_ALL && !isset(self::ENTITIES[$requested])) {
            $output->writeln(sprintf('<error>Unknown entity type "%s".</error>', OutputFormatter::escape($requested)));

            return Cli::RETURN_FAILURE;
        }

        $dryRun = (bool) $input->getOption(self::OPTION_DRY_RUN);
        $types = $requested === self::ENTITY_ALL ? array_keys(self::ENTITIES) : [$requested];
        $template = $dryRun
            ? '[dry run] %s: %d store assignment(s) to repair, %d rewrite(s) to write.'
            : '%s: %d store assignment(s) repaired, %d rewrite(s) written.';
        $failed = false;

        foreach ($types as $type) {
            $storeLinks = $this->repairStoreLinks($type, $dryRun);
            $result = $this->regenerate($type, $dryRun);

            $output->writeln(sprintf($template, $type, $storeLinks, $result['rewrites']));
            foreach ($result['errors'] as $error) {
                $output->writeln('<error>' . OutputFormatter::escape($error) . '</error>');
                $failed = true;
            }
        }

        return $failed ? Cli::RETURN_FAILURE : Cli::RETURN_SUCCESS;
    }

    /**
     * Entities with no pivot row at all are assigned to store 0, which means all store views.
     */
    private function repairStoreLinks(string $type, bool $dryRun): int
    {
        $pivot = self::ENTITIES[$type]['pivot'];
        if ($pivot === null) {
            return 0;
        }

        $idColumn = self::ENTITIES[$type]['id'];
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from(['e' => $this->resource->getTableName(self::ENTITIES[$type]['table'])], [$idColumn])
            ->joinLeft(
                ['s' => $this->resource->getTableName($pivot)],
                's.' . $idColumn . ' = e.' . $idColumn,
                []
            )
            ->where('s.' . $idColumn . ' IS NULL');

        $missing = array_map('intval', $connection->fetchCol($select));
        if ($missing === [] || $dryRun) {
            return \count($missing);
        }

        $connection->insertMultiple(
            $this->resource->getTableName($pivot),
            array_map(
                static fn (int $id): array => [$idColumn => $id, 'store_id' => 0],
                $missing
            )
        );

        return \count($missing);
    }

    /**
     * @return array{rewrites: int, errors: string[]}
     */
    private function regenerate(string $type, bool $dryRun): array
    {
        $rewrites = 0;
        $errors = [];

        foreach ($this->fetchIds($type) as $id) {
            try {
                $rows = $this->buildRows($type, $id);
                if (!$dryRun) {
                    $this->urlPersist->replace($rows);
                }
                $rewrites += \count($rows);
            } catch (\Exception $e) {
                $errors[] = sprintf('%s %d: %s', $type, $id, $e->getMessage());
            }
        }

        return ['rewrites' => $rewrites, 'errors' => $errors];
    }

    /**
     * @return int[]
     */
    private function fetchIds(string $type): array
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()->from(
            $this->resource->getTableName(self::ENTITIES[$type]['table']),
            [self::ENTITIES[$type]['id']]
        );

        return array_map('intval', $connection->fetchCol($select));
    }

    /**
     * @return UrlRewrite[]
     */
    private function buildRows(string $type, int $id): array
    {
        return match ($type) {
            'post' => $this->postRows($id),
            'category' => $this->categoryRows($id),
            'tag' => $this->tagRows($id),
            'author' => $this->authorRows($id),
            default => throw new \InvalidArgumentException('Unknown entity type: ' . $type),
        };
    }

    /**
     * @return UrlRewrite[]
     */
    private function postRows(int $id): array
    {
        $post = $this->postRepository->getById($id);

        return $this->urlRewriteBuilder->buildForPost($post, $post->getStoreIds());
    }

    /**
     * @return UrlRewrite[]
     */
    private function categoryRows(int $id): array
    {
        $category = $this->categoryRepository->getById($id);

        return $this->urlRewriteBuilder->buildForCategory($category, $category->getStoreIds());
    }

    /**
     * @return UrlRewrite[]
     */
    private function tagRows(int $id): array
    {
        $tag = $this->tagRepository->getById($id);

        return $this->urlRewriteBuilder->buildForTag($tag, $tag->getStoreIds());
    }

    /**
     * @return UrlRewrite[]
     */
    private function authorRows(int $id): array
    {
        return $this->urlRewriteBuilder->buildForAuthor($this->authorRepository->getById($id));
    }
}
