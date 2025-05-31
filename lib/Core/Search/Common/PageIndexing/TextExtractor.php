<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Common\PageIndexing;

use Ibexa\Contracts\Core\Persistence\Content\ContentInfo;

abstract class TextExtractor
{
    abstract public function extractPageText(ContentInfo $contentInfo, string $languageCode);
}
