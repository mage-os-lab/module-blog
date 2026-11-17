<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Ui;

use MageOS\Blog\Controller\Adminhtml\Author\Edit as AuthorEdit;
use MageOS\Blog\Controller\Adminhtml\Category\Edit as CategoryEdit;
use MageOS\Blog\Controller\Adminhtml\Post\Edit as PostEdit;
use MageOS\Blog\Controller\Adminhtml\Tag\Edit as TagEdit;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;

/**
 * Magento's mui/index/render only enforces ACL when the data source carries an
 * <aclResource>; without it any admin can load any entity's form data.
 */
final class FormAclResourceTest extends TestCase
{
    #[Test]
    #[DataProvider('formCases')]
    public function form_data_source_requires_the_same_acl_resource_as_the_edit_controller(
        string $form,
        string $expectedResource
    ): void {
        $xml = simplexml_load_file(
            __DIR__ . '/../../../view/adminhtml/ui_component/' . $form . '.xml'
        );
        self::assertInstanceOf(SimpleXMLElement::class, $xml);

        $resources = $xml->xpath('/form/dataSource/aclResource');

        self::assertIsArray($resources);
        self::assertCount(1, $resources);
        self::assertSame($expectedResource, trim((string) $resources[0]));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function formCases(): array
    {
        return [
            'post'     => ['mageos_blog_post_form', PostEdit::ADMIN_RESOURCE],
            'category' => ['mageos_blog_category_form', CategoryEdit::ADMIN_RESOURCE],
            'tag'      => ['mageos_blog_tag_form', TagEdit::ADMIN_RESOURCE],
            'author'   => ['mageos_blog_author_form', AuthorEdit::ADMIN_RESOURCE],
        ];
    }
}
