<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Container\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DescendantIndexingPass implements CompilerPassInterface
{
    private const ParentChildConfigurationParameter = 'netgen.ibexa_search_extra.descendant_indexing.configuration';
    private const ParentChildMessageHandlerTag = 'netgen.ibexa_search_extra.descendant_indexing.message_handler';
    private const MessageHandlerTag = 'messenger.message_handler';
    private const ParentChildSolrContentFieldMapperServiceId = 'netgen.ibexa_search_extra.solr.field_mapper.descendant_indexing.content';
    private const ParentChildSolrContentFieldMapperTag = 'netgen.ibexa_search_extra.solr.field_mapper.descendant_indexing.content';
    private const ParentChildSolrContentTranslationFieldMapperServiceId = 'netgen.ibexa_search_extra.solr.field_mapper.descendant_indexing.content.translation';
    private const ParentChildSolrContentTranslationFieldMapperTag = 'netgen.ibexa_search_extra.solr.field_mapper.descendant_indexing.content.translation';
    private const ParentChildSolrLocationFieldMapperServiceId = 'netgen.ibexa_search_extra.solr.field_mapper.descendant_indexing.location';
    private const ParentChildSolrLocationFieldMapperTag = 'netgen.ibexa_search_extra.solr.field_mapper.descendant_indexing.location';

    public function process(ContainerBuilder $container): void
    {
        $configuration = $container->getParameter(self::ParentChildConfigurationParameter);
        $isEnabled = $configuration['enabled'] ?? false;

        if (!$isEnabled) {
            return;
        }

        $this->registerHandlers($container);
        $this->registerSolrContentFieldMappers($container);
        $this->registerSolrContentTranslationFieldMappers($container);
        $this->registerSolrLocationFieldMappers($container);
    }

    private function registerHandlers(ContainerBuilder $container): void
    {
        $serviceIds = $container->findTaggedServiceIds(self::ParentChildMessageHandlerTag);

        foreach ($serviceIds as $serviceId => $tag) {
            $definition = $container->getDefinition($serviceId);
            $definition->addTag(self::MessageHandlerTag);
        }
    }

    private function registerSolrContentFieldMappers(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition(self::ParentChildSolrContentFieldMapperServiceId);
        $serviceIds = $container->findTaggedServiceIds(self::ParentChildSolrContentFieldMapperTag);

        foreach (array_keys($serviceIds) as $id) {
            $definition->addMethodCall('addFieldMapper', [new Reference($id)]);
        }
    }

    private function registerSolrContentTranslationFieldMappers(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition(self::ParentChildSolrContentTranslationFieldMapperServiceId);
        $serviceIds = $container->findTaggedServiceIds(self::ParentChildSolrContentTranslationFieldMapperTag);

        foreach (array_keys($serviceIds) as $id) {
            $definition->addMethodCall('addFieldMapper', [new Reference($id)]);
        }
    }

    private function registerSolrLocationFieldMappers(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition(self::ParentChildSolrLocationFieldMapperServiceId);
        $serviceIds = $container->findTaggedServiceIds(self::ParentChildSolrLocationFieldMapperTag);

        foreach (array_keys($serviceIds) as $id) {
            $definition->addMethodCall('addFieldMapper', [new Reference($id)]);
        }
    }
}
