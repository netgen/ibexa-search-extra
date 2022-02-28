<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Solr\API\FacetBuilder;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\FacetBuilder;
use Netgen\IbexaSearchExtra\Core\Search\Solr\API\FacetBuilder\RawFacetBuilder\Domain;

/**
 * RawFacetBuilder provides full Solr JSON facet API.
 */
class RawFacetBuilder extends FacetBuilder
{
    /**
     * Solr JSON facet API params as an array to be encoded with \json_encode().
     *
     * Example:
     *
     * ```php
     *  $facet->parameters = [
     *      'type': 'terms'
     *      'field' => 'genre',
     *      'limit' => 5,
     *  ];
     * ```
     */
    public ?array $parameters = null;
    public ?Domain $domain = null;
}
