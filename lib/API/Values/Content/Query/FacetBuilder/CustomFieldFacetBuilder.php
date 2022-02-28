<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\API\Values\Content\Query\FacetBuilder;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\FacetBuilder;

/**
 * Builds a custom field facet.
 */
class CustomFieldFacetBuilder extends FacetBuilder
{
    /**
     * Sort by facet count descending.
     */
    public const COUNT_DESC = 'count_descending';

    /**
     * Sort by facet term ascending.
     */
    public const TERM_ASC = 'term_ascending';

    /**
     * Name of the field in the Solr backend.
     */
    public string $fieldName;

    /**
     * The sort order of the terms.
     *
     * One of CustomFieldFacetBuilder::COUNT_DESC, CustomFieldFacetBuilder::TERM_ASC.
     */
    public string $sort = self::TERM_ASC;
}
