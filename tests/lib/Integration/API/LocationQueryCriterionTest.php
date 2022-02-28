<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Tests\Integration\API;

use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\ContentId;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Location\Priority;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LogicalAnd;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LogicalNot;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LogicalOr;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Subtree;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Visibility;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause\ContentId as ContentIdSortClause;
use Netgen\IbexaSearchExtra\API\Values\Content\Query\Criterion\LocationQuery;

class LocationQueryCriterionTest extends BaseTest
{
    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     *
     * @return array
     */
    public function providerForTestFind(): array
    {
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();

        $homeLocation = $locationService->loadLocation(2);
        $usersLocation = $locationService->loadLocation(5);
        $mediaLocation = $locationService->loadLocation(43);

        return [
            [
                new Query([
                    'filter' => new LogicalAnd([
                        new ContentId([12, 13, 42]),
                        new LocationQuery(
                            new LogicalAnd([
                                new Visibility(Visibility::HIDDEN),
                                new Priority(Operator::LTE, 100),
                                new Priority(Operator::GTE, 100),
                            ]),
                        ),
                    ]),
                    'sortClauses' => [new ContentIdSortClause()],
                ]),
                [],
                [12, 42],
            ],
            [
                new Query([
                    'filter' => new LogicalAnd([
                        new ContentId([12, 13, 42]),
                        new LocationQuery(
                            new LogicalNot(
                                new LogicalAnd([
                                    new Visibility(Visibility::HIDDEN),
                                    new Priority(Operator::LTE, 100),
                                    new Priority(Operator::GTE, 100),
                                ]),
                            ),
                        ),
                    ]),
                    'sortClauses' => [new ContentIdSortClause()],
                ]),
                [],
                [12, 13, 42],
            ],
            [
                new Query([
                    'filter' => new LogicalAnd([
                        new ContentId([12, 13, 42]),
                        new LocationQuery(
                            new LogicalAnd([
                                new Subtree($homeLocation->pathString),
                                new Visibility(Visibility::HIDDEN),
                                new Priority(Operator::LTE, 100),
                                new Priority(Operator::GTE, 100),
                            ]),
                        ),
                    ]),
                    'sortClauses' => [new ContentIdSortClause()],
                ]),
                [],
                [12],
            ],
            [
                new Query([
                    'filter' => new LogicalAnd([
                        new ContentId([12, 13, 42]),
                        new LocationQuery(
                            new LogicalAnd([
                                new Subtree($mediaLocation->pathString),
                                new Visibility(Visibility::HIDDEN),
                                new Priority(Operator::LTE, 100),
                                new Priority(Operator::GTE, 100),
                            ]),
                        ),
                    ]),
                    'sortClauses' => [new ContentIdSortClause()],
                ]),
                [],
                [42],
            ],
            [
                new Query([
                    'filter' => new LogicalAnd([
                        new ContentId([12, 13, 42]),
                        new LogicalNot(
                            new Subtree($homeLocation->pathString),
                        ),
                    ]),
                    'sortClauses' => [new ContentIdSortClause()],
                ]),
                [],
                [13],
            ],
            [
                new Query([
                    'filter' => new LogicalAnd([
                        new ContentId([12, 13, 42]),
                        new LogicalNot(
                            new Subtree($usersLocation->pathString),
                        ),
                    ]),
                    'sortClauses' => [new ContentIdSortClause()],
                ]),
                [],
                [],
            ],
            [
                new Query([
                    'filter' => new LogicalAnd([
                        new ContentId([12, 13, 42]),
                        new LogicalNot(
                            new LocationQuery(
                                new Subtree($usersLocation->pathString),
                            ),
                        ),
                    ]),
                    'sortClauses' => [new ContentIdSortClause()],
                ]),
                [],
                [],
            ],
            [
                new Query([
                    'filter' => new LogicalAnd([
                        new ContentId([12, 13, 42]),
                        new Subtree($homeLocation->pathString),
                        new LocationQuery(
                            new LogicalNot(
                                new Subtree($usersLocation->pathString),
                            ),
                        ),
                    ]),
                    'sortClauses' => [new ContentIdSortClause()],
                ]),
                [],
                [12, 42],
            ],
            [
                new Query([
                    'filter' => new LogicalAnd([
                        new ContentId([12, 13, 42]),
                        new LocationQuery(
                            new LogicalAnd([
                                new Visibility(Visibility::HIDDEN),
                                new Subtree($homeLocation->pathString),
                            ]),
                        ),
                        new LocationQuery(
                            new LogicalNot(
                                new Subtree($usersLocation->pathString),
                            ),
                        ),
                    ]),
                    'sortClauses' => [new ContentIdSortClause()],
                ]),
                [],
                [12],
            ],
            [
                new Query([
                    'filter' => new LogicalAnd([
                        new ContentId([12, 13, 42]),
                        new LocationQuery(
                            new LogicalAnd([
                                new Visibility(Visibility::HIDDEN),
                                new Subtree($mediaLocation->pathString),
                            ]),
                        ),
                        new LocationQuery(
                            new LogicalNot(
                                new Subtree($usersLocation->pathString),
                            ),
                        ),
                    ]),
                    'sortClauses' => [new ContentIdSortClause()],
                ]),
                [],
                [42],
            ],
            [
                new Query([
                    'filter' => new LogicalAnd([
                        new ContentId([12, 13, 42]),
                        new LocationQuery(
                            new LogicalAnd([
                                new Visibility(Visibility::VISIBLE),
                                new Subtree($homeLocation->pathString),
                            ]),
                        ),
                        new LocationQuery(
                            new LogicalNot(
                                new Subtree($usersLocation->pathString),
                            ),
                        ),
                    ]),
                    'sortClauses' => [new ContentIdSortClause()],
                ]),
                [],
                [42],
            ],
            [
                new Query([
                    'filter' => new LogicalAnd([
                        new ContentId([12, 13, 42]),
                        new LocationQuery(
                            new LogicalAnd([
                                new Visibility(Visibility::HIDDEN),
                                new Subtree($homeLocation->pathString),
                            ]),
                        ),
                        new LocationQuery(
                            new LogicalNot(
                                new Subtree($usersLocation->pathString),
                            ),
                        ),
                    ]),
                    'sortClauses' => [new ContentIdSortClause()],
                ]),
                [],
                [12],
            ],
            [
                new Query([
                    'filter' => new LogicalAnd([
                        new ContentId([12, 13, 42]),
                        new LogicalOr([
                            new LogicalNot(
                                new Subtree($homeLocation->pathString),
                            ),
                            new LogicalAnd([
                                new LocationQuery(
                                    new LogicalAnd([
                                        new Visibility(Visibility::HIDDEN),
                                        new Subtree($homeLocation->pathString),
                                    ]),
                                ),
                                new LocationQuery(
                                    new LogicalNot(
                                        new Subtree($usersLocation->pathString),
                                    ),
                                ),
                            ]),
                        ]),
                    ]),
                    'sortClauses' => [new ContentIdSortClause()],
                ]),
                [],
                [12, 13],
            ],
            [
                new Query([
                    'filter' => new LogicalAnd([
                        new ContentId([12, 13, 42]),
                        new LogicalNot(
                            new LocationQuery(
                                new Subtree($homeLocation->pathString),
                            ),
                        ),
                    ]),
                    'sortClauses' => [new ContentIdSortClause()],
                ]),
                [],
                [13],
            ],
            [
                new Query([
                    'filter' => new LogicalAnd([
                        new ContentId([12, 13, 42]),
                        new LogicalNot(
                            new LocationQuery(
                                new Subtree($mediaLocation->pathString),
                            ),
                        ),
                    ]),
                    'sortClauses' => [new ContentIdSortClause()],
                ]),
                [],
                [13],
            ],
            [
                new Query([
                    'filter' => new LogicalAnd([
                        new ContentId([12, 13, 42]),
                        new LogicalNot(
                            new LocationQuery(
                                new LogicalOr([
                                    new Subtree($homeLocation->pathString),
                                    new Subtree($mediaLocation->pathString),
                                ]),
                            ),
                        ),
                    ]),
                    'sortClauses' => [new ContentIdSortClause()],
                ]),
                [],
                [13],
            ],
            [
                new Query([
                    'filter' => new LogicalAnd([
                        new ContentId([12, 13, 42]),
                        new LocationQuery(
                            new LogicalOr([
                                new Subtree($homeLocation->pathString),
                                new Subtree($mediaLocation->pathString),
                            ]),
                        ),
                    ]),
                    'sortClauses' => [new ContentIdSortClause()],
                ]),
                [],
                [12, 42],
            ],
        ];
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testPrepareTestFixtures(): void
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();
        $locationService = $repository->getLocationService();

        $contentInfo = $contentService->loadContentInfo(12);

        $createStruct = $locationService->newLocationCreateStruct(2);
        $createStruct->hidden = true;
        $createStruct->priority = 100;
        $locationService->createLocation($contentInfo, $createStruct);

        $createStruct = $locationService->newLocationCreateStruct(43);
        $createStruct->hidden = false;
        $createStruct->priority = 200;
        $locationService->createLocation($contentInfo, $createStruct);

        $contentInfo = $contentService->loadContentInfo(42);

        $createStruct = $locationService->newLocationCreateStruct(2);
        $createStruct->hidden = false;
        $createStruct->priority = 200;
        $locationService->createLocation($contentInfo, $createStruct);

        $createStruct = $locationService->newLocationCreateStruct(43);
        $createStruct->hidden = true;
        $createStruct->priority = 100;
        $locationService->createLocation($contentInfo, $createStruct);

        $this->refreshSearch($repository);

        self::assertTrue(true);
    }

    /**
     * @dataProvider providerForTestFind
     *
     * @param int[] $expectedIds
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testFindContent(Query $query, array $languageFilter, array $expectedIds): void
    {
        $searchService = $this->getSearchService(false);

        $searchResult = $searchService->findContentInfo($query, $languageFilter);

        $this->assertSearchResultContentIds($searchResult, $expectedIds);
    }
}
