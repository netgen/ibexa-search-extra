<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Container\Compiler;

use Ibexa\Contracts\Core\Persistence\Content\Handler;
use Netgen\IbexaSearchExtra\Core\Search\Elasticsearch\DocumentMapper\DocumentFactory;
use Netgen\IbexaSearchExtra\Core\Search\Elasticsearch\Query\CriterionVisitor\Content\VisibilityVisitor as ContentVisibilityVisitor;
use Netgen\IbexaSearchExtra\Core\Search\Elasticsearch\Query\CriterionVisitor\Location\VisibilityVisitor as LocationVisibilityVisitor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Ibexa\Elasticsearch\DocumentMapper\DocumentFactoryInterface;
use function array_keys;

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

        $this->processDocumentFactory($container);

        $container
            ->register(ContentVisibilityVisitor::class, ContentVisibilityVisitor::class)
            ->addTag('ibexa.search.elasticsearch.query.content.criterion.visitor');

        $container
            ->register(LocationVisibilityVisitor::class, LocationVisibilityVisitor::class)
            ->addTag('ibexa.search.elasticsearch.query.location.criterion.visitor');
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

    /**
     * @param ContainerBuilder $container
     * @return void
     */
    public function processDocumentFactory(ContainerBuilder $container): void
    {
        $container
            ->register(DocumentFactory::class, DocumentFactory::class)
            ->setDecoratedService(DocumentFactoryInterface::class)
            ->setArguments([
                new Reference('.inner'),
                new Reference(Handler::class),
                new Reference('netgen.ibexa_search_extra.elasticsearch.field_mapper.content.aggregate'),
                new Reference('netgen.ibexa_search_extra.elasticsearch.field_mapper.location.aggregate'),
                new Reference('netgen.ibexa_search_extra.elasticsearch.field_mapper.content_translation.aggregate'),
                new Reference('netgen.ibexa_search_extra.elasticsearch.field_mapper.location_translation.aggregate'),
                new Reference('netgen.ibexa_search_extra.elasticsearch.field_mapper.block.aggregate'),
                new Reference('netgen.ibexa_search_extra.elasticsearch.field_mapper.block_translation.aggregate'),
            ]);
    }
}
