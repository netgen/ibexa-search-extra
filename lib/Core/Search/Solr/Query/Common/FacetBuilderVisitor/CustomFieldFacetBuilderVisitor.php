<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Solr\Query\Common\FacetBuilderVisitor;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\FacetBuilder;
use Ibexa\Solr\Query\FacetBuilderVisitor;
use Ibexa\Solr\Query\FacetFieldVisitor;
use Netgen\IbexaSearchExtra\API\Values\Content\Query\FacetBuilder\CustomFieldFacetBuilder;
use Netgen\IbexaSearchExtra\API\Values\Content\Search\Facet\CustomFieldFacet;

/**
 * Visits the CustomField facet builder.
 *
 * @see \Netgen\IbexaSearchExtra\API\Values\Content\Query\FacetBuilder\CustomFieldFacetBuilder
 */
class CustomFieldFacetBuilderVisitor extends FacetBuilderVisitor implements FacetFieldVisitor
{
    public function canVisit(FacetBuilder $facetBuilder): bool
    {
        return $facetBuilder instanceof CustomFieldFacetBuilder;
    }

    public function mapField($field, array $data, FacetBuilder $facetBuilder): CustomFieldFacet
    {
        return new CustomFieldFacet([
            'name' => $facetBuilder->name,
            'entries' => $this->mapData($data),
        ]);
    }

    public function visitBuilder(FacetBuilder $facetBuilder, $fieldId): array
    {
        /** @var \Netgen\IbexaSearchExtra\API\Values\Content\Query\FacetBuilder\CustomFieldFacetBuilder $facetBuilder */
        $fieldName = $facetBuilder->fieldName;

        return [
            'facet.field' => "{!ex=dt key={$fieldId}}{$fieldName}",
            "f.{$fieldName}.facet.limit" => $facetBuilder->limit,
            "f.{$fieldName}.facet.mincount" => $facetBuilder->minCount,
            "f.{$fieldName}.facet.sort" => $this->getSort($facetBuilder),
        ];
    }

    /**
     * Returns facet sort parameter.
     *
     * @param \Netgen\IbexaSearchExtra\API\Values\Content\Query\FacetBuilder\CustomFieldFacetBuilder $facetBuilder
     *
     * @return string
     */
    private function getSort(CustomFieldFacetBuilder $facetBuilder): string
    {
        switch ($facetBuilder->sort) {
            case CustomFieldFacetBuilder::COUNT_DESC:
                return 'count';

            case CustomFieldFacetBuilder::TERM_ASC:
                return 'index';
        }

        return 'index';
    }
}
