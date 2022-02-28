<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Solr\ResultExtractor;

use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchResult;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use IBexa\Contracts\Solr\ResultExtractor\AggregationResultExtractor;
use Ibexa\Solr\Gateway\EndpointRegistry;
use Ibexa\Solr\Query\FacetFieldVisitor;
use Ibexa\Solr\ResultExtractor as BaseResultExtractor;
use Netgen\IbexaSearchExtra\Core\Search\Solr\ResultExtractor;

/**
 * Native Result Extractor extracts the value object from the data returned by the Solr backend.
 */
final class NativeResultExtractor extends ResultExtractor
{
    private BaseResultExtractor $nativeResultExtractor;

    public function __construct(
        BaseResultExtractor $nativeResultExtractor,
        FacetFieldVisitor $facetBuilderVisitor,
        AggregationResultExtractor $aggregationResultExtractor,
        EndpointRegistry $endpointRegistry
    ) {
        $this->nativeResultExtractor = $nativeResultExtractor;

        parent::__construct($facetBuilderVisitor, $aggregationResultExtractor, $endpointRegistry);
    }

    public function extractHit($hit): ValueObject
    {
        return $this->nativeResultExtractor->extractHit($hit);
    }

    protected function extractSearchResult(
        $data,
        array $facetBuilders = [],
        array $aggregations = [],
        array $languageFilter = []
    ): SearchResult {
        return $this->nativeResultExtractor->extract(
            $data,
            $facetBuilders,
            $aggregations,
            $languageFilter,
        );
    }
}
