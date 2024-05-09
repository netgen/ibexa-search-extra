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
        $this->addPageIndexingSection($rootNode);

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

    private function addPageIndexingSection(ArrayNodeDefinition $nodeDefinition): void
    {
        $keyValidator = static function ($v) {
            foreach (array_keys($v) as $key) {
                if (!is_string($key)) {
                    return true;
                }
            }
            return false;
        };
        $nodeDefinition
            ->children()
                ->arrayNode('page_indexing')
                ->addDefaultsIfNotSet()
                ->info('Page indexing configuration')
                ->children()
                    ->booleanNode('enabled')
                        ->info('Use page indexing')
                        ->defaultFalse()
                    ->end()
                    ->arrayNode('sites')
                        ->useAttributeAsKey('name')
                        ->normalizeKeys(false)
                        ->validate()
                            ->ifTrue($keyValidator)
                            ->thenInvalid('Site name must be of string type')
                        ->end()
                        ->arrayPrototype()
                            ->children()
                                ->integerNode('tree_root_location_id')
                                    ->info('Site root Location ID')
                                    ->beforeNormalization()->always(static fn ($v) => is_string($v) ? (int)$v : $v)->end()
                                ->end()
                                ->arrayNode('languages_siteaccess_map')
                                    ->info('Language code mapped to page siteaccess')
                                    ->useAttributeAsKey('name')
                                    ->normalizeKeys(false)
                                    ->validate()
                                        ->ifTrue($keyValidator)
                                        ->thenInvalid('Language code must be of string type.')
                                    ->end()
                                    ->scalarPrototype()
                                        ->validate()
                                            ->ifTrue(static fn ($v) => !is_string($v))
                                            ->thenInvalid('Siteaccess name must be of string type.')
                                        ->end()
                                    ->end()
                                ->end()
                                ->arrayNode('fields')
                                    ->info('Mapping of indexed field names to an array of HTML tag selectors')
                                    ->validate()
                                        ->ifTrue($keyValidator)
                                        ->thenInvalid('Indexed field name must be of string type')
                                    ->end()
                                    ->arrayPrototype()
                                        ->useAttributeAsKey('name')
                                        ->normalizeKeys(false)
                                        ->scalarPrototype()
                                            ->validate()
                                                ->ifTrue(static fn ($v) => !is_string($v))
                                                ->thenInvalid('HTML selector must be of string type.')
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                                ->arrayNode('allowed_content_types')
                                    ->info('Content types to index')
                                    ->useAttributeAsKey('name')
                                    ->normalizeKeys(false)
                                    ->scalarPrototype()
                                        ->validate()
                                            ->ifTrue(static fn ($v) => !is_string($v))
                                            ->thenInvalid('Content type identifier must be of string type.')
                                        ->end()
                                    ->end()
                                ->end()
                                ->scalarNode('host')
                                    ->info('Host to index page from, defined in .env files')
                                    ->validate()
                                        ->ifTrue(static fn ($v) => !is_string($v))
                                        ->thenInvalid('Host must be of string type.')
                                    ->end()
                                ->end()
                            ->end()
                    ->end()
                ->end()
            ->end();
    }

}
