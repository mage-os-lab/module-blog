<?php

declare(strict_types=1);

namespace MageOS\Blog\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractExtensibleModel;
use MageOS\Blog\Api\Data\AuthorExtensionInterface;
use MageOS\Blog\Api\Data\AuthorInterface;
use MageOS\Blog\Model\ResourceModel\Author as AuthorResource;

class Author extends AbstractExtensibleModel implements AuthorInterface, IdentityInterface
{
    public const CACHE_TAG = 'mageos_blog_author';

    protected $_eventPrefix = 'mageos_blog_author';
    protected $_eventObject = 'author';

    protected function _construct(): void
    {
        $this->_init(AuthorResource::class);
    }

    public function getIdentities(): array
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getAuthorId(): ?int
    {
        $value = $this->getData(self::AUTHOR_ID);
        return $value === null ? null : (int) $value;
    }

    public function setAuthorId(int $id): self
    {
        return $this->setData(self::AUTHOR_ID, $id);
    }

    public function getSlug(): string
    {
        return (string) $this->getData(self::SLUG);
    }

    public function setSlug(string $slug): self
    {
        return $this->setData(self::SLUG, $slug);
    }

    public function getName(): string
    {
        return (string) $this->getData(self::NAME);
    }

    public function setName(string $name): self
    {
        return $this->setData(self::NAME, $name);
    }

    public function getBio(): ?string
    {
        $value = $this->getData(self::BIO);
        return $value === null ? null : (string) $value;
    }

    public function setBio(?string $bio): self
    {
        return $this->setData(self::BIO, $bio);
    }

    public function getAvatar(): ?string
    {
        $value = $this->getData(self::AVATAR);
        return $value === null ? null : (string) $value;
    }

    public function setAvatar(?string $path): self
    {
        return $this->setData(self::AVATAR, $path);
    }

    public function getEmail(): ?string
    {
        $value = $this->getData(self::EMAIL);
        return $value === null ? null : (string) $value;
    }

    public function setEmail(?string $email): self
    {
        return $this->setData(self::EMAIL, $email);
    }

    public function getTwitter(): ?string
    {
        $value = $this->getData(self::TWITTER);
        return $value === null ? null : (string) $value;
    }

    public function setTwitter(?string $twitter): self
    {
        return $this->setData(self::TWITTER, $twitter);
    }

    public function getLinkedin(): ?string
    {
        $value = $this->getData(self::LINKEDIN);
        return $value === null ? null : (string) $value;
    }

    public function setLinkedin(?string $linkedin): self
    {
        return $this->setData(self::LINKEDIN, $linkedin);
    }

    public function getWebsite(): ?string
    {
        $value = $this->getData(self::WEBSITE);
        return $value === null ? null : (string) $value;
    }

    public function setWebsite(?string $website): self
    {
        return $this->setData(self::WEBSITE, $website);
    }

    public function getIsActive(): bool
    {
        return (bool) $this->getData(self::IS_ACTIVE);
    }

    public function setIsActive(bool $flag): self
    {
        return $this->setData(self::IS_ACTIVE, $flag);
    }

    public function getExtensionAttributes(): ?AuthorExtensionInterface
    {
        /** @var ?AuthorExtensionInterface $value */
        $value = $this->_getExtensionAttributes();

        return $value;
    }

    public function setExtensionAttributes(AuthorExtensionInterface $extensionAttributes): self
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
