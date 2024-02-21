<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Solr;

use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Spellcheck;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchResult;
use Ibexa\Solr\ResultExtractor as BaseResultExtractor;
use Netgen\IbexaSearchExtra\API\Values\Content\Search\LocationQuery as ExtraLocationQuery;
use Netgen\IbexaSearchExtra\API\Values\Content\Search\Query as ExtraQuery;
use Netgen\IbexaSearchExtra\API\Values\Content\Search\SearchHit;
use Netgen\IbexaSearchExtra\Core\Search\Solr\API\FacetBuilder\RawFacetBuilder;
use stdClass;

use function array_filter;
use function get_object_vars;
use function property_exists;
use function spl_object_hash;

/**
 * This DocumentMapper implementation adds support for handling RawFacetBuilders.
 *
 * @see \Netgen\IbexaSearchExtra\Core\Search\Solr\API\Facet\RawFacetBuilder
 */
abstract class ResultExtractor extends BaseResultExtractor
{
    public function extract(
        $data,
        array $facetBuilders = [],
        array $aggregations = [],
        array $languageFilter = [],
        ?Spellcheck $spellcheck = null,
        ?Query $query = null,
    ): SearchResult {
        $searchResult = $this->extractSearchResult(
            $data,
            $facetBuilders,
            $aggregations,
            $languageFilter,
            $spellcheck,
        );

        foreach ($searchResult->searchHits as $key => $searchHit) {
            $searchResult->searchHits[$key] = new SearchHit(get_object_vars($searchHit));
            $searchResult->searchHits[$key]->extraFields = [];

            if (($query instanceof ExtraQuery || $query instanceof ExtraLocationQuery) && !empty($query->extraFields)) {
                $searchResult->searchHits[$key]->extraFields = $this->extractExtraFields(
                    $data,
                    $searchResult->searchHits[$key],
                    $query->extraFields,
                );
            }
        }

        if (!isset($data->facets) || $data->facets->count === 0) {
            return $searchResult;
        }

        foreach ($this->filterNewFacetBuilders($facetBuilders) as $facetBuilder) {
            $identifier = spl_object_hash($facetBuilder);

            $searchResult->facets[] = $this->facetBuilderVisitor->mapField(
                $identifier,
                [$data->facets->{$identifier}],
                $facetBuilder,
            );
        }

        return $searchResult;
    }

    /**
     * Extract the base search result.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\FacetBuilder[] $facetBuilders
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation[] $aggregations
     * @param array $languageFilter
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchResult
     */
    abstract protected function extractSearchResult(
        stdClass $data,
        array $facetBuilders = [],
        array $aggregations = [],
        array $languageFilter = [],
        ?Spellcheck $spellcheck = null,
    ): SearchResult;

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\FacetBuilder[] $facetBuilders
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Query\FacetBuilder[]
     */
    private function filterNewFacetBuilders(array $facetBuilders): array
    {
        return array_filter(
            $facetBuilders,
            static fn ($facetBuilder) => $facetBuilder instanceof RawFacetBuilder,
        );
    }

    /**
     * @param string[] $extraFields
     */
    private function extractExtraFields(stdClass $data, SearchHit $searchHit, array $extraFields): array
    {
        $extractedExtraFields = [];

        foreach ($data->response->docs as $doc) {
            if (
                ($doc->document_type_id === 'content' && (int) $doc->content_id_id === $searchHit->valueObject->id)
                || ($doc->document_type_id === 'location' && (int) $doc->location_id === $searchHit->valueObject->id)
            ) {
                foreach ($extraFields as $extraField) {
                    if (property_exists($doc, $extraField)) {
                        $extractedExtraFields[$extraField] = $doc->{$extraField};
                    }
                }
            }
        }

        return $extractedExtraFields;
    }
}
