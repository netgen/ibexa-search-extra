<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Solr\Query\Common\FacetBuilderVisitor\RawFacetBuilderVisitor;

use Netgen\IbexaSearchExtra\Core\Search\Solr\API\FacetBuilder\RawFacetBuilder\Domain;

abstract class DomainVisitor
{
    abstract public function accept(Domain $domain): bool;
    abstract public function visit(Domain $domain): array;
}
