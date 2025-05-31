<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Common\PageIndexing;

abstract class TextExtractor
{
    /**
     * @return array<string, array<int, string>>
     */
    abstract public function extractText(string $source, int $contentId, string $languageCode): array;
}
