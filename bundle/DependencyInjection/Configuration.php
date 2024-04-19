<?php

declare(strict_types=1);

namespace Netgen\Bundle\IbexaSearchExtraBundle\DependencyInjection;

use Ibexa\Contracts\Core\Repository\LanguageService;
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
        $this->addUsePageIndexingSection($rootNode);
        $this->addPageIndexingSection($rootNode);

        return $treeBuilder;
    }

    private function addIndexableFieldTypeSection(ArrayNodeDefinition $nodeDefinition): void
    {
        /** @noinspection NullPointerExceptionInspection */
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
        /** @noinspection NullPointerExceptionInspection */
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
        /** @noinspection NullPointerExceptionInspection */
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
        /** @noinspection NullPointerExceptionInspection */
        $nodeDefinition
            ->children()
                ->arrayNode('fulltext')
                    ->info('Fulltext configuration')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('boost')
                            ->info('Boost configurations for SearchExtra Fulltext criterion')
                            ->useAttributeAsKey('name')
                            ->normalizeKeys(false)
                            ->arrayPrototype()
                                ->children()
                                    ->arrayNode('content_types')
                                        ->info('Boost values per ContentType')
                                        ->useAttributeAsKey('name')
                                        ->normalizeKeys(false)
                                        ->floatPrototype()
                                            ->info('Boost value for the ContentType')
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
                        ->end()
                        ->arrayNode('meta_fields')
                            ->info('Indexed fulltext meta fields mapping')
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
                    ->end()
                ->end()
            ->end();
    }

    private function addUsePageIndexingSection(ArrayNodeDefinition $nodeDefinition): void
    {
        $nodeDefinition
            ->children()
                ->booleanNode('use_page_indexing')
                    ->info('Use layouts page text indexing')
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
                ->info('Page indexing configuration')
                ->children()
                    ->arrayNode('site_roots')
                        ->info('Site root ids')
                        ->useAttributeAsKey('name')
                        ->normalizeKeys(false)
                        ->validate()
                            ->ifTrue($keyValidator)
                            ->thenInvalid('Site root name must be of string type.')
                        ->end()
                        ->integerPrototype()
                            ->beforeNormalization()->always(static fn ($v) => is_string($v) ? (int)$v : $v)->end()
                            ->info('Content ID')
                        ->end()
                    ->end()
                    ->arrayNode('languages_siteaccess_map')
                        ->info('Language key mapped to page siteaccess')
                        ->useAttributeAsKey('name')
                        ->normalizeKeys(false)
                        ->validate()
                            ->ifTrue($keyValidator)
                            ->thenInvalid('Page name must be of string type.')
                        ->end()
                        ->prototype('array')
                            ->useAttributeAsKey('name')
                            ->normalizeKeys(false)
                            ->validate()
                                ->ifTrue($keyValidator)
                                ->thenInvalid('Language code must be of string type.')
                            ->end()
                            ->prototype('scalar')
                                ->validate()
                                    ->ifTrue(static fn ($v) => !is_string($v))
                                    ->thenInvalid('Siteaccess name must be of string type.')
                                ->end()
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
                    ->arrayNode('config')
                        ->info('Config for separating page text by importance of the html tags and classes, used to index content to separate solr fields')
                        ->validate()
                            ->ifTrue($keyValidator)
                            ->thenInvalid('Array key (level of field importance) must be of string type.')
                        ->end()
                        ->prototype('array')
                            ->useAttributeAsKey('name')
                            ->normalizeKeys(false)
                            ->scalarPrototype()
                                ->validate()
                                    ->ifTrue(static fn ($v) => !is_string($v))
                                    ->thenInvalid('HTML tag and class must be of string type.')
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
                ->end()
            ->end();
    }
}
