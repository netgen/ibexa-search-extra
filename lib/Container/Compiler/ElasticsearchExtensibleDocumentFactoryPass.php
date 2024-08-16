<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Container\Compiler;

use Ibexa\Contracts\Core\Persistence\Content\Handler;
use Ibexa\Elasticsearch\DocumentMapper\DocumentFactoryInterface;
use Netgen\IbexaSearchExtra\Core\Search\Elasticsearch\DocumentMapper\DocumentFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use function array_keys;
use function sprintf;

/**
 * This compiler pass will register elastic search field mappers.
 */
final class ElasticsearchExtensibleDocumentFactoryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $this->processVisitors($container, 'block_translation');
        $this->processVisitors($container, 'block');
        $this->processVisitors($container, 'content');
        $this->processVisitors($container, 'content_translation');
        $this->processVisitors($container, 'location');
        $this->processVisitors($container, 'location_translation');
    }


    private function processVisitors(ContainerBuilder $container, string $name): void
    {
        if (!$container->hasDefinition(sprintf('netgen.ibexa_search_extra.elasticsearch.field_mapper.%s.aggregate', $name))) {
            return;
        }

        $aggregateDefinition = $container->getDefinition(
            sprintf('netgen.ibexa_search_extra.elasticsearch.field_mapper.%s.aggregate', $name),
        );

        $this->registerMappers($aggregateDefinition, $container->findTaggedServiceIds(sprintf('netgen.ibexa_search_extra.elasticsearch.field_mapper.%s', $name)));
    }

    private function registerMappers(Definition $definition, array $mapperIds): void
    {
        foreach (array_keys($mapperIds) as $id) {
            $definition->addMethodCall('addMapper', [new Reference($id)]);
        }
    }
}
