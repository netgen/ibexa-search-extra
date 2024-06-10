<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Tests\Unit\Core\HierarchicalIndexing;

use Netgen\IbexaSearchExtra\Core\Search\Common\Messenger\MessageHandler\Search\DescendantIndexing\AncestorPathGenerator;
use PHPUnit\Framework\TestCase;

/**
 * @group hierarchical-indexing
 */
final class AncestorPathGeneratorTest extends TestCase
{
    public function providerForTestGetPaths(): array
    {
        return [
            [
                [],
                [],
            ],
            [
                [
                    'enabled' => true,
                ],
                [],
            ],
            [
                [
                    'enabled' => true,
                    'map' => [],
                ],
                [],
            ],
            [
                [
                    'enabled' => true,
                    'map' => [
                        'cti_1' => [],
                    ],
                ],
                [],
            ],
            [
                [
                    'enabled' => true,
                    'map' => [
                        'cti_1' => [
                            'children' => [
                                'indexed' => true,
                                'cti_2' => [],
                            ],
                        ],
                    ],
                ],
                [
                    'cti_2/cti_1',
                ],
            ],
            [
                [
                    'enabled' => true,
                    'map' => [
                        'cti_1' => [
                            'children' => [
                                'indexed' => false,
                                'cti_2' => [],
                            ],
                        ],
                    ],
                ],
                [
                    'cti_2/cti_1',
                ],
            ],
            [
                [
                    'enabled' => true,
                    'map' => [
                        'cti_1' => [
                            'children' => [
                                'indexed' => false,
                                'cti_2' => [
                                    'children' => [
                                        'indexed' => false,
                                        'cti_3' => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'cti_3/cti_2/cti_1',
                    'cti_2/cti_1',
                ],
            ],
            [
                [
                    'enabled' => true,
                    'map' => [
                        'cti_1' => [
                            'children' => [
                                'indexed' => false,
                                'cti_2' => [
                                    'children' => [
                                        'indexed' => false,
                                        'cti_3' => [
                                            'children' => [
                                                'cti_4' => []
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'cti_4/cti_3/cti_2/cti_1',
                    'cti_3/cti_2/cti_1',
                    'cti_2/cti_1',
                ],
            ],
            [
                [
                    'map' => [
                        'cti_1' => [
                            'children' => [
                                'cti_2' => [
                                    'children' => [
                                        'indexed' => false,
                                        'cti_3' => [],
                                    ],
                                ],
                            ],
                        ],
                        'cti_4' => [
                            'children' => [
                                'indexed' => false,
                                'cti_2' => [
                                    'children' => [
                                        'cti_3' => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'cti_3/cti_2/cti_1',
                    'cti_2/cti_1',
                    'cti_3/cti_2/cti_4',
                    'cti_2/cti_4',
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerForTestGetPaths
     */
    public function testGetPaths(array $configuration, array $expectedPaths): void
    {
        $generator = $this->getAncestorPathGeneratorUnderTest($configuration);

        $actualPaths = $generator->getPaths();

        self::assertSame($expectedPaths, $actualPaths);
    }

    protected function getAncestorPathGeneratorUnderTest(array $configuration): AncestorPathGenerator
    {
        return new AncestorPathGenerator($configuration);
    }
}
