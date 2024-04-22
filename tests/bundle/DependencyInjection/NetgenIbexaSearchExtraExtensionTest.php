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
                    'page_indexing' => [],
                ],
                [],
                [],
                null,
                [],
                [],
            ],
            [
                [
                    'page_indexing' => [
                        'site_roots' => [
                            'finaweb' => '42',
                        ],
                    ],
                ],
                [
                    'finaweb' => 42,
                ],
                [],
                null,
                [],
                [],
            ],
            [
                [
                    'page_indexing' => [
                        'languages_siteaccess_map' => [
                            'finaweb' => [
                                'cro-HR' => 'fina_cro'
                            ]
                        ],
                    ],
                ],
                [],
                [
                    'finaweb' => [
                        'cro-HR' => 'fina_cro'
                    ]
                ],
                null,
                [],
                []
            ],

            [
                [
                    'page_indexing' => [
                        'host' => 'string'
                    ],
                ],
                [],
                [],
                'string',
                [],
                []
            ],

            [
                [
                    'page_indexing' => [
                        'config' => [
                            'level1' => [
                                'h1',
                                'h2'
                            ]
                        ],
                    ],
                ],
                [],
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
                    'page_indexing' =>[
                        'allowed_content_types' => [
                            'ng_landing_page',
                            'ng_frontpage'
                        ],
                    ]
                ],
                [],
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
                        'site_roots' => [
                            'finaweb' => '42',
                        ],
                        'languages_siteaccess_map' => [
                            'finaweb' => [
                                'cro-HR' => 'fina_cro'
                            ]
                        ],
                        'host' => 'string',
                        'config' => [
                            'level1' => [
                                'h1',
                                'h2'
                            ]
                        ],
                        'allowed_content_types' => [
                            'ng_landing_page',
                            'ng_frontpage'
                        ],
                    ],
                ],
                [
                    'finaweb' => 42,
                ],
                [
                    'finaweb' => [
                        'cro-HR' => 'fina_cro'
                    ]
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
        array $expectedSiteRoots,
        array $expectedLanguagesSiteaccessMap,
        ?string $expectedHost,
        array $expectedConfig,
        array $expectedAllowedContentTypes
    ): void {
        $this->load($configuration);

        $this->assertContainerBuilderHasParameter(
            'netgen_ibexa_search_extra.page_indexing.site_roots',
            $expectedSiteRoots,

        );
        $this->assertContainerBuilderHasParameter(
            'netgen_ibexa_search_extra.page_indexing.languages_siteaccess_map',
            $expectedLanguagesSiteaccessMap,
        );

        $this->assertContainerBuilderHasParameter(
            'netgen_ibexa_search_extra.page_indexing.host',
            $expectedHost,
        );

        $this->assertContainerBuilderHasParameter(
            'netgen_ibexa_search_extra.page_indexing.config',
            $expectedConfig,
        );

        $this->assertContainerBuilderHasParameter(
            'netgen_ibexa_search_extra.page_indexing.allowed_content_types',
            $expectedAllowedContentTypes
        );
    }


    public function providerForPageIndexingDefaultConfigurationInvalidCases(): array
    {
        return [
            [
                [
                    'page_indexing' => [
                        'site_roots' => [
                            'finaweb' => [],
                        ]
                    ],
                ],
                InvalidConfigurationException::class,
                'Expected "int", but got "array"',
            ],
            [
                [
                    'page_indexing' => [
                        'site_roots' => [
                            'finaweb' => true,
                        ]
                    ],
                ],
                InvalidConfigurationException::class,
                'Expected "int", but got "bool"',
            ],
            [
                [
                    'page_indexing' => [
                        'languages_siteaccess_map' => [
                            'finaweb' => [
                                'cro-HR' => 5
                            ]
                        ]
                    ],
                ],
                InvalidConfigurationException::class,
                'Expected "string", but got "int"',
            ],
            [
                [
                    'page_indexing' => [
                        'host' => []
                    ],
                ],
                InvalidConfigurationException::class,
                'Expected "string", but got "array"',
            ],
            [
                [
                    'page_indexing' => [
                        'config' => [
                            'level1' => 'a'
                        ]
                    ],
                ],
                InvalidConfigurationException::class,
                'Expected "array", but got "string"',
            ],
            [
                [
                    'page_indexing' => [
                        'config' => [
                           ['h1', 'h2']
                        ]
                    ],
                ],
                InvalidConfigurationException::class,
                'Array key (field importance level) must be of string type'
            ],
            [
                [
                    'page_indexing' => [
                        'allowed_content_types' => [
                            34,
                            52
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
