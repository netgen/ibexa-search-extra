<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Solr\Query\Common\FacetBuilderVisitor\RawFacetBuilderVisitor\DomainVisitor;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\CustomField;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LogicalAnd;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator;
use Ibexa\Contracts\Solr\Query\CriterionVisitor;
use Netgen\IbexaSearchExtra\Core\Search\Solr\API\FacetBuilder\RawFacetBuilder\Domain;
use Netgen\IbexaSearchExtra\Core\Search\Solr\API\FacetBuilder\RawFacetBuilder\Domain\BlockChildren as BlockChildrenDomain;
use Netgen\IbexaSearchExtra\Core\Search\Solr\Query\Common\FacetBuilderVisitor\RawFacetBuilderVisitor\DomainVisitor;

class BlockChildren extends DomainVisitor
{
    private CriterionVisitor $subdocumentQueryCriterionVisitor;

    public function __construct(CriterionVisitor $subdocumentQueryCriterionVisitor)
    {
        $this->subdocumentQueryCriterionVisitor = $subdocumentQueryCriterionVisitor;
    }

    public function accept(Domain $domain): bool
    {
        return $domain instanceof BlockChildrenDomain;
    }

    public function visit(Domain $domain): array
    {
        \assert($domain instanceof BlockChildrenDomain);

        return [
            'blockChildren' => "document_type_id:{$domain->parentDocumentIdentifier}",
            'filter' => $this->subdocumentQueryCriterionVisitor->visit(
                $this->getFilterCriteria($domain)
            ),
        ];
    }

    private function getFilterCriteria(BlockChildrenDomain $domain): Criterion
    {
        $criteria = new CustomField('document_type_id', Operator::EQ, $domain->childDocumentIdentifier);

        if ($domain->filter !== null) {
            $criteria = new LogicalAnd([$criteria, $domain->filter]);
        }

        return $criteria;
    }
}
