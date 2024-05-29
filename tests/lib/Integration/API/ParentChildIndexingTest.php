<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Tests\Integration\API;

use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Tests\Integration\Core\Repository\BaseTest;

/**
 * @group parent-child-indexing
 */
final class ParentChildIndexingTest extends BaseTest
{
    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentFieldValidationException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentTypeFieldDefinitionValidationException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentValidationException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidCriterionArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function testPublishVersion(): void
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();
        $contentTypeService = $repository->getContentTypeService();
        $locationService = $repository->getLocationService();
        $searchService = $repository->getSearchService();

        $contentTypeGroups = $contentTypeService->loadContentTypeGroups();
        $contentTypeCreateStruct = $contentTypeService->newContentTypeCreateStruct('parent_child_test');
        $contentTypeCreateStruct->mainLanguageCode = 'eng-GB';
        $contentTypeCreateStruct->names = ['eng-GB' => 'Article'];
        $fieldDefinitionCreateStruct = $contentTypeService->newFieldDefinitionCreateStruct('name', 'ezstring');
        $fieldDefinitionCreateStruct->position = 0;
        $fieldDefinitionCreateStruct->isSearchable = true;
        $contentTypeCreateStruct->addFieldDefinition($fieldDefinitionCreateStruct);
        $contentTypeDraft = $contentTypeService->createContentType($contentTypeCreateStruct, [reset($contentTypeGroups)]);
        $contentTypeService->publishContentTypeDraft($contentTypeDraft);

        $contentType = $contentTypeService->loadContentTypeByIdentifier('parent_child_test');

        $locationCreateStruct = $locationService->newLocationCreateStruct(2);
        $contentCreateStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $contentCreateStruct->setField('name', 'mogoruš');
        $contentDraft = $contentService->createContent($contentCreateStruct, [$locationCreateStruct]);
        $parentContentCreated = $contentService->publishVersion($contentDraft->versionInfo);
        $parentLocation = $parentContentCreated->contentInfo->getMainLocation();

        $locationCreateStruct = $locationService->newLocationCreateStruct($parentLocation->id);
        $contentCreateStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $contentCreateStruct->setField('name', 'šćenac');
        $contentDraft = $contentService->createContent($contentCreateStruct, [$locationCreateStruct]);
        $contentService->publishVersion($contentDraft->versionInfo);

        $this->refreshSearch($repository);

        $query = new Query([
            'filter' => new Query\Criterion\LogicalAnd([
                new Query\Criterion\ContentId($parentContentCreated->id),
                new Query\Criterion\FullText('šćenac'),
            ]),
        ]);

        $searchResult = $searchService->findContent($query);

        self::assertEquals(1, $searchResult->totalCount);

        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Content $parentContentFound */
        $parentContentFound = $searchResult->searchHits[0]->valueObject;

        self::assertEquals($parentContentFound->id, $parentContentCreated->id);
    }
}
