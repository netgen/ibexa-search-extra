<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtraBundle\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Netgen\Bundle\IbexaSearchExtraBundle\DependencyInjection\NetgenIbexaSearchExtraExtension;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;
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

    public function providerForIndexableFieldTypeDefaultConfiguration(): array
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
     * @dataProvider providerForIndexableFieldTypeDefaultConfiguration
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

    public function providerForFulltextBoostDefaultConfiguration(): array
    {
        return [
            [
                [],
                [],
            ],
            [
                [
                    'fulltext' => [],
                ],
                [],
            ],
            [
                [
                    'fulltext' => [
                        'boost' => [],
                    ],
                ],
                [],
            ],
            [
                [
                    'fulltext' => [
                        'boost' => [
                            'configuration_name' => [],
                        ],
                    ],
                ],
                [
                    'configuration_name' => [
                        'content_types' => [],
                        'raw_fields' => [],
                        'meta_fields' => [],
                    ],
                ],
            ],
            [
                [
                    'fulltext' => [
                        'boost' => [
                            'configuration_name' => [
                                'content_types' => [],
                            ],
                        ],
                    ],
                ],
                [
                    'configuration_name' => [
                        'content_types' => [],
                        'raw_fields' => [],
                        'meta_fields' => [],
                    ],
                ],
            ],
            [
                [
                    'fulltext' => [
                        'boost' => [
                            'configuration_name' => [
                                'content_types' => [],
                                'raw_fields' => [],
                                'meta_fields' => [],
                            ],
                        ],
                    ],
                ],
                [
                    'configuration_name' => [
                        'content_types' => [],
                        'raw_fields' => [],
                        'meta_fields' => [],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerForFulltextBoostDefaultConfiguration
     */
    public function testFulltextBoostDefaultConfiguration(array $configuration, array $expectedValue): void
    {
        $this->load($configuration);

        $this->assertContainerBuilderHasParameter(
            'netgen_ibexa_search_extra.fulltext.boost',
            $expectedValue,
        );
    }

    public function testFulltextBoostConfigurationValues(): void
    {
        $boost = [
            'default_configuration' => [
                'content_types' => [
                    'rocket' => 2,
                    'missile' => 4,
                ],
                'raw_fields' => [
                    'meta_content__satellite_t' => 2,
                    'meta_content__station_t' => 2,
                ],
                'meta_fields' => [
                    'energia' => 128,
                    'proton' => 16,
                ],
            ]
        ];

        $this->load([
            'fulltext' => [
                'boost' => $boost,
            ],
        ]);

        $this->assertContainerBuilderHasParameter(
            'netgen_ibexa_search_extra.fulltext.boost',
            $boost,
        );
    }

    public function providerForFulltextBoostConfigurationInvalidValues(): array
    {
        return [
            [
                [
                    'fulltext' => [
                        'boost' => 1,
                    ],
                ],
                InvalidTypeException::class,
                'Invalid type for path "netgen_ibexa_search_extra.fulltext.boost". Expected "array", but got "int"',
            ],
            [
                [
                    'fulltext' => [
                        'boost' => [
                            'kvak_configuration' => 11,
                        ],
                    ],
                ],
                InvalidTypeException::class,
                'Invalid type for path "netgen_ibexa_search_extra.fulltext.boost.kvak_configuration". Expected "array", but got "int"',
            ],
            [
                [
                    'fulltext' => [
                        'boost' => [
                            'kvak_configuration' => [
                                'content_types' => 11,
                            ],
                        ],
                    ],
                ],
                InvalidTypeException::class,
                'Invalid type for path "netgen_ibexa_search_extra.fulltext.boost.kvak_configuration.content_types". Expected "array", but got "int"',
            ],
            [
                [
                    'fulltext' => [
                        'boost' => [
                            'kvak_configuration' => [
                                'raw_fields' => 11,
                            ],
                        ],
                    ],
                ],
                InvalidTypeException::class,
                'Invalid type for path "netgen_ibexa_search_extra.fulltext.boost.kvak_configuration.raw_fields". Expected "array", but got "int"',
            ],
            [
                [
                    'fulltext' => [
                        'boost' => [
                            'kvak_configuration' => [
                                'meta_fields' => 11,
                            ],
                        ],
                    ],
                ],
                InvalidTypeException::class,
                'Invalid type for path "netgen_ibexa_search_extra.fulltext.boost.kvak_configuration.meta_fields". Expected "array", but got "int"',
            ],
            [
                [
                    'fulltext' => [
                        'boost' => [
                            'kvak_configuration' => [
                                'raw_fields' => [
                                    'meta_content__satellite_t' => '2',
                                ],
                            ],
                        ],
                    ],
                ],
                InvalidTypeException::class,
                'Invalid type for path "netgen_ibexa_search_extra.fulltext.boost.kvak_configuration.raw_fields.meta_content__satellite_t". Expected "float", but got "string"',
            ],
            [
                [
                    'fulltext' => [
                        'boost' => [
                            'kvak_configuration' => [
                                'meta_fields' => [
                                    'energia' => '128',
                                ],
                            ],
                        ],
                    ],
                ],
                InvalidTypeException::class,
                'Invalid type for path "netgen_ibexa_search_extra.fulltext.boost.kvak_configuration.meta_fields.energia". Expected "float", but got "string"',
            ],
            [
                [
                    'fulltext' => [
                        'boost' => [
                            'kvak_configuration' => [
                                'content_types' => [
                                    'rocket' => '24',
                                ],
                            ],
                        ],
                    ],
                ],
                InvalidTypeException::class,
                'Invalid type for path "netgen_ibexa_search_extra.fulltext.boost.kvak_configuration.content_types.rocket". Expected "float", but got "string"',
            ],
        ];
    }

    /**
     * @dataProvider providerForFulltextBoostConfigurationInvalidValues
     */
    public function testFulltextBoostConfigurationInvalidValues(array $configuration, string $exceptionFqcn, string $message): void
    {
        $this->expectException($exceptionFqcn);
        $this->expectExceptionMessage($message);

        $this->load($configuration);
    }

    public function providerForFulltextMetaFieldsDefaultConfiguration(): array
    {
        return [
            [
                [],
                [],
            ],
            [
                [
                    'fulltext' => [],
                ],
                [],
            ],
            [
                [
                    'fulltext' => [
                        'meta_fields' => [],
                    ],
                ],
                [],
            ],
        ];
    }

    /**
     * @dataProvider providerForFulltextMetaFieldsDefaultConfiguration
     */
    public function testFulltextMetaFieldsDefaultConfiguration(array $configuration, array $expectedValue): void
    {
        $this->load($configuration);

        $this->assertContainerBuilderHasParameter(
            'netgen_ibexa_search_extra.fulltext.meta_fields',
            $expectedValue,
        );
    }

    public function testFulltextMetaFieldsConfigurationValues(): void
    {
        $fields = [
            'energia' => [
                'RD-170',
                'RD-0120',
            ],
        ];

        $this->load([
            'fulltext' => [
                'meta_fields' => $fields,
            ],
        ]);

        $this->assertContainerBuilderHasParameter(
            'netgen_ibexa_search_extra.fulltext.meta_fields',
            $fields,
        );
    }

    public function providerForFulltextMetaFieldsConfigurationInvalidValues(): array
    {
        return [
            [
                [
                    'fulltext' => [
                        'meta_fields' => 12,
                    ],
                ],
                InvalidTypeException::class,
                'Invalid type for path "netgen_ibexa_search_extra.fulltext.meta_fields". Expected "array", but got "int"',
            ],
            [
                [
                    'fulltext' => [
                        'meta_fields' => [
                            12,
                        ],
                    ],
                ],
                InvalidTypeException::class,
                'Invalid type for path "netgen_ibexa_search_extra.fulltext.meta_fields.0". Expected "array", but got "int"',
            ],
            [
                [
                    'fulltext' => [
                        'meta_fields' => [
                            'energia' => 12,
                        ],
                    ],
                ],
                InvalidTypeException::class,
                'Invalid type for path "netgen_ibexa_search_extra.fulltext.meta_fields.energia". Expected "array", but got "int"',
            ],
            [
                [
                    'fulltext' => [
                        'meta_fields' => [
                            'energia' => [
                                12,
                            ],
                        ],
                    ],
                ],
                InvalidConfigurationException::class,
                'Mapped fields must be of string type',
            ],
        ];
    }

    /**
     * @dataProvider providerForFulltextMetaFieldsConfigurationInvalidValues
     */
    public function testFulltextMetaFieldsDefaultConfigurationInvalidValues(array $configuration, string $exceptionFqcn, string $message): void
    {
        $this->expectException($exceptionFqcn);
        $this->expectExceptionMessage($message);

        $this->load($configuration);
    }

    public function providerForPageIndexingConfiguration(): array
    {
        return [
            [
                [
                    'page_indexing' => [
                        'enabled' => true
                    ],
                ],
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
                            'finaweb' => [
                                'tree_root_location_id' => '42',
                            ]
                        ]
                    ],
                ],
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
                            'finaweb' => [
                                'languages_siteaccess_map' => [
                                    'cro-HR' => 'fina_cro'
                                ],
                            ]
                        ]
                    ],
                ],
                null,
                [
                    'cro-HR' => 'fina_cro'
                ],
                null,
                [],
                []
            ],

            [
                [
                    'page_indexing' => [
                        'enabled' => true,
                        'sites' => [
                            'finaweb' => [
                                'host' => 'string'
                            ]
                        ]
                    ],
                ],
                null,
                [],
                'string',
                [],
                []
            ],

            [
                [
                    'page_indexing' => [
                        'enabled' => true,
                        'sites' => [
                            'finaweb' => [
                                'fields' => [
                                    'level1' => [
                                        'h1',
                                        'h2'
                                    ]
                                ],
                            ]
                        ]
                    ],
                ],
                null,
                [],
                null,
                [
                    'level1' => [
                        'h1',
                        'h2'
                    ]
                ],
                []
            ],

            [
                [
                    'page_indexing' => [
                        'enabled' => true,
                        'sites' => [
                            'finaweb' => [
                                'allowed_content_types' => [
                                    'ng_landing_page',
                                    'ng_frontpage'
                                ],
                            ]
                        ]
                    ]
                ],
                null,
                [],
                null,
                [],
                [
                    'ng_landing_page',
                    'ng_frontpage'
                ]
            ],

            [
                [
                    'page_indexing' => [
                        'enabled' => true,
                        'sites' => [
                            'finaweb' => [
                                'tree_root_location_id' => '42',
                                'languages_siteaccess_map' => [
                                    'cro-HR' => 'fina_cro'
                                ],
                                'host' => 'string',
                                'fields' => [
                                    'level1' => [
                                        'h1',
                                        'h2'
                                    ]
                                ],
                                'allowed_content_types' => [
                                    'ng_landing_page',
                                    'ng_frontpage'
                                ],
                            ]
                        ]
                    ],
                ],
                42,
                [
                    'cro-HR' => 'fina_cro'
                ],
                'string',
                [
                    'level1' => [
                        'h1',
                        'h2'
                    ]
                ],
                [
                    'ng_landing_page',
                    'ng_frontpage'
                ]
            ],
        ];
    }


    /**
     * @dataProvider providerForPageIndexingConfiguration
     */
    public function testPageIndexingConfiguration(
        array $configuration,
        ?int $expectedTreeRootLocationId,
        array $expectedLanguagesSiteaccessMap,
        ?string $expectedHost,
        array $expectedFields,
        array $expectedAllowedContentTypes
    ): void {
        $this->load($configuration);

        $this->assertContainerBuilderHasParameter('netgen_ibexa_search_extra.page_indexing.sites');
        $sitesConfig = $this->container->getParameter('netgen_ibexa_search_extra.page_indexing.sites');

        foreach ($sitesConfig as $site => $siteConfig) {
            $this->assertArrayHasKey(
                'tree_root_location_id',
                $siteConfig,
            );
            $this->assertEquals($expectedTreeRootLocationId, $siteConfig['tree_root_location_id']);

            $this->assertArrayHasKey(
                'languages_siteaccess_map',
                $siteConfig,
            );
            $this->assertEquals($expectedLanguagesSiteaccessMap, $siteConfig['languages_siteaccess_map']);

            $this->assertArrayHasKey(
                'fields',
                $siteConfig,
            );
            $this->assertEquals($expectedFields, $siteConfig['fields']);

            $this->assertArrayHasKey(
                'allowed_content_types',
                $siteConfig,
            );
            $this->assertEquals($expectedAllowedContentTypes, $siteConfig['allowed_content_types']);

            $this->assertArrayHasKey(
                'host',
                $siteConfig,
            );
            $this->assertEquals($expectedHost, $siteConfig['host']);

        }
    }


    public function providerForPageIndexingDefaultConfigurationInvalidCases(): array
    {
        return [
            [
                [
                    'page_indexing' => [
                        'finaweb' => [
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
                        'finaweb' => [
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
                        'finaweb' => [
                            'languages_siteaccess_map' => [
                                'cro-HR' => 5
                            ]
                        ],
                    ],
                ],
                InvalidConfigurationException::class,
                'Expected "string", but got "int"',
            ],
            [
                [
                    'page_indexing' => [
                        'finaweb' => [
                            'host' => []
                        ],
                    ],
                ],
                InvalidConfigurationException::class,
                'Expected "string", but got "array"',
            ],
            [
                [
                    'page_indexing' => [
                        'finaweb' => [
                            'config' => [
                                'level1' => 'a'
                            ]
                        ],
                    ],
                ],
                InvalidConfigurationException::class,
                'Expected "array", but got "string"',
            ],
            [
                [
                    'page_indexing' => [
                        'finaweb' => [
                            'config' => [
                                ['h1', 'h2']
                            ]
                        ],
                    ],
                ],
                InvalidConfigurationException::class,
                'Array key (field importance level) must be of string type'
            ],
            [
                [
                    'page_indexing' => [
                        'finaweb' => [
                            'allowed_content_types' => [
                                34,
                                52
                            ],
                        ],
                    ],
                ],
                InvalidConfigurationException::class,
                'Expected "string", but got "int"'
            ],
        ];

    }

    /**
     * @dataProvider providerForPageIndexingDefaultConfigurationInvalidCases
     */
    public function testInvalidPageIndexingDefaultConfiguration(array $siteRootsConfig): void
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
