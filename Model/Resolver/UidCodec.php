<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver;

use Magento\Framework\GraphQl\Query\Uid;

/**
 * Encodes and decodes the opaque uid the blog GraphQL types expose.
 *
 * The encoding is the core one (Magento\Framework\GraphQl\Query\Uid, base64 of the plain id), the
 * same shape the route and urlResolver queries return as entity_uid.
 *
 * Unlike core, decode() never throws: a core-style "Value of uid is incorrect" error would tell a
 * visitor that a uid is malformed rather than merely unknown. Callers treat null exactly like an
 * id that does not exist.
 */
class UidCodec
{
    private const MAX_ID = 2147483647;

    public function __construct(private readonly Uid $uid)
    {
    }

    public function encode(int $id): string
    {
        return $this->uid->encode((string) $id);
    }

    /**
     * @return int|null The positive id, or null when the uid is not exactly what encode() produces
     */
    public function decode(string $uid): ?int
    {
        // Bound the work before decoding: an int32 id encodes to at most 16 characters.
        if ($uid === '' || \strlen($uid) > 16 || !$this->uid->isValidBase64($uid)) {
            return null;
        }

        $decoded = (string) $this->uid->decode($uid);

        if (preg_match('/\A[1-9][0-9]{0,9}\z/', $decoded) !== 1) {
            return null;
        }

        $id = (int) $decoded;

        return $id <= self::MAX_ID ? $id : null;
    }
}
