<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Solr\Query\Common;

use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Solr\Query\AggregationVisitor;
use Ibexa\Contracts\Solr\Query\CriterionVisitor;
use Ibexa\Contracts\Solr\Query\SortClauseVisitor;
use Ibexa\Solr\Query\FacetFieldVisitor;
use Ibexa\Solr\Query\QueryConverter as BaseQueryConverter;
use Netgen\IbexaSearchExtra\API\Values\Content\Query\Criterion\FulltextSpellcheck;
use Netgen\IbexaSearchExtra\Core\Search\Solr\API\FacetBuilder\RawFacetBuilder;
use function json_encode;

/**
 * Converts the query tree into an array of Solr query parameters.
 */
class QueryConverter extends BaseQueryConverter
{
    protected CriterionVisitor $criterionVisitor;
    protected SortClauseVisitor $sortClauseVisitor;
    protected FacetFieldVisitor $facetBuilderVisitor;
    private AggregationVisitor $aggregationVisitor;

    public function __construct(
        CriterionVisitor $criterionVisitor,
        SortClauseVisitor $sortClauseVisitor,
        FacetFieldVisitor $facetBuilderVisitor,
        AggregationVisitor $aggregationVisitor
    ) {
        $this->criterionVisitor = $criterionVisitor;
        $this->sortClauseVisitor = $sortClauseVisitor;
        $this->facetBuilderVisitor = $facetBuilderVisitor;
        $this->aggregationVisitor = $aggregationVisitor;
    }

    /**
     * @throws \JsonException
     */
    public function convert(Query $query, array $languageSettings = []): array
    {
        $params = [
            'q' => '{!lucene}' . $this->criterionVisitor->visit($query->query),
            'fq' => '{!lucene}' . $this->criterionVisitor->visit($query->filter),
            'sort' => $this->getSortParams($query->sortClauses),
            'start' => $query->offset,
            'rows' => $query->limit,
            'fl' => '*,score,[shard]',
            'wt' => 'json',
        ];

        $facetParams = $this->getFacetParams($query->facetBuilders);
        if (!empty($facetParams)) {
            $params['json.facet'] = json_encode($facetParams, JSON_THROW_ON_ERROR);
        }

        $oldFacetParams = $this->getOldFacetParams($query->facetBuilders);
        if (!empty($oldFacetParams)) {
            $params['facet'] = 'true';
            $params['facet.sort'] = 'count';
            $params = array_merge($oldFacetParams, $params);
        }

        if (!empty($query->aggregations)) {
            $aggregations = [];

            foreach ($query->aggregations as $aggregation) {
                if ($this->aggregationVisitor->canVisit($aggregation, $languageSettings)) {
                    $aggregations[$aggregation->getName()] = $this->aggregationVisitor->visit(
                        $this->aggregationVisitor,
                        $aggregation,
                        $languageSettings
                    );
                }
            }

            if (!empty($aggregations)) {
                $params['json.facet'] = json_encode($aggregations, JSON_THROW_ON_ERROR);
            }
        }

        if ($query->query instanceof FulltextSpellcheck) {
            $spellcheckQuery = $query->query->getSpellcheckQuery();

            $params['spellcheck.q'] = $spellcheckQuery->query;
            $params['spellcheck.count'] = $spellcheckQuery->count;

            foreach ($spellcheckQuery->parameters as $key => $value) {
                $params['spellcheck.'.$key] = $value;
            }
        }

        return $params;
    }

    /**
     * Converts an array of sort clause objects to a proper Solr representation.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause[] $sortClauses
     *
     * @return string
     */
    private function getSortParams(array $sortClauses): string
    {
        return implode(
            ', ',
            array_map(
                [$this->sortClauseVisitor, 'visit'],
                $sortClauses
            )
        );
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\FacetBuilder[] $facetBuilders
     *
     * @return array
     */
    private function getFacetParams(array $facetBuilders): array
    {
        $facetParams = [];
        $facetBuilders = $this->filterNewFacetBuilders($facetBuilders);

        foreach ($facetBuilders as $facetBuilder) {
            $identifier = spl_object_hash($facetBuilder);

            $facetParams[$identifier] = $this->facetBuilderVisitor->visitBuilder(
                $facetBuilder,
                null
            );
        }

        return $facetParams;
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\FacetBuilder[] $facetBuilders
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Query\FacetBuilder[]
     */
    private function filterNewFacetBuilders(array $facetBuilders): array
    {
        return array_filter(
            $facetBuilders,
            static function ($facetBuilder) {
                return $facetBuilder instanceof RawFacetBuilder;
            }
        );
    }

    /**
     * Converts an array of facet builder objects to a Solr query parameters representation.
     *
     * This method uses spl_object_hash() to get id of each and every facet builder, as this
     * is expected by {@link \Ibexa\Solr\ResultExtractor}.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\FacetBuilder[] $facetBuilders
     *
     * @return array
     */
    private function getOldFacetParams(array $facetBuilders): array
    {
        $facetParamsGrouped = array_map(
            function ($facetBuilder) {
                return $this->facetBuilderVisitor->visitBuilder($facetBuilder, spl_object_hash($facetBuilder));
            },
            $this->filterOldFacetBuilders($facetBuilders)
        );

        return $this->formatOldFacetParams($facetParamsGrouped);
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\FacetBuilder[] $facetBuilders
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Query\FacetBuilder[]
     */
    private function filterOldFacetBuilders(array $facetBuilders): array
    {
        return array_filter(
            $facetBuilders,
            static function ($facetBuilder) {
                return !($facetBuilder instanceof RawFacetBuilder);
            }
        );
    }

    private function formatOldFacetParams(array $facetParamsGrouped): array
    {
        $params = [];

        // In case when facet sets contain same keys, merge them in an array
        foreach ($facetParamsGrouped as $facetParams) {
            foreach ($facetParams as $key => $value) {
                if (isset($params[$key])) {
                    if (!is_array($params[$key])) {
                        $params[$key] = [$params[$key]];
                    }
                    $params[$key][] = $value;
                } else {
                    $params[$key] = $value;
                }
            }
        }

        return $params;
    }
}
