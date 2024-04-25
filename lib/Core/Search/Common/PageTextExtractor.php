<?php

namespace Netgen\IbexaSearchExtra\Core\Search\Common;

abstract class PageTextExtractor
{
    abstract public function extractPageText(int $contentId, string $languageCode);

}
