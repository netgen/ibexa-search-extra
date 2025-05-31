<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtraBundle\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Netgen\Bundle\IbexaSearchExtraBundle\DependencyInjection\NetgenIbexaSearchExtraExtension;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class NetgenIbexaSearchExtraExtensionTest extends AbstractExtensionTestCase
{
    /**
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $loader = new YamlFileLoader(
            $this->container,
            new FileLocator(__DIR__ . '/_fixtures'),
        );

        $loader->load('indexable_field_types.yaml');

        $this->setParameter('kernel.bundles', []);
    }

    public function provideIndexableFieldTypeDefaultConfigurationCases(): iterable
    {
        return [
            [
                [],
            ],
            [
                [
                    'indexable_field_type' => [],
                ],
            ],
            [
                [
                    'indexable_field_type' => [
                        'ezrichtext' => [],
                    ],
                ],
            ],
            [
                [
                    'indexable_field_type' => [
                        'ezrichtext' => [
                            'enabled' => true,
                            'short_text_limit' => 256,
                        ],
                    ],
                ],
            ],
            [
                [
                    'use_loading_search_result_extractor' => true,
                    'indexable_field_type' => [
                        'ezrichtext' => [
                            'enabled' => true,
                            'short_text_limit' => 256,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideIndexableFieldTypeDefaultConfigurationCases
     */
    public function testIndexableFieldTypeDefaultConfiguration(array $configuration): void
    {
        $this->load($configuration);

        $this->assertContainerBuilderHasParameter(
            'netgen_ibexa_search_extra.use_loading_search_result_extractor',
            true,
        );
        $this->assertContainerBuilderHasParameter(
            'netgen_ibexa_search_extra.indexable_field_type.ezrichtext.enabled',
            true,
        );
        $this->assertContainerBuilderHasParameter(
            'netgen_ibexa_search_extra.indexable_field_type.ezrichtext.short_text_limit',
            256,
        );
    }

    public function providePageIndexingConfigurationCases(): iterable
    {
        return [
            [
                [
                    'page_indexing' => [
                        'enabled' => true,
                    ],
                ],
                null,
                null,
                [],
                null,
                [],
                [],
            ],
            [
                [
                    'page_indexing' => [
                        'enabled' => true,
                        'sites' => [
                            'picanha' => [
                                'tree_root_location_id' => '42',
                            ],
                        ],
                    ],
                ],
                'picanha',
                42,
                [],
                null,
                [],
                [],
            ],
            [
                [
                    'page_indexing' => [
                        'enabled' => true,
                        'sites' => [
                            'picanha' => [
                                'tree_root_location_id' => '42',
                                'language_siteaccess_map' => [
                                    'cro-HR' => 'fina_cro',
                                ],
                            ],
                        ],
                    ],
                ],
                'picanha',
                42,
                [
                    'cro-HR' => 'fina_cro',
                ],
                null,
                [],
                [],
            ],
            [
                [
                    'page_indexing' => [
                        'enabled' => true,
                        'sites' => [
                            'picanha' => [
                                'tree_root_location_id' => '42',
                                'host' => 'string',
                            ],
                        ],
                    ],
                ],
                'picanha',
                42,
                [],
                'string',
                [],
                [],
            ],
            [
                [
                    'page_indexing' => [
                        'enabled' => true,
                        'sites' => [
                            'picanha' => [
                                'tree_root_location_id' => '42',
                                'fields' => [
                                    'level1' => [
                                        'h1',
                                        'h2',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'picanha',
                42,
                [],
                null,
                [
                    'level1' => [
                        'h1',
                        'h2',
                    ],
                ],
                [],
            ],
            [
                [
                    'page_indexing' => [
                        'enabled' => true,
                        'sites' => [
                            'picanha' => [
                                'tree_root_location_id' => '42',
                                'allowed_content_types' => [
                                    'ng_landing_page',
                                    'ng_frontpage',
                                ],
                            ],
                        ],
                    ],
                ],
                'picanha',
                42,
                [],
                null,
                [],
                [
                    'ng_landing_page',
                    'ng_frontpage',
                ],
            ],
            [
                [
                    'page_indexing' => [
                        'enabled' => true,
                        'sites' => [
                            'picanha' => [
                                'tree_root_location_id' => '42',
                                'language_siteaccess_map' => [
                                    'cro-HR' => 'fina_cro',
                                ],
                                'host' => 'string',
                                'fields' => [
                                    'level1' => [
                                        'h1',
                                        'h2',
                                    ],
                                ],
                                'allowed_content_types' => [
                                    'ng_landing_page',
                                    'ng_frontpage',
                                ],
                            ],
                        ],
                    ],
                ],
                'picanha',
                42,
                [
                    'cro-HR' => 'fina_cro',
                ],
                'string',
                [
                    'level1' => [
                        'h1',
                        'h2',
                    ],
                ],
                [
                    'ng_landing_page',
                    'ng_frontpage',
                ],
            ],
        ];
    }

    /**
     * @dataProvider providePageIndexingConfigurationCases
     */
    public function testPageIndexingConfiguration(
        array $configuration,
        ?string $expectedSite,
        ?int $expectedTreeRootLocationId,
        array $expectedLanguagesSiteaccessMap,
        ?string $expectedHost,
        array $expectedFields,
        array $expectedAllowedContentTypes,
    ): void {
        $this->load($configuration);

        $this->assertContainerBuilderHasParameter('netgen_ibexa_search_extra.page_indexing.configuration');
        $sitesConfig = $this->container->getParameter('netgen_ibexa_search_extra.page_indexing.configuration');

        foreach ($sitesConfig as $site => $siteConfig) {
            self::assertIsString($site);
            self::assertEquals($site, $expectedSite);

            self::assertArrayHasKey(
                'tree_root_location_id',
                $siteConfig,
            );
            self::assertEquals($expectedTreeRootLocationId, $siteConfig['tree_root_location_id']);

            self::assertArrayHasKey(
                'language_siteaccess_map',
                $siteConfig,
            );
            self::assertEquals($expectedLanguagesSiteaccessMap, $siteConfig['language_siteaccess_map']);

            self::assertArrayHasKey(
                'fields',
                $siteConfig,
            );
            self::assertEquals($expectedFields, $siteConfig['fields']);

            self::assertArrayHasKey(
                'allowed_content_types',
                $siteConfig,
            );
            self::assertEquals($expectedAllowedContentTypes, $siteConfig['allowed_content_types']);

            self::assertArrayHasKey(
                'host',
                $siteConfig,
            );
            self::assertEquals($expectedHost, $siteConfig['host']);
        }
    }

    public function provideInvalidPageIndexingConfigurationCases(): iterable
    {
        return [
            [
                [
                    'page_indexing' => [
                        'picanha' => [
                            'tree_root_location_id' => [],
                        ],
                    ],
                ],
                InvalidConfigurationException::class,
                'Expected "int", but got "array"',
            ],
            [
                [
                    'page_indexing' => [
                        'picanha' => [
                            'tree_root_location_id' => true,
                        ],
                    ],
                ],
                InvalidConfigurationException::class,
                'Expected "int", but got "bool"',
            ],
            [
                [
                    'page_indexing' => [
                        'picanha' => [
                            'language_siteaccess_map' => [
                                'cro-HR' => 5,
                            ],
                        ],
                    ],
                ],
                InvalidConfigurationException::class,
                'Expected "string", but got "int"',
            ],
            [
                [
                    'page_indexing' => [
                        'picanha' => [
                            'host' => [],
                        ],
                    ],
                ],
                InvalidConfigurationException::class,
                'Expected "string", but got "array"',
            ],
            [
                [
                    'page_indexing' => [
                        'picanha' => [
                            'config' => [
                                'level1' => 'a',
                            ],
                        ],
                    ],
                ],
                InvalidConfigurationException::class,
                'Expected "array", but got "string"',
            ],
            [
                [
                    'page_indexing' => [
                        'picanha' => [
                            'config' => [
                                ['h1', 'h2'],
                            ],
                        ],
                    ],
                ],
                InvalidConfigurationException::class,
                'Array key (field importance level) must be of string type',
            ],
            [
                [
                    'page_indexing' => [
                        'picanha' => [
                            'allowed_content_types' => [
                                34,
                                52,
                            ],
                        ],
                    ],
                ],
                InvalidConfigurationException::class,
                'Expected "string", but got "int"',
            ],
        ];
    }

    /**
     * @dataProvider provideInvalidPageIndexingConfigurationCases
     */
    public function testInvalidPageIndexingConfiguration(array $siteRootsConfig): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->load($siteRootsConfig);
    }

    protected function getContainerExtensions(): array
    {
        return [
            new NetgenIbexaSearchExtraExtension(),
        ];
    }
}
