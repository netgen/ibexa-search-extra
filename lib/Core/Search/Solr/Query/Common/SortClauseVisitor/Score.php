<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Solr\Query\Common\SortClauseVisitor;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Contracts\Solr\Query\SortClauseVisitor;
use Netgen\IbexaSearchExtra\API\Values\Content\Query\SortClause as SortClauseExtra;

class Score extends SortClauseVisitor
{
    public function canVisit(SortClause $sortClause): bool
    {
        return $sortClause instanceof SortClauseExtra\Score;
    }

    public function visit(SortClause $sortClause): string
    {
        return 'score' . $this->getDirection($sortClause);
    }
}
