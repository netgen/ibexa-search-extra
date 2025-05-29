<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Common\PageIndexing;

abstract class PageTextExtractor
{
    abstract public function extractPageText(int $contentId, string $languageCode);
}
