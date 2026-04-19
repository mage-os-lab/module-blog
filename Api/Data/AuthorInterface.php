<?php

declare(strict_types=1);

namespace MageOS\Blog\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface AuthorInterface extends ExtensibleDataInterface
{
    public const AUTHOR_ID = 'author_id';
    public const SLUG = 'slug';
    public const NAME = 'name';
    public const BIO = 'bio';
    public const AVATAR = 'avatar';
    public const EMAIL = 'email';
    public const TWITTER = 'twitter';
    public const LINKEDIN = 'linkedin';
    public const WEBSITE = 'website';
    public const IS_ACTIVE = 'is_active';

    public function getAuthorId(): ?int;
    public function setAuthorId(int $id): self;
    public function getSlug(): string;
    public function setSlug(string $slug): self;
    public function getName(): string;
    public function setName(string $name): self;
    public function getBio(): ?string;
    public function setBio(?string $bio): self;
    public function getAvatar(): ?string;
    public function setAvatar(?string $path): self;
    public function getEmail(): ?string;
    public function setEmail(?string $email): self;
    public function getTwitter(): ?string;
    public function setTwitter(?string $twitter): self;
    public function getLinkedin(): ?string;
    public function setLinkedin(?string $linkedin): self;
    public function getWebsite(): ?string;
    public function setWebsite(?string $website): self;
    public function getIsActive(): bool;
    public function setIsActive(bool $flag): self;

    public function getExtensionAttributes(): ?AuthorExtensionInterface;
    public function setExtensionAttributes(AuthorExtensionInterface $extensionAttributes): self;
}
