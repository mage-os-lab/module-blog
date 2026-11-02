<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\Mapper;

use MageOS\Blog\Api\Data\AuthorInterface;
use MageOS\Blog\Model\Resolver\UidCodec;

class AuthorMapper
{
    public function __construct(private readonly UidCodec $uidCodec)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(AuthorInterface $author): array
    {
        return [
            'id' => $author->getAuthorId(),
            'uid' => $this->uidCodec->encode((int) $author->getAuthorId()),
            'slug' => $author->getSlug(),
            'name' => $author->getName(),
            'bio' => $author->getBio(),
            'avatar' => $author->getAvatar(),
            'email' => $author->getEmail(),
            'twitter' => $author->getTwitter(),
            'linkedin' => $author->getLinkedin(),
            'website' => $author->getWebsite(),
            'is_active' => $author->getIsActive(),
        ];
    }
}
