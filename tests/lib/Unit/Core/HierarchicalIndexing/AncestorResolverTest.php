<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Tests\Unit\Core\HierarchicalIndexing;

use Ibexa\Contracts\Core\Persistence\Content\ContentInfo;
use Ibexa\Contracts\Core\Persistence\Content\Handler as ContentHandlerInterface;
use Ibexa\Contracts\Core\Persistence\Content\Location;
use Ibexa\Contracts\Core\Persistence\Content\Location\Handler as LocationHandlerInterface;
use Ibexa\Contracts\Core\Persistence\Content\Type;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as ContentTypeHandlerInterface;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Netgen\IbexaSearchExtra\Core\Search\Common\Messenger\MessageHandler\Search\DescendantIndexing\AncestorPathGenerator;
use Netgen\IbexaSearchExtra\Core\Search\Common\Messenger\MessageHandler\Search\DescendantIndexing\AncestorResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @group ancestor-resolver
 * @group hierarchical-indexing
 */
final class AncestorResolverTest extends TestCase
{
    public function providerForTestResolveAncestor(): array
    {
        return [
            [
                [
                    'map' => [
                        'cti_1' => [],
                    ],
                ],
                [
                    [
                        'id' => 3,
                        'parentId' => 2,
                        'depth' => 4,
                        'contentId' => 3,
                        'contentTypeId' => 3,
                        'contentTypeIdentifier' => 'cti_3',
                    ],
                ],
                3,
                null,
            ],
            [
                [
                    'map' => [
                        'cti_1' => [
                            'children' => [
                                'cti_2' => [],
                            ],
                        ],
                    ],
                ],
                [
                    [
                        'id' => 2,
                        'parentId' => 1,
                        'depth' => 3,
                        'contentId' => 2,
                        'contentTypeId' => 2,
                        'contentTypeIdentifier' => 'cti_2',
                    ],
                    [
                        'id' => 1,
                        'parentId' => 0,
                        'depth' => 2,
                        'contentId' => 1,
                        'contentTypeId' => 1,
                        'contentTypeIdentifier' => 'cti_1',
                    ],
                ],
                2,
                1,
            ],
            [
                [
                    'map' => [
                        'cti_1' => [
                            'children' => [
                                'cti_2' => [],
                            ],
                        ],
                    ],
                ],
                [
                    [
                        'id' => 2,
                        'parentId' => 1,
                        'depth' => 3,
                        'contentId' => 2,
                        'contentTypeId' => 2,
                        'contentTypeIdentifier' => 'cti_2',
                    ],
                    [
                        'id' => 1,
                        'parentId' => 0,
                        'depth' => 2,
                        'contentId' => 1,
                        'contentTypeId' => 1,
                        'contentTypeIdentifier' => 'cti_5',
                    ],
                ],
                2,
                null,
            ],
            [
                [
                    'map' => [
                        'cti_1' => [
                            'children' => [
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
                    [
                        'id' => 2,
                        'parentId' => 1,
                        'depth' => 3,
                        'contentId' => 2,
                        'contentTypeId' => 2,
                        'contentTypeIdentifier' => 'cti_2',
                    ],
                    [
                        'id' => 1,
                        'parentId' => 0,
                        'depth' => 2,
                        'contentId' => 1,
                        'contentTypeId' => 1,
                        'contentTypeIdentifier' => 'cti_1',
                    ],
                ],
                2,
                1,
            ],
            [
                [
                    'map' => [
                        'cti_1' => [
                            'children' => [
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
                    [
                        'id' => 3,
                        'parentId' => 2,
                        'depth' => 4,
                        'contentId' => 3,
                        'contentTypeId' => 3,
                        'contentTypeIdentifier' => 'cti_3',
                    ],
                    [
                        'id' => 2,
                        'parentId' => 1,
                        'depth' => 3,
                        'contentId' => 2,
                        'contentTypeId' => 2,
                        'contentTypeIdentifier' => 'cti_2',
                    ],
                    [
                        'id' => 1,
                        'parentId' => 0,
                        'depth' => 2,
                        'contentId' => 1,
                        'contentTypeId' => 1,
                        'contentTypeIdentifier' => 'cti_1',
                    ],
                ],
                3,
                1,
            ],
        ];
    }

    /**
     * @dataProvider providerForTestResolveAncestor
     */
    public function testResolveAncestor(
        array $configuration,
        array $locationRepositoryData,
        int $initialLocationId,
        ?int $expectedAncestor,
    ): void {
        $resolver = $this->getAncestorResolverUnderTest($configuration, $locationRepositoryData);

        $locationStub = $this->getLocationStubFromRepositoryData($initialLocationId, $locationRepositoryData);

        $actualAncestor = $resolver->resolveAncestor($locationStub);

        if ($expectedAncestor === null) {
            $this->assertNull($actualAncestor);

            return;
        }

        $this->assertInstanceOf(Location::class, $actualAncestor);
        $this->assertSame($expectedAncestor, $actualAncestor->id);
    }

    protected function getAncestorResolverUnderTest(array $configuration, array $locationRepositoryData): AncestorResolver
    {
        return new AncestorResolver(
            $this->getContentHandlerMock($locationRepositoryData),
            $this->getContentTypeHandlerMock($locationRepositoryData),
            $this->getLocationHandlerMock($locationRepositoryData),
            new AncestorPathGenerator($configuration),
        );
    }

    protected function getContentHandlerMock(array $locationRepositoryData): ContentHandlerInterface|MockObject
    {
        $mock = $this->getMockBuilder(ContentHandlerInterface::class)->getMock();
        $contentIdToContentTypeIdMap = [];

        foreach ($locationRepositoryData as $data) {
            $contentIdToContentTypeIdMap[$data['contentId']] = $data['contentTypeId'];
        }

        $mock->method('loadContentInfo')->willReturnCallback(
            function ($id) use ($contentIdToContentTypeIdMap) {
                foreach ($contentIdToContentTypeIdMap as $contentId => $contentTypeId) {
                    if ($id === $contentId) {
                        return new ContentInfo([
                            'contentTypeId' => $contentTypeId,
                        ]);
                    }
                }

                throw new NotFoundException('ContentInfo', $id);
            }
        );

        return $mock;
    }

    protected function getContentTypeHandlerMock(array $locationRepositoryData): ContentTypeHandlerInterface|MockObject
    {
        $mock = $this->getMockBuilder(ContentTypeHandlerInterface::class)->getMock();
        $contentTypeIdToContentTypeIdentifierMap = [];

        foreach ($locationRepositoryData as $data) {
            $contentTypeIdToContentTypeIdentifierMap[$data['contentTypeId']] = $data['contentTypeIdentifier'];
        }

        $mock->method('load')->willReturnCallback(
            function ($id) use ($contentTypeIdToContentTypeIdentifierMap) {
                foreach ($contentTypeIdToContentTypeIdentifierMap as $contentTypeId => $contentTypeIdentifier) {
                    if ($id === $contentTypeId) {
                        return new Type([
                            'identifier' => $contentTypeIdentifier,
                        ]);
                    }
                }

                throw new NotFoundException('ContentType', $id);
            }
        );

        return $mock;
    }

    protected function getLocationHandlerMock(array $locationRepositoryData): LocationHandlerInterface|MockObject
    {
        $mock = $this->getMockBuilder(LocationHandlerInterface::class)->getMock();

        $mock->method('load')->willReturnCallback(
            function ($id) use ($locationRepositoryData) {
                foreach ($locationRepositoryData as $data) {
                    if ($id === $data['id']) {
                        return $this->getLocationStub($data);
                    }
                }

                throw new NotFoundException('Location', $id);
            }
        );

        return $mock;
    }

    protected function getLocationStubFromRepositoryData(int $id, array $locationRepositoryData): Location
    {
        foreach ($locationRepositoryData as $data) {
            if ($id === $data['id']) {
                return $this->getLocationStub($data);
            }
        }

        throw new RuntimeException(
            sprintf(
                'Missing Location #%s data',
                $id,
            ),
        );
    }

    protected function getLocationStub(array $locationData): Location
    {
        return new Location([
            'id' => $locationData['id'],
            'parentId' => $locationData['parentId'],
            'depth' => $locationData['depth'],
            'contentId' => $locationData['contentId'],
        ]);
    }
}
