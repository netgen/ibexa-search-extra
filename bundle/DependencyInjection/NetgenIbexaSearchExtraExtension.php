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
        return 'netgen_ez_platform_search_extra';
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
            $loader->load('search/solr.yaml');
        }

        $loader->load('search/common.yaml');

        $this->processExtensionConfiguration($configs, $container);
    }

    private function processExtensionConfiguration(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);

        $configuration = $this->processConfiguration($configuration, $configs);

        $this->processIndexableFieldTypeConfiguration($configuration, $container);
        $this->processSearchResultExtractorConfiguration($configuration, $container);
    }

    private function processSearchResultExtractorConfiguration(array $configuration, ContainerBuilder $container): void
    {
        $container->setParameter(
            'netgen_ez_platform_search_extra.use_loading_search_result_extractor',
            $configuration['use_loading_search_result_extractor'],
        );
    }

    private function processIndexableFieldTypeConfiguration(array $configuration, ContainerBuilder $container): void
    {
        $container->setParameter(
            'netgen_ez_platform_search_extra.indexable_field_type.ezrichtext.enabled',
            $configuration['indexable_field_type']['ezrichtext']['enabled'],
        );
        $container->setParameter(
            'netgen_ez_platform_search_extra.indexable_field_type.ezrichtext.short_text_limit',
            $configuration['indexable_field_type']['ezrichtext']['short_text_limit'],
        );
    }
}
