<?php

declare(strict_types=1);

namespace Netgen\Bundle\IbexaSearchExtraBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Yaml;

use function array_key_exists;

class NetgenIbexaSearchExtraExtension extends Extension implements PrependExtensionInterface
{
    private static array $defaultConfiguration = [
        'tree_root_location_id' => null,
        'languages_siteaccess_map' => [],
        'host' => null,
        'fields' => [],
        'allowed_content_types' => []
    ];
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

        if (array_key_exists('IbexaElasticsearchBundle', $activatedBundlesMap)) {
            $loader->load('search/elasticsearch_services.yaml');
        }

        $loader->load('search/common.yaml');

        $this->processExtensionConfiguration($configs, $container);
    }

    public function prepend(ContainerBuilder $container): void
    {
        $configs = [
            'messenger.yaml' => 'framework',
        ];

        foreach ($configs as $fileName => $extensionName) {
            $configFile = __DIR__ . '/../Resources/config/' . $fileName;
            $config = Yaml::parse((string) file_get_contents($configFile));
            $container->prependExtensionConfig($extensionName, $config);
            $container->addResource(new FileResource($configFile));
        }
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
        $this->processAsynchronousIndexingConfiguration($configuration, $container);
        $this->processFullTextBoostConfiguration($configuration, $container);
        $this->processUsePageIndexingConfiguration($configuration, $container);
        $this->processPageIndexingConfiguration($configuration, $container);
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

    private function processAsynchronousIndexingConfiguration(array $configuration, ContainerBuilder $container): void
    {
        $container->setParameter(
            'netgen_ibexa_search_extra.use_asynchronous_indexing',
            $configuration['use_asynchronous_indexing'],
        );
    }

    private function processFullTextBoostConfiguration(array $configuration, ContainerBuilder $container): void
    {
        $container->setParameter(
            'netgen_ibexa_search_extra.fulltext.boost',
            $configuration['fulltext']['boost'] ?? [],
        );

        $container->setParameter(
            'netgen_ibexa_search_extra.fulltext.meta_fields',
            $configuration['fulltext']['meta_fields'] ?? [],
        );
    }

    private function processUsePageIndexingConfiguration(array $configuration, ContainerBuilder $container): void
    {
        $container->setParameter(
            'netgen_ibexa_search_extra.use_page_indexing',
            $configuration['use_page_indexing'],
        );
    }

    private function processPageIndexingConfiguration(array $configuration, ContainerBuilder $container): void
    {
        $container->setParameter(
            'netgen_ibexa_search_extra.page_indexing.sites',
            $configuration['page_indexing']['sites'] ?? [],
        );

        $container->setParameter(
            'netgen_ibexa_search_extra.page_indexing.enabled',
            $configuration['page_indexing']['enabled'] ?? false,
        );

        if ($configuration['page_indexing']['sites'] === []) {
            $container->setParameter(
                'netgen_ibexa_search_extra.page_indexing.sites',
                [
                    'default' => self::$defaultConfiguration
                ]
            );
            return;
        }
        foreach ($container->getParameter('netgen_ibexa_search_extra.page_indexing.sites') as $siteName => $config) {
            $this->setPageIndexingSitesParameters($configuration, $container, $siteName);
        }
    }

    private function setPageIndexingSitesParameters(array $configuration, ContainerBuilder $container, string $siteName): void
    {
        /** @var array $pageIndexingSitesConfig */
        $pageIndexingSitesConfig = $container->getParameter('netgen_ibexa_search_extra.page_indexing.sites');

        if (!array_key_exists('tree_root_location_id', $container->getParameter('netgen_ibexa_search_extra.page_indexing.sites')[$siteName])) {
            $pageIndexingSitesConfig[$siteName]['tree_root_location_id'] = null;
        }

        if (!array_key_exists('languages_siteaccess_map', $container->getParameter('netgen_ibexa_search_extra.page_indexing.sites')[$siteName])) {
            $pageIndexingSitesConfig[$siteName]['languages_siteaccess_map'] = [];
        }

        if (!array_key_exists('host', $container->getParameter('netgen_ibexa_search_extra.page_indexing.sites')[$siteName])) {
            $pageIndexingSitesConfig[$siteName]['host'] = null;
        }

        if (!array_key_exists('fields', $container->getParameter('netgen_ibexa_search_extra.page_indexing.sites')[$siteName])) {
            $pageIndexingSitesConfig[$siteName]['fields'] = [];
        }

        if (!array_key_exists('allowed_content_types', $container->getParameter('netgen_ibexa_search_extra.page_indexing.sites')[$siteName])) {
            $pageIndexingSitesConfig[$siteName]['allowed_content_types'] = [];
        }

        $container->setParameter(
            'netgen_ibexa_search_extra.page_indexing.sites',
            $pageIndexingSitesConfig,
        );
    }
}
