<?php

declare(strict_types=1);

namespace Netgen\Bundle\IbexaSearchExtraBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

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
        $this->addHierarchicalIndexingSection($rootNode);

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
                            ?->end()
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
            ?->end();
    }

    private function addAsynchronousIndexingSection(ArrayNodeDefinition $nodeDefinition): void
    {
        $nodeDefinition
            ->children()
                ->booleanNode('use_asynchronous_indexing')
                    ->info('Use asynchronous mechanism to handle repository content indexing')
                    ->defaultFalse()
                ->end()
            ?->end();
    }

    private function addHierarchicalIndexingSection(ArrayNodeDefinition $nodeDefinition): void
    {
        $childrenNodeDefinition = $nodeDefinition
            ->children()
                ->arrayNode('hierarchical_indexing')
                    ->info('Hierarchical indexing configuration')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('descendant_indexing')
                            ->info('Descendant indexing configuration')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('enabled')
                                    ->info('Enable/disable descendant indexing')
                                    ->defaultFalse()
                                ->end()
                                ?->arrayNode('map')
                                    ->useAttributeAsKey('name')
                                    ->normalizeKeys(false)
                                    ->arrayPrototype()
                                        ->children()
                                            ->arrayNode('handlers')
                                                ->info('List of indexing handlers to execute')
                                                ->example([
                                                    'handler_identifier_1',
                                                    'handler_identifier_2',
                                                ])
                                                ->scalarPrototype()
                                                    ->defaultValue([])
                                                    ->validate()
                                                        ->ifTrue(fn ($v) => !is_string($v))
                                                        ->thenInvalid('Handler identifier must be a string.')
                                                    ->end()
                                                ->end()
                                            ?->end()
                                            ?->arrayNode('children')
                                                ->useAttributeAsKey('name')
                                                ->normalizeKeys(false)
                                                ->arrayPrototype()
        ;

        $this->buildChildrenNode($childrenNodeDefinition);
    }

    private function evaluateChildren(&$child, $name): void
    {
        $builder = new TreeBuilder($name, 'array');
        $root = $builder->getRootNode();

        $this->buildChildrenNode($root);

        $root->getNode(true)->finalize($child);
    }

    private function buildChildrenNode(ArrayNodeDefinition $node): void
    {
        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('indexed')
                    ->info('Whether the node should be indexed')
                    ->defaultTrue()
                ->end()
                ?->variableNode('children')
                    ->defaultValue([])
                    ->validate()
                        ->ifTrue(fn ($v) => !is_array($v))
                        ->thenInvalid('The children element must be an array.')
                    ->end()
                    ->validate()
                        ->always(
                            function ($children) {
                                array_walk($children, $this->evaluateChildren(...));

                                return $children;
                            }
                        )
                    ->end()
                ->end()
            ?->end()
            ->validate()
                ->always(
                    function ($children) {
                        foreach (array_keys($children) as $key) {
                            $allowedOptions = ['indexed', 'children'];

                            if (!in_array($key, $allowedOptions, true)) {
                                throw new InvalidConfigurationException(
                                    sprintf(
                                        'Unrecognized option "%s". Available options are "%s".',
                                        $key,
                                        implode('", "', $allowedOptions),
                                    ),
                                );
                            }
                        }

                        return $children;
                    }
                )
            ->end()
        ;
    }
}
