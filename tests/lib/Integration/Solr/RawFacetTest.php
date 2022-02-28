<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Tests\Integration\Solr;

use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\ContentTypeIdentifier;
use Ibexa\Tests\Integration\Core\Repository\BaseTest;
use Netgen\IbexaSearchExtra\API\Values\Content\Query\FacetBuilder\CustomFieldFacetBuilder;
use Netgen\IbexaSearchExtra\API\Values\Content\Search\Facet\CustomFieldFacet;
use Netgen\IbexaSearchExtra\Core\Search\Solr\API\Facet\RawFacet;
use Netgen\IbexaSearchExtra\Core\Search\Solr\API\FacetBuilder\RawFacetBuilder;
use function json_decode;
use function json_encode;
use function reset;
use const JSON_THROW_ON_ERROR;

class RawFacetTest extends BaseTest
{
    /**
     * @throws \JsonException
     */
    public function providerForTestFind(): array
    {
        return [
            [
                new LocationQuery([
                    'facetBuilders' => [
                        new RawFacetBuilder([
                            'name' => 'test_facet',
                            'parameters' => [
                                'type' => 'terms',
                                'field' => 'toaster_price_value_i',
                                'allBuckets' => true,
                                'sort' => 'count desc',
                                'facet' => [
                                    'average' => 'avg(toaster_price_value_i)',
                                    'sum' => 'sum(toaster_price_value_i)',
                                ],
                            ],
                        ]),
                    ],
                    'filter' => new ContentTypeIdentifier('toaster'),
                ]),
                [
                    new RawFacet([
                        'name' => 'test_facet',
                        'data' => json_decode(
                            json_encode(
                                [
                                    'allBuckets' => [
                                        'count' => 10,
                                        'average' => 30.5,
                                        'sum' => 305,
                                    ],
                                    'buckets' => [
                                        [
                                            'val' => 55,
                                            'count' => 4,
                                            'average' => 55,
                                            'sum' => 220,
                                        ],
                                        [
                                            'val' => 20,
                                            'count' => 3,
                                            'average' => 20,
                                            'sum' => 60,
                                        ],
                                        [
                                            'val' => 10,
                                            'count' => 2,
                                            'average' => 10,
                                            'sum' => 20,
                                        ],
                                        [
                                            'val' => 5,
                                            'count' => 1,
                                            'average' => 5,
                                            'sum' => 5,
                                        ],
                                    ],
                                ],
                                JSON_THROW_ON_ERROR,
                            ),
                            false,
                            512,
                            JSON_THROW_ON_ERROR,
                        ),
                    ]),
                ],
            ],
            [
                new LocationQuery([
                    'facetBuilders' => [
                        new CustomFieldFacetBuilder([
                            'fieldName' => 'toaster_price_value_i',
                            'limit' => 10,
                            'minCount' => 1,
                            'name' => 'test_facet',
                            'sort' => CustomFieldFacetBuilder::COUNT_DESC,
                        ]),
                        new RawFacetBuilder([
                            'name' => 'test_facet2',
                            'parameters' => [
                                'type' => 'terms',
                                'field' => 'toaster_price_value_i',
                                'allBuckets' => true,
                                'sort' => 'count desc',
                                'limit' => 0,
                                'facet' => [
                                    'average' => 'avg(toaster_price_value_i)',
                                    'sum' => 'sum(toaster_price_value_i)',
                                ],
                            ],
                        ]),
                    ],
                    'filter' => new ContentTypeIdentifier('toaster'),
                ]),
                [
                    new CustomFieldFacet([
                        'entries' => [
                            5 => 1,
                            10 => 2,
                            20 => 3,
                            55 => 4,
                        ],
                        'name' => 'test_facet',
                    ]),
                    new RawFacet([
                        'name' => 'test_facet2',
                        'data' => json_decode(
                            json_encode(
                                [
                                    'allBuckets' => [
                                        'count' => 10,
                                        'average' => 30.5,
                                        'sum' => 305,
                                    ],
                                    'buckets' => [],
                                ],
                                JSON_THROW_ON_ERROR,
                            ),
                            false,
                            512,
                            JSON_THROW_ON_ERROR,
                        ),
                    ]),
                ],
            ],
            [
                new LocationQuery([
                    'facetBuilders' => [
                        new RawFacetBuilder([
                            'name' => 'test_facet',
                            'parameters' => [
                                'type' => 'terms',
                                'field' => 'toaster_price_value_i',
                                'allBuckets' => true,
                                'limit' => 0,
                                'facet' => [
                                    'average' => 'avg(toaster_price_value_i)',
                                    'sum' => 'sum(toaster_price_value_i)',
                                    'maximum' => 'max(toaster_price_value_i)',
                                    'minimum' => 'min(toaster_price_value_i)',
                                ],
                            ],
                        ]),
                    ],
                    'filter' => new ContentTypeIdentifier('toaster'),
                ]),
                [
                    new RawFacet([
                        'name' => 'test_facet',
                        'data' => json_decode(
                            json_encode(
                                [
                                    'allBuckets' => [
                                        'count' => 10,
                                        'average' => 30.5,
                                        'sum' => 305,
                                        'maximum' => 55,
                                        'minimum' => 5,
                                    ],
                                    'buckets' => [],
                                ],
                                JSON_THROW_ON_ERROR,
                            ),
                            false,
                            512,
                            JSON_THROW_ON_ERROR,
                        ),
                    ]),
                ],
            ],
            [
                new LocationQuery([
                    'facetBuilders' => [
                        new RawFacetBuilder([
                            'name' => 'test_facet',
                            'parameters' => [
                                'type' => 'terms',
                                'field' => 'toaster_price_value_i',
                                'allBuckets' => true,
                                'limit' => 0,
                                'facet' => [
                                    'maximum' => 'max(toaster_price_value_i)',
                                ],
                            ],
                        ]),
                        new RawFacetBuilder([
                            'name' => 'test_facet',
                            'parameters' => [
                                'type' => 'terms',
                                'field' => 'toaster_price_value_i',
                                'allBuckets' => true,
                                'facet' => [
                                    'name' => [
                                        'type' => 'terms',
                                        'field' => 'content_name_s',
                                    ],
                                ],
                            ],
                        ]),
                    ],
                    'filter' => new ContentTypeIdentifier('toaster'),
                ]),
                [
                    new RawFacet([
                        'name' => 'test_facet',
                        'data' => json_decode(
                            json_encode(
                                [
                                    'allBuckets' => [
                                        'count' => 10,
                                        'maximum' => 55,
                                    ],
                                    'buckets' => [],
                                ],
                                JSON_THROW_ON_ERROR,
                            ),
                            false,
                            512,
                            JSON_THROW_ON_ERROR,
                        ),
                    ]),
                    new RawFacet([
                        'name' => 'test_facet',
                        'data' => json_decode(
                            json_encode(
                                [
                                    'allBuckets' => [
                                        'count' => 10,
                                    ],
                                    'buckets' => [
                                        [
                                            'val' => 55,
                                            'count' => 4,
                                            'name' => [
                                                'buckets' => [
                                                    [
                                                        'val' => '55',
                                                        'count' => 4,
                                                    ],
                                                ],
                                            ],
                                        ],
                                        [
                                            'val' => 20,
                                            'count' => 3,
                                            'name' => [
                                                'buckets' => [
                                                    [
                                                        'val' => '20',
                                                        'count' => 3,
                                                    ],
                                                ],
                                            ],
                                        ],
                                        [
                                            'val' => 10,
                                            'count' => 2,
                                            'name' => [
                                                'buckets' => [
                                                    [
                                                        'val' => '10',
                                                        'count' => 2,
                                                    ],
                                                ],
                                            ],
                                        ],
                                        [
                                            'val' => 5,
                                            'count' => 1,
                                            'name' => [
                                                'buckets' => [
                                                    [
                                                        'val' => '5',
                                                        'count' => 1,
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                                JSON_THROW_ON_ERROR,
                            ),
                            false,
                            512,
                            JSON_THROW_ON_ERROR,
                        ),
                    ]),
                ],
            ],
        ];
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testPrepareTestFixtures(): void
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();
        $contentTypeService = $repository->getContentTypeService();
        $locationService = $repository->getLocationService();

        $contentTypeGroups = $contentTypeService->loadContentTypeGroups();
        $contentTypeCreateStruct = $contentTypeService->newContentTypeCreateStruct('toaster');
        $contentTypeCreateStruct->mainLanguageCode = 'eng-GB';
        $contentTypeCreateStruct->names = ['eng-GB' => 'Toaster'];
        $fieldDefinitionCreateStruct = $contentTypeService->newFieldDefinitionCreateStruct('price', 'ezinteger');
        $contentTypeCreateStruct->addFieldDefinition($fieldDefinitionCreateStruct);
        $contentTypeDraft = $contentTypeService->createContentType($contentTypeCreateStruct, [reset($contentTypeGroups)]);
        $contentTypeService->publishContentTypeDraft($contentTypeDraft);
        $contentType = $contentTypeService->loadContentTypeByIdentifier('toaster');

        $values = [
            5,
            10,
            10,
            20,
            20,
            20,
            55,
            55,
            55,
            55,
        ];

        $locationCreateStruct = $locationService->newLocationCreateStruct(2);

        foreach ($values as $value) {
            $contentCreateStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
            $contentCreateStruct->setField('price', $value);
            $contentDraft = $contentService->createContent($contentCreateStruct, [$locationCreateStruct]);
            $contentService->publishVersion($contentDraft->versionInfo);
        }

        $this->refreshSearch($repository);

        self::assertTrue(true);
    }

    /**
     * @dataProvider providerForTestFind
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query $query
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Search\Facet[] $expectedFacets
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testFindContent(Query $query, array $expectedFacets): void
    {
        $searchService = $this->getSearchService(false);

        $searchResult = $searchService->findContentInfo($query);

        self::assertEquals($expectedFacets, $searchResult->facets);
    }

    /**
     * @dataProvider providerForTestFind
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery $query
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Search\Facet[] $expectedFacets
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testFindLocations(LocationQuery $query, array $expectedFacets): void
    {
        $searchService = $this->getSearchService(false);

        $searchResult = $searchService->findLocations($query);

        self::assertEquals($expectedFacets, $searchResult->facets);
    }

    protected function getSearchService($initialInitializeFromScratch = true): SearchService
    {
        return $this->getRepository($initialInitializeFromScratch)->getSearchService();
    }
}
