<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\API\Values\Content\Search\Facet;

use Ibexa\Contracts\Core\Repository\Values\Content\Search\Facet;

/**
 * Holds custom field facet terms and counts.
 */
class CustomFieldFacet extends Facet
{
    /**
     * An array of terms (key) and counts (value).
     */
    public array $entries;
}
