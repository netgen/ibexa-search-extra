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
                    'rocket' => [
                        'id' => 24,
                        'boost_value' => 2,
                    ],
                    'missile' => [
                        'id' => 42,
                        'boost_value' => 4,
                    ],
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
                                    'rocket' => 22,
                                ],
                            ],
                        ],
                    ],
                ],
                InvalidTypeException::class,
                'Invalid type for path "netgen_ibexa_search_extra.fulltext.boost.kvak_configuration.content_types.rocket". Expected "array", but got "int"',
            ],
            [
                [
                    'fulltext' => [
                        'boost' => [
                            'kvak_configuration' => [
                                'content_types' => [
                                    'rocket' => [
                                        'id' => '24',
                                        'boost_value' => 2,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                InvalidTypeException::class,
                'Invalid type for path "netgen_ibexa_search_extra.fulltext.boost.kvak_configuration.content_types.rocket.id". Expected "int", but got "string"',
            ],
            [
                [
                    'fulltext' => [
                        'boost' => [
                            'kvak_configuration' => [
                                'content_types' => [
                                    'rocket' => [
                                        'id' => 24,
                                        'boost_value' => '2',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                InvalidTypeException::class,
                'Invalid type for path "netgen_ibexa_search_extra.fulltext.boost.kvak_configuration.content_types.rocket.boost_value". Expected "float", but got "string"',
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

    protected function getContainerExtensions(): array
    {
        return [
            new NetgenIbexaSearchExtraExtension(),
        ];
    }
}
