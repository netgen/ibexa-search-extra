<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Tests\Integration\API;

use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\ContentId;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LogicalAnd;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LogicalNot;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause\ContentId as ContentIdSortClause;
use Netgen\IbexaSearchExtra\API\Values\Content\Query\Criterion\IsFieldEmpty;

class IsFieldEmptyCriterionTest extends BaseTest
{
    public function providerForTestFind(): array
    {
        return [
            [
                new LocationQuery([
                    'filter' => new LogicalAnd([
                        new ContentId([4, 11, 42]),
                        new IsFieldEmpty('description', IsFieldEmpty::IS_NOT_EMPTY),
                    ]),
                    'sortClauses' => [new ContentIdSortClause()],
                ]),
                [],
                [4, 42],
            ],
            [
                new LocationQuery([
                    'filter' => new LogicalAnd([
                        new ContentId([4, 11, 42]),
                        new IsFieldEmpty('description', IsFieldEmpty::IS_EMPTY),
                    ]),
                    'sortClauses' => [new ContentIdSortClause()],
                ]),
                [],
                [11],
            ],
            [
                new LocationQuery([
                    'filter' => new LogicalAnd([
                        new ContentId([4, 11, 42]),
                        new LogicalNot(
                            new IsFieldEmpty('description', IsFieldEmpty::IS_NOT_EMPTY),
                        ),
                    ]),
                    'sortClauses' => [new ContentIdSortClause()],
                ]),
                [],
                [11],
            ],
            [
                new LocationQuery([
                    'filter' => new LogicalAnd([
                        new ContentId([4, 11, 42]),
                        new LogicalNot(
                            new IsFieldEmpty('description', IsFieldEmpty::IS_EMPTY),
                        ),
                    ]),
                    'sortClauses' => [new ContentIdSortClause()],
                ]),
                [],
                [4, 42],
            ],
            [
                new LocationQuery([
                    'filter' => new LogicalAnd([
                        new ContentId([4, 11, 42]),
                        new IsFieldEmpty('description', IsFieldEmpty::IS_NOT_EMPTY),
                    ]),
                    'sortClauses' => [new ContentIdSortClause()],
                ]),
                ['languages' => ['ger-DE']],
                [11],
            ],
            [
                new LocationQuery([
                    'filter' => new LogicalAnd([
                        new ContentId([4, 11, 42]),
                        new IsFieldEmpty('description', IsFieldEmpty::IS_EMPTY),
                    ]),
                    'sortClauses' => [new ContentIdSortClause()],
                ]),
                ['languages' => ['ger-DE']],
                [4, 42],
            ],
            [
                new LocationQuery([
                    'filter' => new LogicalAnd([
                        new ContentId([4, 11, 42]),
                        new LogicalNot(
                            new IsFieldEmpty('description', IsFieldEmpty::IS_NOT_EMPTY),
                        ),
                    ]),
                    'sortClauses' => [new ContentIdSortClause()],
                ]),
                ['languages' => ['ger-DE']],
                [4, 42],
            ],
            [
                new LocationQuery([
                    'filter' => new LogicalAnd([
                        new ContentId([4, 11, 42]),
                        new LogicalNot(
                            new IsFieldEmpty('description', IsFieldEmpty::IS_EMPTY),
                        ),
                    ]),
                    'sortClauses' => [new ContentIdSortClause()],
                ]),
                ['languages' => ['ger-DE']],
                [11],
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
        $languageCode = 'ger-DE';

        $contentInfo = $contentService->loadContentInfo(4);
        $draft = $contentService->createContentDraft($contentInfo);
        $updateStruct = $contentService->newContentUpdateStruct();
        $updateStruct->initialLanguageCode = $languageCode;
        $updateStruct->setField('name', 'not empty', $languageCode);
        $updateStruct->setField('description', '', $languageCode);
        $contentService->updateContent($draft->versionInfo, $updateStruct);
        $contentService->publishVersion($draft->versionInfo);

        $contentInfo = $contentService->loadContentInfo(11);
        $draft = $contentService->createContentDraft($contentInfo);
        $updateStruct = $contentService->newContentUpdateStruct();
        $updateStruct->initialLanguageCode = $languageCode;
        $updateStruct->setField('name', 'not empty', $languageCode);
        $updateStruct->setField('description', 'not empty', $languageCode);
        $contentService->updateContent($draft->versionInfo, $updateStruct);
        $contentService->publishVersion($draft->versionInfo);

        $contentInfo = $contentService->loadContentInfo(42);
        $draft = $contentService->createContentDraft($contentInfo);
        $updateStruct = $contentService->newContentUpdateStruct();
        $updateStruct->initialLanguageCode = $languageCode;
        $updateStruct->setField('name', 'not empty', $languageCode);
        $updateStruct->setField('description', '', $languageCode);
        $contentService->updateContent($draft->versionInfo, $updateStruct);
        $contentService->publishVersion($draft->versionInfo);

        $this->refreshSearch($repository);

        $this->addToAssertionCount(1);
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

    /**
     * @dataProvider providerForTestFind
     *
     * @param int[] $expectedIds
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testFindLocations(LocationQuery $query, array $languageFilter, array $expectedIds): void
    {
        $searchService = $this->getSearchService(false);

        $searchResult = $searchService->findLocations($query, $languageFilter);

        $this->assertSearchResultContentIds($searchResult, $expectedIds);
    }
}
