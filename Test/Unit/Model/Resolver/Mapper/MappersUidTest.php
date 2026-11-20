<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Resolver\Mapper;

use Magento\Framework\GraphQl\Query\Uid;
use MageOS\Blog\Api\Data\AuthorInterface;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\Data\TagInterface;
use MageOS\Blog\Model\BlogPostStatus;
use MageOS\Blog\Model\Resolver\Mapper\AuthorMapper;
use MageOS\Blog\Model\Resolver\Mapper\CategoryMapper;
use MageOS\Blog\Model\Resolver\Mapper\PostMapper;
use MageOS\Blog\Model\Resolver\Mapper\TagMapper;
use MageOS\Blog\Model\Resolver\UidCodec;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MappersUidTest extends TestCase
{
    #[Test]
    public function the_post_mapper_adds_a_uid_next_to_the_id(): void
    {
        $post = $this->createMock(PostInterface::class);
        $post->method('getPostId')->willReturn(42);
        $post->method('getStatus')->willReturn(BlogPostStatus::Published->value);
        $post->method('getStoreIds')->willReturn([]);
        $post->method('getCategoryIds')->willReturn([]);
        $post->method('getTagIds')->willReturn([]);

        $result = (new PostMapper($this->codec()))->toArray($post);

        self::assertSame(42, $result['id']);
        self::assertSame('NDI=', $result['uid']);
    }

    #[Test]
    public function the_category_mapper_adds_a_uid_next_to_the_id(): void
    {
        $category = $this->createMock(CategoryInterface::class);
        $category->method('getCategoryId')->willReturn(42);
        $category->method('getStoreIds')->willReturn([]);

        $result = (new CategoryMapper($this->codec()))->toArray($category);

        self::assertSame(42, $result['id']);
        self::assertSame('NDI=', $result['uid']);
    }

    #[Test]
    public function the_tag_mapper_adds_a_uid_next_to_the_id(): void
    {
        $tag = $this->createMock(TagInterface::class);
        $tag->method('getTagId')->willReturn(42);
        $tag->method('getStoreIds')->willReturn([]);

        $result = (new TagMapper($this->codec()))->toArray($tag);

        self::assertSame(42, $result['id']);
        self::assertSame('NDI=', $result['uid']);
    }

    #[Test]
    public function the_author_mapper_adds_a_uid_next_to_the_id(): void
    {
        $author = $this->createMock(AuthorInterface::class);
        $author->method('getAuthorId')->willReturn(42);

        $result = (new AuthorMapper($this->codec()))->toArray($author);

        self::assertSame(42, $result['id']);
        self::assertSame('NDI=', $result['uid']);
    }

    private function codec(): UidCodec
    {
        return new UidCodec(new Uid());
    }
}
