<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\API\Values\Content\Query\SortClause;

use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Netgen\IbexaSearchExtra\API\Values\Content\Query\SortClause\Target\RandomTarget;

/**
 * Sets sort random on a content query.
 */
class Random extends SortClause
{
    public function __construct($seed, string $sortDirection = Query::SORT_ASC)
    {
        parent::__construct('random', $sortDirection, new RandomTarget($seed));
    }
}
