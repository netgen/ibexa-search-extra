<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Container\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This compiler pass will register Solr Storage facet builder visitors.
 */
class AggregateFacetBuilderVisitorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $this->processVisitors($container, 'content');
        $this->processVisitors($container, 'location');
    }

    private function processVisitors(ContainerBuilder $container, string $name): void
    {
        if (!$container->hasDefinition("ibexa.solr.query.${name}.facet_builder_visitor.aggregate")) {
            return;
        }

        $aggregateFacetBuilderVisitorDefinition = $container->getDefinition(
            "ibexa.solr.query.${name}.facet_builder_visitor.aggregate"
        );

        foreach ($container->findTaggedServiceIds("ibexa.search.solr.query.${name}.facet_builder_visitor") as $id => $attributes) {
            $aggregateFacetBuilderVisitorDefinition->addMethodCall(
                'addVisitor',
                [
                    new Reference($id),
                ]
            );
        }
    }
}
