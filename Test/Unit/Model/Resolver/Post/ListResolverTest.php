<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Resolver\Post;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\Data\PostSearchResultsInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Model\Resolver\Hydrator\LinkHydrator;
use MageOS\Blog\Model\Resolver\Mapper\PostMapper;
use MageOS\Blog\Model\Resolver\Post\ListResolver;
use MageOS\Blog\Model\Resolver\ReadPolicy;
use MageOS\Blog\Model\Resolver\RelationFilterGuard;
use MageOS\Blog\Model\Resolver\SearchCriteria\FilterBuilder;
use MageOS\Blog\Model\Resolver\SearchCriteria\PageArguments;
use MageOS\Blog\Test\Unit\Model\Resolver\ResolverTestTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ListResolverTest extends TestCase
{
    use ResolverTestTrait;

    #[Test]
    public function the_read_policy_filters_are_required_on_the_criteria(): void
    {
        $context = $this->context();
        $policy = $this->createMock(ReadPolicy::class);
        $policy->method('postListFilters')->with($context)->willReturn(['status' => 2, 'store_ids' => 1]);

        $criteria = $this->createMock(SearchCriteriaInterface::class);
        $filterBuilder = $this->createMock(FilterBuilder::class);
        $filterBuilder->expects(self::once())
            ->method('build')
            ->with(['status' => ['eq' => '0']], null, 20, 1, self::isType('array'), ['status' => 2, 'store_ids' => 1])
            ->willReturn($criteria);

        $repository = $this->createMock(PostRepositoryInterface::class);
        $repository->expects(self::once())->method('getList')->with($criteria)->willReturn($this->results([], 0));

        $resolver = new ListResolver($repository, $filterBuilder, $this->createMock(PostMapper::class), $policy, new PageArguments(), $this->createMock(LinkHydrator::class), $this->normalizer(), $this->openGuard());
        $resolver->resolve($this->field(), $context, $this->info(), null, ['filter' => ['status' => ['eq' => '0']]]);
    }

    #[Test]
    public function it_maps_the_items_and_reports_the_pagination(): void
    {
        $post = $this->createMock(PostInterface::class);
        $mapper = $this->createMock(PostMapper::class);
        $mapper->method('toArray')->with($post)->willReturn(['id' => 5]);
        $filterBuilder = $this->createMock(FilterBuilder::class);
        $filterBuilder->method('build')->willReturn($this->createMock(SearchCriteriaInterface::class));
        $repository = $this->createMock(PostRepositoryInterface::class);
        $repository->method('getList')->willReturn($this->results([$post], 45));

        $result = (new ListResolver($repository, $filterBuilder, $mapper, $this->createMock(ReadPolicy::class), new PageArguments(), $this->createMock(LinkHydrator::class), $this->normalizer(), $this->openGuard()))
            ->resolve($this->field(), $this->context(), $this->info(), null, ['pageSize' => 20, 'currentPage' => 2]);

        self::assertSame(
            [
                'items' => [['id' => 5]],
                'page_info' => ['page_size' => 20, 'current_page' => 2, 'total_pages' => 3],
                'total_count' => 45,
            ],
            $result
        );
    }

    #[Test]
    public function the_listed_posts_are_hydrated_and_the_page_size_is_capped(): void
    {
        $post = $this->createMock(PostInterface::class);
        $mapper = $this->createMock(PostMapper::class);
        $mapper->method('toArray')->willReturn(['id' => 5]);
        $filterBuilder = $this->createMock(FilterBuilder::class);
        $filterBuilder->expects(self::once())->method('build')
            ->with(null, null, 100, 1, self::isType('array'), [])
            ->willReturn($this->createMock(SearchCriteriaInterface::class));
        $repository = $this->createMock(PostRepositoryInterface::class);
        $repository->method('getList')->willReturn($this->results([$post], 250));
        $hydrator = $this->createMock(LinkHydrator::class);
        $hydrator->expects(self::once())->method('hydratePosts')->with([$post]);

        $result = (new ListResolver($repository, $filterBuilder, $mapper, $this->createMock(ReadPolicy::class), new PageArguments(), $hydrator, $this->normalizer(), $this->openGuard()))
            ->resolve($this->field(), $this->context(), $this->info(), null, ['pageSize' => 5000]);

        self::assertSame(['page_size' => 100, 'current_page' => 1, 'total_pages' => 3], $result['page_info']);
    }

    #[Test]
    public function a_page_size_of_zero_is_rejected_before_anything_is_queried(): void
    {
        $repository = $this->createMock(PostRepositoryInterface::class);
        $repository->expects(self::never())->method('getList');
        $hydrator = $this->createMock(LinkHydrator::class);
        $hydrator->expects(self::never())->method('hydratePosts');

        $this->expectException(GraphQlInputException::class);

        (new ListResolver($repository, $this->createMock(FilterBuilder::class), $this->createMock(PostMapper::class), $this->createMock(ReadPolicy::class), new PageArguments(), $hydrator, $this->normalizer(), $this->openGuard()))
            ->resolve($this->field(), $this->context(), $this->info(), null, ['pageSize' => 0]);
    }

    #[Test]
    public function relation_and_store_filters_map_to_the_repository_filters_that_exist(): void
    {
        $captured = null;
        $resolver = $this->resolverCapturing($captured, $this->openGuard());

        $resolver->resolve($this->field(), $this->context(), $this->info(), null, ['filter' => ['id' => ['eq' => '1']]]);

        self::assertSame('category_ids', $captured['category_id']);
        self::assertSame('tag_ids', $captured['tag_id']);
        self::assertSame('store_ids', $captured['store_id']);
        self::assertSame('author_id', $captured['author_id']);
    }

    #[Test]
    public function the_unsuffixed_keys_reach_the_filter_builder_as_the_legacy_keys(): void
    {
        $filter = null;
        $resolver = $this->resolverCapturing($map, $this->openGuard(), $filter);

        $resolver->resolve($this->field(), $this->context(), $this->info(), null, ['filter' => [
            'author' => ['eq' => '1'],
            'category' => ['eq' => '2'],
            'tag' => ['in' => ['3']],
            'store' => ['eq' => '4'],
        ]]);

        self::assertSame(
            ['author_id' => ['eq' => '1'], 'category_id' => ['eq' => '2'], 'tag_id' => ['in' => ['3']], 'store_id' => ['eq' => '4']],
            $filter
        );
    }

    #[Test]
    public function sending_both_spellings_is_rejected_before_anything_is_read(): void
    {
        $repository = $this->createMock(PostRepositoryInterface::class);
        $repository->expects(self::never())->method('getList');
        $resolver = new ListResolver(
            $repository,
            $this->createMock(FilterBuilder::class),
            $this->createMock(PostMapper::class),
            $this->createMock(ReadPolicy::class),
            new PageArguments(),
            $this->createMock(LinkHydrator::class),
            $this->normalizer(),
            $this->openGuard(),
        );

        $this->expectException(GraphQlInputException::class);
        $resolver->resolve($this->field(), $this->context(), $this->info(), null, ['filter' => [
            'tag' => ['eq' => '1'],
            'tag_id' => ['eq' => '1'],
        ]]);
    }

    #[Test]
    public function the_filter_goes_through_the_relation_guard_with_the_caller_context(): void
    {
        $context = $this->context();
        $guard = $this->createMock(RelationFilterGuard::class);
        $guard->expects(self::once())->method('restrict')
            ->with(
                $context,
                ['category_id' => ['eq' => '5']],
                ['author_id' => 'author', 'category_id' => 'category', 'tag_id' => 'tag']
            )
            ->willReturn(['category_id' => ['eq' => -1]]);
        $filter = null;
        $resolver = $this->resolverCapturing($map, $guard, $filter);

        $resolver->resolve($this->field(), $context, $this->info(), null, ['filter' => ['category' => ['eq' => '5']]]);

        self::assertSame(['category_id' => ['eq' => -1]], $filter);
    }

    /**
     * @param array<string, string>|null $map
     * @param array<string, mixed>|null $filter
     */
    private function resolverCapturing(?array &$map, RelationFilterGuard $guard, ?array &$filter = null): ListResolver
    {
        $filterBuilder = $this->createMock(FilterBuilder::class);
        $filterBuilder->method('build')->willReturnCallback(
            function ($f, $s, $p, $c, $fieldMap) use (&$map, &$filter): SearchCriteriaInterface {
                $filter = $f;
                $map = $fieldMap;

                return $this->createMock(SearchCriteriaInterface::class);
            }
        );
        $repository = $this->createMock(PostRepositoryInterface::class);
        $repository->method('getList')->willReturn($this->results([], 0));

        return new ListResolver(
            $repository,
            $filterBuilder,
            $this->createMock(PostMapper::class),
            $this->createMock(ReadPolicy::class),
            new PageArguments(),
            $this->createMock(LinkHydrator::class),
            $this->normalizer(),
            $guard,
        );
    }

    /**
     * @param PostInterface[] $items
     */
    private function results(array $items, int $total): PostSearchResultsInterface
    {
        $results = $this->createMock(PostSearchResultsInterface::class);
        $results->method('getItems')->willReturn($items);
        $results->method('getTotalCount')->willReturn($total);

        return $results;
    }
}
