<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Container\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DescendantIndexingPass implements CompilerPassInterface
{
    private const DescendantIndexingConfigurationParameter = 'netgen.ibexa_search_extra.descendant_indexing.configuration';
    private const DescendantIndexingMessageHandlerTag = 'netgen.ibexa_search_extra.descendant_indexing.message_handler';
    private const MessageHandlerTag = 'messenger.message_handler';
    private const DescendantIndexingSolrBlockFieldMapperServiceId = 'netgen.ibexa_search_extra.solr.field_mapper.descendant_indexing.block';
    private const DescendantIndexingSolrBlockFieldMapperTag = 'netgen.ibexa_search_extra.solr.field_mapper.descendant_indexing.block';
    private const DescendantIndexingSolrBlockTranslationFieldMapperServiceId = 'netgen.ibexa_search_extra.solr.field_mapper.descendant_indexing.block.translation';
    private const DescendantIndexingSolrBlockTranslationFieldMapperTag = 'netgen.ibexa_search_extra.solr.field_mapper.descendant_indexing.block.translation';
    private const DescendantIndexingSolrContentFieldMapperServiceId = 'netgen.ibexa_search_extra.solr.field_mapper.descendant_indexing.content';
    private const DescendantIndexingSolrContentFieldMapperTag = 'netgen.ibexa_search_extra.solr.field_mapper.descendant_indexing.content';
    private const DescendantIndexingSolrContentTranslationFieldMapperServiceId = 'netgen.ibexa_search_extra.solr.field_mapper.descendant_indexing.content.translation';
    private const DescendantIndexingSolrContentTranslationFieldMapperTag = 'netgen.ibexa_search_extra.solr.field_mapper.descendant_indexing.content.translation';
    private const DescendantIndexingSolrLocationFieldMapperServiceId = 'netgen.ibexa_search_extra.solr.field_mapper.descendant_indexing.location';
    private const DescendantIndexingSolrLocationFieldMapperTag = 'netgen.ibexa_search_extra.solr.field_mapper.descendant_indexing.location';

    public function process(ContainerBuilder $container): void
    {
        $configuration = $container->getParameter(self::DescendantIndexingConfigurationParameter);
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
        $serviceIds = $container->findTaggedServiceIds(self::DescendantIndexingMessageHandlerTag);

        foreach ($serviceIds as $serviceId => $tag) {
            $definition = $container->getDefinition($serviceId);
            $definition->addTag(self::MessageHandlerTag);
        }
    }

    private function registerSolrBlockFieldMappers(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition(self::DescendantIndexingSolrBlockFieldMapperServiceId);
        $serviceIds = $container->findTaggedServiceIds(self::DescendantIndexingSolrBlockFieldMapperTag);

        foreach (array_keys($serviceIds) as $id) {
            $definition->addMethodCall('addFieldMapper', [new Reference($id)]);
        }
    }

    private function registerSolrBlockTranslationFieldMappers(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition(self::DescendantIndexingSolrBlockTranslationFieldMapperServiceId);
        $serviceIds = $container->findTaggedServiceIds(self::DescendantIndexingSolrBlockTranslationFieldMapperTag);

        foreach (array_keys($serviceIds) as $id) {
            $definition->addMethodCall('addFieldMapper', [new Reference($id)]);
        }
    }

    private function registerSolrContentFieldMappers(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition(self::DescendantIndexingSolrContentFieldMapperServiceId);
        $serviceIds = $container->findTaggedServiceIds(self::DescendantIndexingSolrContentFieldMapperTag);

        foreach (array_keys($serviceIds) as $id) {
            $definition->addMethodCall('addFieldMapper', [new Reference($id)]);
        }
    }

    private function registerSolrContentTranslationFieldMappers(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition(self::DescendantIndexingSolrContentTranslationFieldMapperServiceId);
        $serviceIds = $container->findTaggedServiceIds(self::DescendantIndexingSolrContentTranslationFieldMapperTag);

        foreach (array_keys($serviceIds) as $id) {
            $definition->addMethodCall('addFieldMapper', [new Reference($id)]);
        }
    }

    private function registerSolrLocationFieldMappers(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition(self::DescendantIndexingSolrLocationFieldMapperServiceId);
        $serviceIds = $container->findTaggedServiceIds(self::DescendantIndexingSolrLocationFieldMapperTag);

        foreach (array_keys($serviceIds) as $id) {
            $definition->addMethodCall('addFieldMapper', [new Reference($id)]);
        }
    }
}
