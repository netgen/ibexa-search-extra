<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Solr\API\Facet;

use Ibexa\Contracts\Core\Repository\Values\Content\Search\Facet;

/**
 * Holds facet data for RawFacetBuilder.
 *
 * @see \Netgen\IbexaSearchExtra\Core\Search\Solr\API\Facet\RawFacetBuilder
 */
class RawFacet extends Facet
{
    /**
     * Facet data as \stdObject instance from \json_decode() on raw Solr search result.
     *
     * Example:
     *
     * ```php
     * $averagePrice = $facet->data->prices->allBuckets->average;
     * ```
     *
     * @var mixed
     */
    public $data;
}
