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

    public function providerForSearchBoostDefaultConfiguration(): array
    {
        return [
            [
                [],
            ],
            [
                [
                    'search_boost' => [
                        'content_types' => [],
                        'raw_fields' => [],
                        'meta_fields' => [],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerForSearchBoostDefaultConfiguration
     */
    public function testSearchBoostDefaultConfiguration(array $configuration): void
    {
        $this->load($configuration);

        $this->assertContainerBuilderHasParameter(
            'netgen_ibexa_search_extra.search_boost',
            [
                'content_types' => [],
                'raw_fields' => [],
                'meta_fields' => [],
            ],
        );
    }

    public function testSearchBoostConfigurationValues(): void
    {
        $boost = [
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
        ];

        $this->load([
            'search_boost' => $boost,
        ]);

        $this->assertContainerBuilderHasParameter(
            'netgen_ibexa_search_extra.search_boost',
            $boost,
        );
    }

    public function providerForSearchBoostConfigurationInvalidValues(): array
    {
        return [
            [
                [
                    'search_boost' => 1,
                ],
                InvalidTypeException::class,
                'Invalid type for path "netgen_ibexa_search_extra.search_boost". Expected "array", but got "int"',
            ],
            [
                [
                    'search_boost' => [
                        'content_types' => 11,
                    ],
                ],
                InvalidTypeException::class,
                'Invalid type for path "netgen_ibexa_search_extra.search_boost.content_types". Expected "array", but got "int"',
            ],
            [
                [
                    'search_boost' => [
                        'raw_fields' => 11,
                    ],
                ],
                InvalidTypeException::class,
                'Invalid type for path "netgen_ibexa_search_extra.search_boost.raw_fields". Expected "array", but got "int"',
            ],
            [
                [
                    'search_boost' => [
                        'meta_fields' => 11,
                    ],
                ],
                InvalidTypeException::class,
                'Invalid type for path "netgen_ibexa_search_extra.search_boost.meta_fields". Expected "array", but got "int"',
            ],
            [
                [
                    'search_boost' => [
                        'raw_fields' => [
                            'meta_content__satellite_t' => '2',
                        ],
                    ],
                ],
                InvalidTypeException::class,
                'Invalid type for path "netgen_ibexa_search_extra.search_boost.raw_fields.meta_content__satellite_t". Expected "float", but got "string"',
            ],
            [
                [
                    'search_boost' => [
                        'meta_fields' => [
                            'energia' => '128',
                        ],
                    ],
                ],
                InvalidTypeException::class,
                'Invalid type for path "netgen_ibexa_search_extra.search_boost.meta_fields.energia". Expected "float", but got "string"',
            ],
            [
                [
                    'search_boost' => [
                        'content_types' => [
                            'rocket' => 22,
                        ],
                    ],
                ],
                InvalidTypeException::class,
                'Invalid type for path "netgen_ibexa_search_extra.search_boost.content_types.rocket". Expected "array", but got "int"',
            ],
            [
                [
                    'search_boost' => [
                        'content_types' => [
                            'rocket' => [
                                'id' => '24',
                                'boost_value' => 2,
                            ],
                        ],
                    ],
                ],
                InvalidTypeException::class,
                'Invalid type for path "netgen_ibexa_search_extra.search_boost.content_types.rocket.id". Expected "int", but got "string"',
            ],
            [
                [
                    'search_boost' => [
                        'content_types' => [
                            'rocket' => [
                                'id' => 24,
                                'boost_value' => '2',
                            ],
                        ],
                    ],
                ],
                InvalidTypeException::class,
                'Invalid type for path "netgen_ibexa_search_extra.search_boost.content_types.rocket.boost_value". Expected "float", but got "string"',
            ],
        ];
    }

    /**
     * @dataProvider providerForSearchBoostConfigurationInvalidValues
     */
    public function testSearchBoostConfigurationInvalidValues(array $configuration, string $exceptionFqcn, string $message): void
    {
        $this->expectException($exceptionFqcn);
        $this->expectExceptionMessage($message);

        $this->load($configuration);
    }

    public function providerForCustomFulltextFieldsDefaultConfiguration(): array
    {
        return [
            [
                [],
            ],
            [
                [
                    'field_mapper_custom_fulltext_field_config' => [],
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerForSearchBoostDefaultConfiguration
     */
    public function testCustomFulltextFieldsDefaultConfiguration(array $configuration): void
    {
        $this->load($configuration);

        $this->assertContainerBuilderHasParameter(
            'netgen_ibexa_search_extra.field_mapper_custom_fulltext_field_config',
            [],
        );
    }

    public function testCustomFulltextFieldsConfigurationValues(): void
    {
        $fields = [
            'energia' => [
                'RD-170',
                'RD-0120',
            ],
        ];

        $this->load([
            'field_mapper_custom_fulltext_field_config' => $fields,
        ]);

        $this->assertContainerBuilderHasParameter(
            'netgen_ibexa_search_extra.field_mapper_custom_fulltext_field_config',
            $fields,
        );
    }

    public function providerForCustomFulltextFieldsConfigurationInvalidValues(): array
    {
        return [
            [
                [
                    'field_mapper_custom_fulltext_field_config' => 12,
                ],
                InvalidTypeException::class,
                'Invalid type for path "netgen_ibexa_search_extra.field_mapper_custom_fulltext_field_config". Expected "array", but got "int"',
            ],
            [
                [
                    'field_mapper_custom_fulltext_field_config' => [
                        12,
                    ],
                ],
                InvalidTypeException::class,
                'Invalid type for path "netgen_ibexa_search_extra.field_mapper_custom_fulltext_field_config.0". Expected "array", but got "int"',
            ],
            [
                [
                    'field_mapper_custom_fulltext_field_config' => [
                        'energia' => 12,
                    ],
                ],
                InvalidTypeException::class,
                'Invalid type for path "netgen_ibexa_search_extra.field_mapper_custom_fulltext_field_config.energia". Expected "array", but got "int"',
            ],
            [
                [
                    'field_mapper_custom_fulltext_field_config' => [
                        'energia' => [
                            12,
                        ],
                    ],
                ],
                InvalidConfigurationException::class,
                'Mapped fields must be of string type',
            ],
        ];
    }

    /**
     * @dataProvider providerForCustomFulltextFieldsConfigurationInvalidValues
     */
    public function testCustomFulltextFieldsDefaultConfigurationInvalidValues(array $configuration, string $exceptionFqcn, string $message): void
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
