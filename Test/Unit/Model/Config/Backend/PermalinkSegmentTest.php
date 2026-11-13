<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Route\ConfigInterface as RouteConfig;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use MageOS\Blog\Model\Config\Backend\PermalinkSegment;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PermalinkSegmentTest extends TestCase
{
    #[Test]
    public function a_segment_is_saved_lowercased_and_without_surrounding_slashes(): void
    {
        $model = $this->model('tag', ' /Onderwerp/ ');

        $model->beforeSave();

        self::assertSame('onderwerp', $model->getValue());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidSegments(): array
    {
        return [
            'empty' => [''],
            'nested' => ['blog/tag'],
            'space' => ['my tag'],
            'dot' => ['tag.html'],
            'trailing dash' => ['tag-'],
        ];
    }

    #[Test]
    #[DataProvider('invalidSegments')]
    public function an_unservable_segment_is_refused(string $value): void
    {
        $this->expectException(LocalizedException::class);

        $this->model('tag', $value)->beforeSave();
    }

    #[Test]
    public function a_segment_cannot_take_the_search_segment(): void
    {
        $this->expectException(LocalizedException::class);

        $this->model('author', 'search')->beforeSave();
    }

    #[Test]
    public function a_segment_cannot_equal_another_segment_saved_with_it(): void
    {
        $this->expectException(LocalizedException::class);

        $this->model('tag', 'onderwerp', ['category' => 'Onderwerp', 'author' => 'auteur'])->beforeSave();
    }

    #[Test]
    public function an_inherited_sibling_is_compared_by_the_value_it_inherits(): void
    {
        $this->expectException(LocalizedException::class);

        $this->model('tag', 'categorie', ['author' => 'auteur'], ['mageos_blog/permalink/category' => 'categorie'])
            ->beforeSave();
    }

    #[Test]
    public function a_segment_that_differs_from_its_siblings_is_saved(): void
    {
        $model = $this->model('category', 'categorie', ['tag' => 'tag', 'author' => 'auteur']);

        $model->beforeSave();

        self::assertSame('categorie', $model->getValue());
    }

    #[Test]
    public function the_route_cannot_take_the_front_name_of_another_module(): void
    {
        $this->expectException(LocalizedException::class);

        $this->model('route', 'checkout')->beforeSave();
    }

    #[Test]
    public function the_route_can_be_the_blog_front_name_itself(): void
    {
        $model = $this->model('route', 'Blog');

        $model->beforeSave();

        self::assertSame('blog', $model->getValue());
    }

    #[Test]
    public function the_route_may_repeat_a_segment_because_it_sits_a_level_above_them(): void
    {
        $model = $this->model('route', 'nieuws', ['category' => 'nieuws']);

        $model->beforeSave();

        self::assertSame('nieuws', $model->getValue());
    }

    #[Test]
    public function the_search_segment_cannot_equal_a_page_type_segment(): void
    {
        $this->expectException(LocalizedException::class);

        $this->model('search', 'zoeken', ['tag' => 'zoeken'])->beforeSave();
    }

    /**
     * @param array<string, string> $fieldset values of the other fields posted with this one
     * @param array<string, string> $config values the scope config returns for inherited fields
     */
    private function model(string $field, string $value, array $fieldset = [], array $config = []): PermalinkSegment
    {
        $routeConfig = $this->createStub(RouteConfig::class);
        $routeConfig->method('getRouteByFrontName')->willReturnCallback(
            static fn (string $frontName): string|false => match ($frontName) {
                'blog' => 'blog',
                'checkout' => 'checkout',
                default => false,
            }
        );
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(
            static fn (string $path): ?string => $config[$path] ?? null
        );

        $context = $this->createStub(Context::class);
        $context->method('getEventDispatcher')->willReturn($this->createStub(EventManagerInterface::class));

        $model = new PermalinkSegment(
            $context,
            $this->createStub(Registry::class),
            $scopeConfig,
            $this->createStub(TypeListInterface::class),
            $routeConfig,
        );
        $model->setField($field);
        $model->setValue($value);
        $model->setScope('stores');
        $model->setScopeCode('2');
        $model->setData('fieldset_data', [$field => $value] + $fieldset);

        return $model;
    }
}
