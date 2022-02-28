<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Solr\Query\Content\CriterionVisitor;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Solr\Query\CriterionVisitor;
use Netgen\IbexaSearchExtra\API\Values\Content\Query\Criterion\Visible as VisibleCriterion;

class Visible extends CriterionVisitor
{
    public function canVisit(Criterion $criterion): bool
    {
        return $criterion instanceof VisibleCriterion;
    }

    public function visit(Criterion $criterion, ?CriterionVisitor $subVisitor = null): string
    {
        $isVisible = $criterion->value[0];

        return 'ng_content_visible_b:' . ($isVisible ? 'true' : 'false');
    }
}
