<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Solr\Query\Common\SortClauseVisitor;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Contracts\Solr\Query\SortClauseVisitor;
use Netgen\IbexaSearchExtra\API\Values\Content\Query\SortClause\Random as RandomSortClause;

class Random extends SortClauseVisitor
{
    public function canVisit(SortClause $sortClause): bool
    {
        return $sortClause instanceof RandomSortClause;
    }

    public function visit(SortClause $sortClause): string
    {
        $seed = $sortClause->targetData->seed ?? mt_rand();

        return 'random_' . $seed . $this->getDirection($sortClause);
    }
}
