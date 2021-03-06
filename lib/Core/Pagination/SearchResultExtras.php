<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Pagination;

use Netgen\IbexaSearchExtra\API\Values\Content\Search\Suggestion;

/**
 * Defines access to extra information of the search query result.
 */
interface SearchResultExtras
{
    /**
     * Return facets for the search query.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Search\Facet[]
     */
    public function getFacets(): array;

    /**
     * Return maximum score for the search query.
     *
     * @return float
     */
    public function getMaxScore(): float;

    /**
     * Return suggestion object for the search query.
     */
    public function getSuggestion(): Suggestion;

    /**
     * Return duration of the search query processing in milliseconds.
     *
     * Note: this will be available only if the query is executed.
     *
     * @return float|int|null
     */
    public function getTime();
}
