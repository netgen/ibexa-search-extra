<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Tests\Integration\API;

use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\ContentTypeIdentifier;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause\ContentId as ContentIdSortClause;
use Netgen\IbexaSearchExtra\API\Values\Content\Query\FacetBuilder\CustomFieldFacetBuilder;
use Netgen\IbexaSearchExtra\API\Values\Content\Search\Facet\CustomFieldFacet;
use function reset;

class CustomFieldFacetTest extends BaseTest
{
    public function providerForTestFind(): array
    {
        return [
            [
                new LocationQuery([
                    'facetBuilders' => [
                        new CustomFieldFacetBuilder([
                            'fieldName' => 'forest_tree_value_s',
                            'limit' => 10,
                            'minCount' => 1,
                            'name' => 'test_facet',
                            'sort' => CustomFieldFacetBuilder::COUNT_DESC,
                        ]),
                    ],
                    'filter' => new ContentTypeIdentifier('forest'),
                    'sortClauses' => [new ContentIdSortClause()],
                ]),
                [
                    new CustomFieldFacet([
                        'entries' => [
                            'hrast' => 4,
                            'lipa' => 3,
                            'grab' => 2,
                            'jasen' => 2,
                            'jela' => 1,
                            'smreka' => 1,
                        ],
                        'name' => 'test_facet',
                    ]),
                ],
            ],
            [
                new LocationQuery([
                    'facetBuilders' => [
                        new CustomFieldFacetBuilder([
                            'fieldName' => 'forest_tree_value_s',
                            'limit' => 10,
                            'minCount' => 1,
                            'name' => 'test_facet',
                            'sort' => CustomFieldFacetBuilder::TERM_ASC,
                        ]),
                    ],
                    'filter' => new ContentTypeIdentifier('forest'),
                    'sortClauses' => [new ContentIdSortClause()],
                ]),
                [
                    new CustomFieldFacet([
                        'entries' => [
                            'grab' => 2,
                            'hrast' => 4,
                            'jasen' => 2,
                            'jela' => 1,
                            'lipa' => 3,
                            'smreka' => 1,
                        ],
                        'name' => 'test_facet',
                    ]),
                ],
            ],
            [
                new LocationQuery([
                    'facetBuilders' => [
                        new CustomFieldFacetBuilder([
                            'fieldName' => 'forest_tree_value_s',
                            'limit' => 4,
                            'minCount' => 1,
                            'name' => 'test_facet',
                            'sort' => CustomFieldFacetBuilder::COUNT_DESC,
                        ]),
                    ],
                    'filter' => new ContentTypeIdentifier('forest'),
                    'sortClauses' => [new ContentIdSortClause()],
                ]),
                [
                    new CustomFieldFacet([
                        'entries' => [
                            'hrast' => 4,
                            'lipa' => 3,
                            'grab' => 2,
                            'jasen' => 2,
                        ],
                        'name' => 'test_facet',
                    ]),
                ],
            ],
            [
                new LocationQuery([
                    'facetBuilders' => [
                        new CustomFieldFacetBuilder([
                            'fieldName' => 'forest_tree_value_s',
                            'limit' => 5,
                            'minCount' => 1,
                            'name' => 'test_facet',
                            'sort' => CustomFieldFacetBuilder::TERM_ASC,
                        ]),
                    ],
                    'filter' => new ContentTypeIdentifier('forest'),
                    'sortClauses' => [new ContentIdSortClause()],
                ]),
                [
                    new CustomFieldFacet([
                        'entries' => [
                            'grab' => 2,
                            'hrast' => 4,
                            'jasen' => 2,
                            'jela' => 1,
                            'lipa' => 3,
                        ],
                        'name' => 'test_facet',
                    ]),
                ],
            ],
            [
                new LocationQuery([
                    'facetBuilders' => [
                        new CustomFieldFacetBuilder([
                            'fieldName' => 'forest_tree_value_s',
                            'limit' => 10,
                            'minCount' => 3,
                            'name' => 'test_facet',
                            'sort' => CustomFieldFacetBuilder::COUNT_DESC,
                        ]),
                    ],
                    'filter' => new ContentTypeIdentifier('forest'),
                    'sortClauses' => [new ContentIdSortClause()],
                ]),
                [
                    new CustomFieldFacet([
                        'entries' => [
                            'hrast' => 4,
                            'lipa' => 3,
                        ],
                        'name' => 'test_facet',
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
        $contentTypeCreateStruct = $contentTypeService->newContentTypeCreateStruct('forest');
        $contentTypeCreateStruct->mainLanguageCode = 'eng-GB';
        $contentTypeCreateStruct->names = ['eng-GB' => 'Forest type'];
        $fieldDefinitionCreateStruct = $contentTypeService->newFieldDefinitionCreateStruct('tree', 'ezstring');
        $contentTypeCreateStruct->addFieldDefinition($fieldDefinitionCreateStruct);
        $contentTypeDraft = $contentTypeService->createContentType($contentTypeCreateStruct, [reset($contentTypeGroups)]);
        $contentTypeService->publishContentTypeDraft($contentTypeDraft);
        $contentType = $contentTypeService->loadContentTypeByIdentifier('forest');

        $values = [
            'hrast',
            'hrast',
            'hrast',
            'hrast',
            'lipa',
            'lipa',
            'lipa',
            'grab',
            'grab',
            'jasen',
            'jasen',
            'smreka',
            'jela',
        ];

        $locationCreateStruct = $locationService->newLocationCreateStruct(2);

        foreach ($values as $value) {
            $contentCreateStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
            $contentCreateStruct->setField('tree', $value);
            $contentDraft = $contentService->createContent($contentCreateStruct, [$locationCreateStruct]);
            $contentService->publishVersion($contentDraft->versionInfo);
        }

        $this->refreshSearch($repository);

        self::assertTrue(true);
    }

    /**
     * @dataProvider providerForTestFind
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Search\Facet[] $expectedFacets
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
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
     * @param \eZ\Publish\API\Repository\Values\Content\Search\Facet[] $expectedFacets
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testFindLocations(LocationQuery $query, array $expectedFacets): void
    {
        $searchService = $this->getSearchService(false);

        $searchResult = $searchService->findLocations($query);

        self::assertEquals($expectedFacets, $searchResult->facets);
    }
}
