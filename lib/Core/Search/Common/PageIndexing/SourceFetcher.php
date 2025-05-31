<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Common\PageIndexing;

abstract class SourceFetcher
{
    abstract public function fetchSource(string $url): string;
}
