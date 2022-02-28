<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\API\Values\Content\Query\SortClause\Target;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause\Target;

/**
 * Struct that stores extra target information for a RandomTarget object.
 */
class RandomTarget extends Target
{
    /** @var int|string|null */
    public $seed;

    public function __construct($seed = null)
    {
        $this->seed = $seed;
    }
}
