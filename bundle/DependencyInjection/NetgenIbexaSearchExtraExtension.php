<?php

declare(strict_types=1);

namespace Netgen\Bundle\IbexaSearchExtraBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use function array_key_exists;

class NetgenIbexaSearchExtraExtension extends Extension
{
    public function getAlias(): string
    {
        return 'netgen_ibexa_search_extra';
    }

    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        return new Configuration($this->getAlias());
    }

    /**
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $activatedBundlesMap = $container->getParameter('kernel.bundles');

        $loader = new Loader\YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../lib/Resources/config/'),
        );

        if (array_key_exists('IbexaLegacySearchEngineBundle', $activatedBundlesMap)) {
            $loader->load('search/legacy.yaml');
        }

        if (array_key_exists('IbexaSolrBundle', $activatedBundlesMap)) {
            $loader->load('search/solr_services.yaml');
            $this->loadBundleSolrEngine($container);
        }

        $loader->load('search/common.yaml');

        $this->processExtensionConfiguration($configs, $container);
    }

    /**
     * @throws \Exception
     */
    private function loadBundleSolrEngine(ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config/'),
        );

        $loader->load('solr_engine.yaml');
    }

    private function processExtensionConfiguration(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);

        $configuration = $this->processConfiguration($configuration, $configs);

        $this->processIndexableFieldTypeConfiguration($configuration, $container);
        $this->processSearchResultExtractorConfiguration($configuration, $container);
        $this->processFullTextBoostConfiguration($configuration, $container);
    }

    private function processSearchResultExtractorConfiguration(array $configuration, ContainerBuilder $container): void
    {
        $container->setParameter(
            'netgen_ibexa_search_extra.use_loading_search_result_extractor',
            $configuration['use_loading_search_result_extractor'],
        );
    }

    private function processIndexableFieldTypeConfiguration(array $configuration, ContainerBuilder $container): void
    {
        $container->setParameter(
            'netgen_ibexa_search_extra.indexable_field_type.ezrichtext.enabled',
            $configuration['indexable_field_type']['ezrichtext']['enabled'],
        );
        $container->setParameter(
            'netgen_ibexa_search_extra.indexable_field_type.ezrichtext.short_text_limit',
            $configuration['indexable_field_type']['ezrichtext']['short_text_limit'],
        );
    }

    private function processFullTextBoostConfiguration(array $configuration, ContainerBuilder $container)
    {
        $fullTextBoostConfig = $container->getParameter('netgen_ibexa_search_extra')['search_boost'];

        $container->setParameter(
            'netgen_ibexa_search_extra.search_boost',
            $configuration['search_boost'] ?? [],
        );

        $container->setParameter(
            'netgen_ibexa_search_extra.field_mapper_custom_fulltext_field_config',
            $configuration['field_mapper_custom_fulltext_field_config'] ?? [],
        );

        if (!array_key_exists('content_types', $container->getParameter('netgen_ibexa_search_extra.search_boost'))) {
            $fullTextBoostConfig['content_types'] = null;
        }
        if (!array_key_exists('raw_fields', $container->getParameter('netgen_ibexa_search_extra.search_boost'))) {
            $fullTextBoostConfig['raw_fields'] = null;
        }
        if (!array_key_exists('meta_fields', $container->getParameter('netgen_ibexa_search_extra.search_boost'))) {
            $fullTextBoostConfig['meta_fields'] = null;
        }

        $container->setParameter(
            'netgen_ibexa_search_extra.search_boost',
            $fullTextBoostConfig,
        );
    }
}
