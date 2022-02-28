<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Solr\API\FacetBuilder\RawFacetBuilder\Domain;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Netgen\IbexaSearchExtra\Core\Search\Solr\API\FacetBuilder\RawFacetBuilder\Domain;

/**
 * BlockChildren block-join domain for RawFacetBuilder.
 *
 * @see \Netgen\IbexaSearchExtra\Core\Search\Solr\API\FacetBuilder\RawFacetBuilder
 */
class BlockChildren extends Domain
{
    public string $parentDocumentIdentifier;
    public string $childDocumentIdentifier;
    public ?Criterion $filter;
}
