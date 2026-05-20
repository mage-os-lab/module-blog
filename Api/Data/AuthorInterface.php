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

    /** @return int|null */
    public function getAuthorId(): ?int;
    /** @param int $id @return self */
    public function setAuthorId(int $id): self;
    /** @return string */
    public function getSlug(): string;
    /** @param string $slug @return self */
    public function setSlug(string $slug): self;
    /** @return string */
    public function getName(): string;
    /** @param string $name @return self */
    public function setName(string $name): self;
    /** @return string|null */
    public function getBio(): ?string;
    /** @param string|null $bio @return self */
    public function setBio(?string $bio): self;
    /** @return string|null */
    public function getAvatar(): ?string;
    /** @param string|null $path @return self */
    public function setAvatar(?string $path): self;
    /** @return string|null */
    public function getEmail(): ?string;
    /** @param string|null $email @return self */
    public function setEmail(?string $email): self;
    /** @return string|null */
    public function getTwitter(): ?string;
    /** @param string|null $twitter @return self */
    public function setTwitter(?string $twitter): self;
    /** @return string|null */
    public function getLinkedin(): ?string;
    /** @param string|null $linkedin @return self */
    public function setLinkedin(?string $linkedin): self;
    /** @return string|null */
    public function getWebsite(): ?string;
    /** @param string|null $website @return self */
    public function setWebsite(?string $website): self;
    /** @return bool */
    public function getIsActive(): bool;
    /** @param bool $flag @return self */
    public function setIsActive(bool $flag): self;

    /** @return \MageOS\Blog\Api\Data\AuthorExtensionInterface|null */
    public function getExtensionAttributes(): ?AuthorExtensionInterface;
    /** @param \MageOS\Blog\Api\Data\AuthorExtensionInterface $extensionAttributes @return self */
    public function setExtensionAttributes(AuthorExtensionInterface $extensionAttributes): self;
}
