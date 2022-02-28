<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Tests\Integration\Solr;

use Ibexa\Tests\Integration\Core\Repository\BaseTest;
use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\ContentId;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\CustomField;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator;
use Netgen\IbexaSearchExtra\Core\Search\Solr\API\Facet\RawFacet;
use Netgen\IbexaSearchExtra\Core\Search\Solr\API\FacetBuilder\RawFacetBuilder;
use Netgen\IbexaSearchExtra\Core\Search\Solr\API\FacetBuilder\RawFacetBuilder\Domain\BlockChildren;

/**
 * @group raw
 */
class RawFacetDomainTest extends BaseTest
{
    /**
     * @throws \JsonException
     */
    public function providerForTestFind(): array
    {
        return [
            [
                new Query([
                    'facetBuilders' => [
                        new RawFacetBuilder([
                            'name' => 'test_facet',
                            'parameters' => [
                                'type' => 'terms',
                                'field' => 'price_i',
                                'allBuckets' => true,
                                'sort' => 'count desc',
                                'facet' => [
                                    'maximum' => 'max(price_i)',
                                    'minimum' => 'min(price_i)',
                                ],
                            ],
                            'domain' => new BlockChildren([
                                'parentDocumentIdentifier' => 'content',
                                'childDocumentIdentifier' => 'test_content_subdocument',
                                'filter' => null,
                            ]),
                        ]),
                    ],
                    'filter' => new ContentId([4, 12, 13, 42, 59]),
                    'limit' => 0,
                ]),
                [
                    new RawFacet([
                        'name' => 'test_facet',
                        'data' => json_decode(
                            json_encode(
                                [
                                    'allBuckets' => [
                                        'count' => 4,
                                        'maximum' => 60.0,
                                        'minimum' => 40.0,
                                    ],
                                    'buckets' => [
                                        [
                                            'val' => 50,
                                            'count' => 2,
                                            'maximum' => 50.0,
                                            'minimum' => 50.0,
                                        ],
                                        [
                                            'val' => 40,
                                            'count' => 1,
                                            'maximum' => 40.0,
                                            'minimum' => 40.0,
                                        ],
                                        [
                                            'val' => 60,
                                            'count' => 1,
                                            'maximum' => 60.0,
                                            'minimum' => 60.0,
                                        ],
                                    ],
                                ],
                                JSON_THROW_ON_ERROR
                            ),
                            false,
                            512,
                            JSON_THROW_ON_ERROR
                        ),
                    ]),
                ],
            ],
            [
                new Query([
                    'facetBuilders' => [
                        new RawFacetBuilder([
                            'name' => 'test_facet',
                            'parameters' => [
                                'type' => 'terms',
                                'field' => 'price_i',
                                'allBuckets' => true,
                                'sort' => 'count desc',
                                'facet' => [
                                    'maximum' => 'max(price_i)',
                                    'minimum' => 'min(price_i)',
                                ],
                            ],
                            'domain' => new BlockChildren([
                                'parentDocumentIdentifier' => 'content',
                                'childDocumentIdentifier' => 'test_content_subdocument',
                                'filter' => new CustomField('visible_b', Operator::EQ, true),
                            ]),
                        ]),
                    ],
                    'filter' => new ContentId([4, 12, 13, 42, 59]),
                    'limit' => 0,
                ]),
                [
                    new RawFacet([
                        'name' => 'test_facet',
                        'data' => json_decode(
                            json_encode(
                                [
                                    'allBuckets' => [
                                        'count' => 2,
                                        'maximum' => 60.0,
                                        'minimum' => 40.0,
                                    ],
                                    'buckets' => [
                                        [
                                            'val' => 40,
                                            'count' => 1,
                                            'maximum' => 40.0,
                                            'minimum' => 40.0,
                                        ],
                                        [
                                            'val' => 60,
                                            'count' => 1,
                                            'maximum' => 60.0,
                                            'minimum' => 60.0,
                                        ],
                                    ],
                                ],
                                JSON_THROW_ON_ERROR
                            ),
                            false,
                            512,
                            JSON_THROW_ON_ERROR
                        ),
                    ]),
                ],
            ],
            [
                new Query([
                    'facetBuilders' => [
                        new RawFacetBuilder([
                            'name' => 'test_facet',
                            'parameters' => [
                                'type' => 'terms',
                                'field' => 'price_i',
                                'allBuckets' => true,
                                'sort' => 'count desc',
                                'facet' => [
                                    'maximum' => 'max(price_i)',
                                    'minimum' => 'min(price_i)',
                                ],
                            ],
                            'domain' => new BlockChildren([
                                'parentDocumentIdentifier' => 'content',
                                'childDocumentIdentifier' => 'test_content_subdocument',
                                'filter' => new CustomField('visible_b', Operator::EQ, false),
                            ]),
                        ]),
                    ],
                    'filter' => new ContentId([4, 12, 13, 42, 59]),
                    'limit' => 0,
                ]),
                [
                    new RawFacet([
                        'name' => 'test_facet',
                        'data' => json_decode(
                            json_encode(
                                [
                                    'allBuckets' => [
                                        'count' => 2,
                                        'maximum' => 50.0,
                                        'minimum' => 50.0,
                                    ],
                                    'buckets' => [
                                        [
                                            'val' => 50,
                                            'count' => 2,
                                            'maximum' => 50.0,
                                            'minimum' => 50.0,
                                        ],
                                    ],
                                ],
                                JSON_THROW_ON_ERROR
                            ),
                            false,
                            512,
                            JSON_THROW_ON_ERROR
                        ),
                    ]),
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerForTestFind
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Search\Facet[] $expectedFacets
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testFindContent(Query $query, array $expectedFacets): void
    {
        $searchService = $this->getSearchService(false);

        $searchResult = $searchService->findContentInfo($query);

        $this->assertEquals($expectedFacets, $searchResult->facets);
    }

    protected function getSearchService($initialInitializeFromScratch = true): SearchService
    {
        return $this->getRepository($initialInitializeFromScratch)->getSearchService();
    }
}
