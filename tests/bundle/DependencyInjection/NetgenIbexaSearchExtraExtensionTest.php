<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtraBundle\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Netgen\Bundle\IbexaSearchExtraBundle\DependencyInjection\NetgenIbexaSearchExtraExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;


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
