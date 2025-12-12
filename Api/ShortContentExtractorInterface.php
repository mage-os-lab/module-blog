<?php
declare(strict_types=1);

namespace MageOS\Blog\Api;

interface ShortContentExtractorInterface
{
    /**
     * Retrieve short filtered content
     * @param string$content
     * @param mixed $len
     * @param mixed $endCharacters
     * @return string
     * @throws \Exception
     */
    public function execute($content, $len = null, $endCharacters = null);
}
