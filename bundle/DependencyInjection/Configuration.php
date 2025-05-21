<?php

declare(strict_types=1);

namespace Netgen\Bundle\IbexaSearchExtraBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    protected string $rootNodeName;

    public function __construct(string $rootNodeName)
    {
        $this->rootNodeName = $rootNodeName;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder($this->rootNodeName);
        $rootNode = $treeBuilder->getRootNode();

        $this->addIndexableFieldTypeSection($rootNode);
        $this->addSearchResultExtractorSection($rootNode);
        $this->addAsynchronousIndexingSection($rootNode);
        $this->addFulltextBoostSection($rootNode);

        return $treeBuilder;
    }

    private function addIndexableFieldTypeSection(ArrayNodeDefinition $nodeDefinition): void
    {
        $nodeDefinition
            ->children()
                ->arrayNode('indexable_field_type')
                    ->info('Configure override for field type Indexable interface implementation')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('ezrichtext')
                            ->addDefaultsIfNotSet()
                            ->canBeDisabled()
                            ->children()
                                ->integerNode('short_text_limit')
                                    ->info("Maximum number of characters for the indexed short text ('value' string type field)")
                                    ->defaultValue(256)
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addSearchResultExtractorSection(ArrayNodeDefinition $nodeDefinition): void
    {
        $nodeDefinition
            ->children()
                ->booleanNode('use_loading_search_result_extractor')
                    ->info('Get search result objects by loading them from the persistence layer, instead of reconstructing them from the returned Solr data')
                    ->defaultTrue()
                ->end()
            ->end();
    }

    private function addAsynchronousIndexingSection(ArrayNodeDefinition $nodeDefinition): void
    {
        $nodeDefinition
            ->children()
                ->booleanNode('use_asynchronous_indexing')
                    ->info('Use asynchronous mechanism to handle repository content indexing')
                    ->defaultFalse()
                ->end()
            ->end();
    }

    private function addFulltextBoostSection(ArrayNodeDefinition $nodeDefinition): void
    {
        $nodeDefinition
            ->children()
                ->arrayNode('search_boost')
                    ->info('Search boost configuration')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('content_types')
                            ->info('Define boost value per content type')
                            ->useAttributeAsKey('name')
                            ->normalizeKeys(false)
                            ->arrayPrototype()
                                ->children()
                                    ->integerNode('id')
                                        ->info('Content type id')
                                        ->isRequired()
                                    ->end()
                                    ->floatNode('boost_value')
                                        ->info('Boost value for the content type')
                                        ->isRequired()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('raw_fields')
                            ->info('Boost values for raw fields')
                            ->useAttributeAsKey('name')
                            ->normalizeKeys(false)
                            ->floatPrototype()
                                ->info('Boost value for the raw field')
                            ->end()
                        ->end()
                        ->arrayNode('meta_fields')
                            ->info('Boost values for meta fields')
                            ->useAttributeAsKey('name')
                            ->normalizeKeys(false)
                            ->floatPrototype()
                                ->info('Boost value for the meta field')
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('field_mapper_custom_fulltext_field_config')
                    ->info('Custom fulltext field mapping')
                    ->useAttributeAsKey('name')
                    ->normalizeKeys(false)
                    ->arrayPrototype()
                        ->scalarPrototype()
                            ->info('List of mapped fields')
                            ->validate()
                                ->ifTrue(static fn ($v) => !is_string($v))
                                ->thenInvalid('Mapped fields must be of string type')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
