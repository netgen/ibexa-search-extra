<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Container\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use function array_keys;

/**
 * This compiler pass will register RawFacetBuilder Domain visitors.
 *
 * @see \Netgen\IbexaSearchExtra\Core\Search\Solr\API\FacetBuilder\RawFacetBuilder\Domain
 * @see \Netgen\IbexaSearchExtra\Core\Search\Solr\Query\Common\FacetBuilderVisitor\RawFacetBuilderVisitor\DomainVisitor\Aggregate
 */
final class RawFacetBuilderDomainVisitorPass implements CompilerPassInterface
{
    private static string $aggregateVisitorId = 'netgen.ibexa_search_extra.solr.query.common.facet_builder_visitor.raw.domain_visitor.aggregate';
    private static string $visitorTag = 'netgen.ibexa_search_extra.solr.query.common.facet_builder_visitor.raw.domain_visitor';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(self::$aggregateVisitorId)) {
            return;
        }

        $aggregateDefinition = $container->getDefinition(self::$aggregateVisitorId);
        $mapperIds = $container->findTaggedServiceIds(self::$visitorTag);

        $this->registerMappers($aggregateDefinition, $mapperIds);
    }

    private function registerMappers(Definition $definition, array $visitorIds): void
    {
        foreach (array_keys($visitorIds) as $id) {
            $definition->addMethodCall('addVisitor', [new Reference($id)]);
        }
    }
}
