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
}
