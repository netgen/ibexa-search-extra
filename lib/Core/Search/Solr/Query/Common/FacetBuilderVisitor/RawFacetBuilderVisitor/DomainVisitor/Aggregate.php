<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Solr\Query\Common\FacetBuilderVisitor\RawFacetBuilderVisitor\DomainVisitor;

use OutOfBoundsException;
use Netgen\IbexaSearchExtra\Core\Search\Solr\API\FacetBuilder\RawFacetBuilder\Domain;
use Netgen\IbexaSearchExtra\Core\Search\Solr\Query\Common\FacetBuilderVisitor\RawFacetBuilderVisitor\DomainVisitor;

class Aggregate extends DomainVisitor
{
    /**
     * @var \Netgen\IbexaSearchExtra\Core\Search\Solr\Query\Common\FacetBuilderVisitor\RawFacetBuilderVisitor\DomainVisitor[]
     */
    private array $visitors = [];

    /**
     * @param \Netgen\IbexaSearchExtra\Core\Search\Solr\Query\Common\FacetBuilderVisitor\RawFacetBuilderVisitor\DomainVisitor[] $visitors
     */
    public function __construct(array $visitors = [])
    {
        foreach ($visitors as $visitor) {
            $this->addVisitor($visitor);
        }
    }

    public function addVisitor(DomainVisitor $visitor): void
    {
        $this->visitors[] = $visitor;
    }

    public function accept(Domain $domain): bool
    {
        return true;
    }

    public function visit(Domain $domain): array
    {
        foreach ($this->visitors as $visitor) {
            if ($visitor->accept($domain)) {
                return $visitor->visit($domain);
            }
        }

        throw new OutOfBoundsException('No visitor found for the given domain');
    }
}
