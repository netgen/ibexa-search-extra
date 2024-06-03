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

    public function providerForTestHierarchicalIndexingConfiguration(): array
    {
        return [
            [
                [
                    'hierarchical_indexing' => false,
                ],
                false,
            ],
            [
                [
                    'hierarchical_indexing' => [],
                ],
                true,
            ],
            [
                [
                    'hierarchical_indexing' => [
                        'descendant_indexing' => false,
                    ],
                ],
                false,
            ],
            [
                [
                    'hierarchical_indexing' => [
                        'descendant_indexing' => [],
                    ],
                ],
                true,
            ],
            [
                [
                    'hierarchical_indexing' => [
                        'mogoruš' => [],
                    ],
                ],
                false,
            ],
            [
                [
                    'hierarchical_indexing' => [
                        'descendant_indexing' => [
                            'enabled' => 'yes',
                        ],
                    ],
                ],
                false,
            ],
            [
                [
                    'hierarchical_indexing' => [
                        'descendant_indexing' => [
                            'enabled' => true,
                        ],
                    ],
                ],
                true,
            ],
            [
                [
                    'hierarchical_indexing' => [
                        'descendant_indexing' => [
                            'mogoruš' => true,
                        ],
                    ],
                ],
                false,
            ],
            [
                [
                    'hierarchical_indexing' => [
                        'descendant_indexing' => [
                            'enabled' => false,
                            'map' => 1,
                        ],
                    ],
                ],
                false,
            ],
            [
                [
                    'hierarchical_indexing' => [
                        'descendant_indexing' => [
                            'enabled' => false,
                            'map' => [],
                        ],
                    ],
                ],
                true,
            ],
            [
                [
                    'hierarchical_indexing' => [
                        'descendant_indexing' => [
                            'enabled' => false,
                            'mogoruš' => [],
                        ],
                    ],
                ],
                false,
            ],
            [
                [
                    'hierarchical_indexing' => [
                        'descendant_indexing' => [
                            'enabled' => false,
                            'map' => [
                                'content_type_identifier' => 2,
                            ],
                        ],
                    ],
                ],
                false,
            ],
            [
                [
                    'hierarchical_indexing' => [
                        'descendant_indexing' => [
                            'enabled' => false,
                            'map' => [
                                'content_type_identifier' => [],
                            ],
                        ],
                    ],
                ],
                true,
            ],
            [
                [
                    'hierarchical_indexing' => [
                        'descendant_indexing' => [
                            'enabled' => false,
                            'map' => [
                                'content_type_identifier' => [
                                    'children' => 3,
                                ],
                            ],
                        ],
                    ],
                ],
                false,
            ],
            [
                [
                    'hierarchical_indexing' => [
                        'descendant_indexing' => [
                            'enabled' => false,
                            'map' => [
                                'content_type_identifier' => [
                                    'mogoruš' => 3,
                                ],
                            ],
                        ],
                    ],
                ],
                false,
            ],
            [
                [
                    'hierarchical_indexing' => [
                        'descendant_indexing' => [
                            'enabled' => false,
                            'map' => [
                                'content_type_identifier' => [
                                    'children' => [],
                                ],
                            ],
                        ],
                    ],
                ],
                true,
            ],
            [
                [
                    'hierarchical_indexing' => [
                        'descendant_indexing' => [
                            'enabled' => false,
                            'map' => [
                                'content_type_identifier' => [
                                    'handlers' => false,
                                    'children' => [],
                                ],
                            ],
                        ],
                    ],
                ],
                false,
            ],
            [
                [
                    'hierarchical_indexing' => [
                        'descendant_indexing' => [
                            'enabled' => false,
                            'map' => [
                                'content_type_identifier' => [
                                    'handlers' => [],
                                    'children' => [],
                                ],
                            ],
                        ],
                    ],
                ],
                true,
            ],
            [
                [
                    'hierarchical_indexing' => [
                        'descendant_indexing' => [
                            'enabled' => false,
                            'map' => [
                                'content_type_identifier' => [
                                    'handlers' => [
                                        false,
                                    ],
                                    'children' => [],
                                ],
                            ],
                        ],
                    ],
                ],
                false,
            ],
            [
                [
                    'hierarchical_indexing' => [
                        'descendant_indexing' => [
                            'enabled' => false,
                            'map' => [
                                'content_type_identifier' => [
                                    'handlers' => [
                                        'handler_identifier_1',
                                    ],
                                    'children' => [],
                                ],
                            ],
                        ],
                    ],
                ],
                true,
            ],
            [
                [
                    'hierarchical_indexing' => [
                        'descendant_indexing' => [
                            'enabled' => false,
                            'map' => [
                                'content_type_identifier' => [
                                    'handlers' => [
                                        'handler_identifier_1',
                                        'handler_identifier_2',
                                    ],
                                    'children' => [],
                                ],
                            ],
                        ],
                    ],
                ],
                true,
            ],
            [
                [
                    'hierarchical_indexing' => [
                        'descendant_indexing' => [
                            'enabled' => false,
                            'map' => [
                                'content_type_identifier' => [
                                    'handlers' => [
                                        'handler_identifier_1',
                                        'handler_identifier_2',
                                    ],
                                    'children' => [
                                        'content_type_identifier' => 4,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                false,
            ],
            [
                [
                    'hierarchical_indexing' => [
                        'descendant_indexing' => [
                            'enabled' => false,
                            'map' => [
                                'content_type_identifier' => [
                                    'handlers' => [
                                        'handler_identifier_1',
                                        'handler_identifier_2',
                                    ],
                                    'children' => [
                                        'content_type_identifier' => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                true,
            ],
            [
                [
                    'hierarchical_indexing' => [
                        'descendant_indexing' => [
                            'enabled' => false,
                            'map' => [
                                'content_type_identifier' => [
                                    'handlers' => [
                                        'handler_identifier_1',
                                        'handler_identifier_2',
                                    ],
                                    'children' => [
                                        'content_type_identifier' => [
                                            'indexed' => [],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                false,
            ],
            [
                [
                    'hierarchical_indexing' => [
                        'descendant_indexing' => [
                            'enabled' => false,
                            'map' => [
                                'content_type_identifier' => [
                                    'handlers' => [
                                        'handler_identifier_1',
                                        'handler_identifier_2',
                                    ],
                                    'children' => [
                                        'content_type_identifier' => [
                                            'mogoruš' => [],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                false,
            ],
            [
                [
                    'hierarchical_indexing' => [
                        'descendant_indexing' => [
                            'enabled' => false,
                            'map' => [
                                'content_type_identifier' => [
                                    'handlers' => [
                                        'handler_identifier_1',
                                        'handler_identifier_2',
                                    ],
                                    'children' => [
                                        'content_type_identifier' => [
                                            'indexed' => false,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                true,
            ],
            [
                [
                    'hierarchical_indexing' => [
                        'descendant_indexing' => [
                            'enabled' => false,
                            'map' => [
                                'content_type_identifier' => [
                                    'handlers' => [
                                        'handler_identifier_1',
                                        'handler_identifier_2',
                                    ],
                                    'children' => [
                                        'content_type_identifier' => [
                                            'indexed' => true,
                                            'children' => 'many',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                false,
            ],
            [
                [
                    'hierarchical_indexing' => [
                        'descendant_indexing' => [
                            'enabled' => false,
                            'map' => [
                                'content_type_identifier' => [
                                    'handlers' => [
                                        'handler_identifier_1',
                                        'handler_identifier_2',
                                    ],
                                    'children' => [
                                        'content_type_identifier' => [
                                            'indexed' => true,
                                            'children' => [],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                true,
            ],
            [
                [
                    'hierarchical_indexing' => [
                        'descendant_indexing' => [
                            'enabled' => false,
                            'map' => [
                                'content_type_identifier' => [
                                    'handlers' => [
                                        'handler_identifier_1',
                                        'handler_identifier_2',
                                    ],
                                    'children' => [
                                        'content_type_identifier' => [
                                            'indexed' => true,
                                            'children' => [
                                                'content_type_identifier' => 7,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                false,
            ],
            [
                [
                    'hierarchical_indexing' => [
                        'descendant_indexing' => [
                            'enabled' => false,
                            'map' => [
                                'content_type_identifier' => [
                                    'handlers' => [
                                        'handler_identifier_1',
                                        'handler_identifier_2',
                                    ],
                                    'children' => [
                                        'content_type_identifier' => [
                                            'indexed' => true,
                                            'children' => [
                                                'content_type_identifier' => [],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                true,
            ],
            [
                [
                    'hierarchical_indexing' => [
                        'descendant_indexing' => [
                            'enabled' => false,
                            'map' => [
                                'content_type_identifier' => [
                                    'handlers' => [
                                        'handler_identifier_1',
                                        'handler_identifier_2',
                                    ],
                                    'children' => [
                                        'content_type_identifier' => [
                                            'indexed' => true,
                                            'children' => [
                                                'content_type_identifier' => [
                                                    'indexed' => true,
                                                    'children' => 8,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                false,
            ],
            [
                [
                    'hierarchical_indexing' => [
                        'descendant_indexing' => [
                            'enabled' => false,
                            'map' => [
                                'content_type_identifier' => [
                                    'handlers' => [
                                        'handler_identifier_1',
                                        'handler_identifier_2',
                                    ],
                                    'children' => [
                                        'content_type_identifier' => [
                                            'handlers' => [],
                                            'indexed' => true,
                                            'children' => [
                                                'content_type_identifier' => [
                                                    'indexed' => true,
                                                    'children' => [],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                false,
            ],
            [
                [
                    'hierarchical_indexing' => [
                        'descendant_indexing' => [
                            'enabled' => false,
                            'map' => [
                                'content_type_identifier' => [
                                    'handlers' => [
                                        'handler_identifier_1',
                                        'handler_identifier_2',
                                    ],
                                    'children' => [
                                        'content_type_identifier' => [
                                            'indexed' => true,
                                            'children' => [
                                                'content_type_identifier' => [
                                                    'handlers' => [],
                                                    'indexed' => true,
                                                    'children' => [],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                false,
            ],
            [
                [
                    'hierarchical_indexing' => [
                        'descendant_indexing' => [
                            'enabled' => false,
                            'map' => [
                                'content_type_identifier' => [
                                    'handlers' => [
                                        'handler_identifier_1',
                                        'handler_identifier_2',
                                    ],
                                    'children' => [
                                        'content_type_identifier' => [
                                            'indexed' => true,
                                            'children' => [
                                                'content_type_identifier' => [
                                                    'indexed' => true,
                                                    'children' => [],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                true,
            ],
        ];
    }

    /**
     * @dataProvider providerForTestHierarchicalIndexingConfiguration
     */
    public function testHierarchicalIndexingInvalidConfiguration(array $configuration, bool $valid): void
    {
        if (!$valid) {
            $this->expectException(InvalidConfigurationException::class);
        } else {
            $this->addToAssertionCount(1);
        }

        $this->load($configuration);
    }

    protected function getContainerExtensions(): array
    {
        return [
            new NetgenIbexaSearchExtraExtension(),
        ];
    }
}
