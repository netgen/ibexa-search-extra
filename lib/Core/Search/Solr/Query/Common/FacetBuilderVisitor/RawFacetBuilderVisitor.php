<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Solr\Query\Common\FacetBuilderVisitor;

use Ibexa\Solr\Query\FacetBuilderVisitor;
use Ibexa\Solr\Query\FacetFieldVisitor;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\FacetBuilder;
use Netgen\IbexaSearchExtra\Core\Search\Solr\API\Facet\RawFacet;
use Netgen\IbexaSearchExtra\Core\Search\Solr\API\FacetBuilder\RawFacetBuilder;
use Netgen\IbexaSearchExtra\Core\Search\Solr\Query\Common\FacetBuilderVisitor\RawFacetBuilderVisitor\DomainVisitor;
use function reset;

/**
 * Visits the RawFacetBuilder.
 */
class RawFacetBuilderVisitor extends FacetBuilderVisitor implements FacetFieldVisitor
{
    private DomainVisitor $domainVisitor;

    public function __construct(DomainVisitor $domainVisitor)
    {
        $this->domainVisitor = $domainVisitor;
    }

    public function mapField($field, array $data, FacetBuilder $facetBuilder): RawFacet
    {
        return new RawFacet([
            'name' => $facetBuilder->name,
            'data' => reset($data),
        ]);
    }

    public function canVisit(FacetBuilder $facetBuilder): bool
    {
        return $facetBuilder instanceof RawFacetBuilder;
    }

    public function visitBuilder(FacetBuilder $facetBuilder, $fieldId): array
    {
        /** @var $facetBuilder \Netgen\IbexaSearchExtra\Core\Search\Solr\API\FacetBuilder\RawFacetBuilder */
        $parameters = $facetBuilder->parameters ?? [];

        if ($facetBuilder->domain !== null) {
            $parameters['domain'] = $this->domainVisitor->visit($facetBuilder->domain);
        }

        return $parameters;
    }
}
