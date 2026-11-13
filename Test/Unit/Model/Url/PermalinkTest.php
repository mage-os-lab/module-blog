<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Url;

use MageOS\Blog\Model\Config;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PermalinkTest extends TestCase
{
    #[Test]
    public function unconfigured_paths_are_the_paths_the_module_always_had(): void
    {
        $permalink = PermalinkFixture::create();

        self::assertSame('blog', $permalink->getIndexPath());
        self::assertSame('blog/search', $permalink->getSearchPath());
        self::assertSame('blog/hello-world', $permalink->getPostPath('hello-world'));
        self::assertSame('blog/category/news', $permalink->getCategoryPath('news'));
        self::assertSame('blog/tag/magento', $permalink->getTagPath('magento'));
        self::assertSame('blog/author/jane-doe', $permalink->getAuthorPath('jane-doe'));
    }

    #[Test]
    public function segments_are_read_per_store_view(): void
    {
        $permalink = PermalinkFixture::create([], [
            2 => [
                Config::XML_PATH_PERMALINK_CATEGORY => 'categorie',
                Config::XML_PATH_PERMALINK_TAG => 'onderwerp',
                Config::XML_PATH_PERMALINK_AUTHOR => 'auteur',
            ],
        ]);

        self::assertSame('blog/category/news', $permalink->getCategoryPath('news', 1));
        self::assertSame('blog/categorie/nieuws', $permalink->getCategoryPath('nieuws', 2));
        self::assertSame('blog/onderwerp/magento', $permalink->getTagPath('magento', 2));
        self::assertSame('blog/auteur/jan', $permalink->getAuthorPath('jan', 2));
    }

    #[Test]
    public function post_paths_have_no_segment_to_configure(): void
    {
        $permalink = PermalinkFixture::create([Config::XML_PATH_PERMALINK_CATEGORY => 'categorie']);

        self::assertSame('blog/hello-world', $permalink->getPostPath('hello-world', 2));
    }

    #[Test]
    public function a_segment_is_lowercased_and_stripped_of_surrounding_slashes(): void
    {
        $permalink = PermalinkFixture::create([Config::XML_PATH_PERMALINK_TAG => ' /Onderwerp/ ']);

        self::assertSame('blog/onderwerp/magento', $permalink->getTagPath('magento'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unusableSegments(): array
    {
        return [
            'empty' => [''],
            'nested' => ['a/b'],
            'space' => ['my tag'],
            'dot' => ['tag.html'],
            'leading dash' => ['-tag'],
        ];
    }

    #[Test]
    #[DataProvider('unusableSegments')]
    public function an_unusable_segment_falls_back_to_the_default(string $value): void
    {
        $permalink = PermalinkFixture::create([Config::XML_PATH_PERMALINK_TAG => $value]);

        self::assertSame('blog/tag/magento', $permalink->getTagPath('magento'));
    }

    #[Test]
    public function segments_lists_every_page_type_word_of_the_store(): void
    {
        $permalink = PermalinkFixture::create([], [2 => [Config::XML_PATH_PERMALINK_AUTHOR => 'auteur']]);

        self::assertSame(['search', 'category', 'tag', 'author'], $permalink->getSegments(1));
        self::assertSame(['search', 'category', 'tag', 'auteur'], $permalink->getSegments(2));
    }
}
