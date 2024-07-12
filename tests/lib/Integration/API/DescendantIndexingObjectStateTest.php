<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Tests\Integration\API;

use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Core\Repository\SiteAccessAware\ContentTypeService;
use Ibexa\Tests\Integration\Core\Repository\BaseTest;

final class DescendantIndexingObjectStateTest extends BaseTest
{

    /**
     * @return void
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidCriterionArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function testSetObjectState(): void
    {
        $repository = $this->getRepository();
        $contentService = $this->getRepository()->getContentService();
        $searchService = $this->getRepository()->getSearchService();
        $objectStateService = $this->getRepository()->getObjectStateService();

        $content = $this->createContentForTesting();
        $grandChildContentId = $content['grandChildContentId'];
        $parentContentId = $content['parentContentId'];
        $grandChildContent = $contentService->loadContent($grandChildContentId);
        $parentContent = $contentService->loadContent($parentContentId);

        $stateGroup = $objectStateService->loadObjectStateGroupByIdentifier(
            "ez_lock",
        );

        $lockedState = $objectStateService->loadObjectStateByIdentifier($stateGroup, 'locked');

        $objectStateService->setContentState($grandChildContent->contentInfo, $stateGroup, $lockedState);

        $this->refreshSearch($repository);

        $query = new Query([
            'filter' => new Query\Criterion\LogicalAnd([
                new Query\Criterion\LocationId($parentContent->contentInfo->mainLocationId),
                new Query\Criterion\CustomField(
                    'ng_child_object_state_2_s',
                    Operator::EQ,
                    $lockedState->identifier
                )
            ]),
        ]);

        $searchResult = $searchService->findContent($query);

        self::assertEquals(1, $searchResult->totalCount);
        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Content $parentContentFound */
        $parentContentFound = $searchResult->searchHits[0]->valueObject;
        self::assertEquals($parentContentId, $parentContentFound->id);
    }


    /**
     * @return array
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentFieldValidationException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentValidationException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    private function createContentForTesting(): array
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();
        $contentTypeService = $repository->getContentTypeService();
        $locationService = $repository->getLocationService();

        $contentType = $this->createContentType($contentTypeService, 'descendant_indexing_test');
        $contentType2 = $this->createContentType($contentTypeService, 'descendant_indexing_test_2');
        $contentType3 = $this->createContentType($contentTypeService, 'descendant_indexing_test_3');

        $locationCreateStruct = $locationService->newLocationCreateStruct(2);
        $contentCreateStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $contentCreateStruct->setField('name', 'mogoruš');
        $contentDraft = $contentService->createContent($contentCreateStruct, [$locationCreateStruct]);
        $parentContentCreated = $contentService->publishVersion($contentDraft->versionInfo);
        $parentLocation = $parentContentCreated->contentInfo->getMainLocation();

        $locationCreateStruct = $locationService->newLocationCreateStruct($parentLocation->id);
        $contentCreateStruct = $contentService->newContentCreateStruct($contentType2, 'eng-GB');
        $contentCreateStruct->setField('name', 'šćenac');
        $contentDraft = $contentService->createContent($contentCreateStruct, [$locationCreateStruct]);
        $childContentCreated = $contentService->publishVersion($contentDraft->versionInfo);
        $childLocation = $childContentCreated->contentInfo->getMainLocation();

        $locationCreateStruct = $locationService->newLocationCreateStruct($childLocation->id);
        $contentCreateStruct = $contentService->newContentCreateStruct($contentType3, 'eng-GB');
        $contentCreateStruct->setField('name', 'more');
        $contentDraft = $contentService->createContent($contentCreateStruct, [$locationCreateStruct]);
        $grandChildContentCreated = $contentService->publishVersion($contentDraft->versionInfo);

        return [
            'parentContentId' => $parentContentCreated->id,
            'childContentId' => $childContentCreated->id,
            'grandChildContentId' => $grandChildContentCreated->id
        ];
    }

    /**
     * @param ContentTypeService $contentTypeService
     * @param string $identifier
     * @return \Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentTypeFieldDefinitionValidationException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    private function createContentType(ContentTypeService $contentTypeService, string $identifier): \Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType
    {
        $contentTypeGroups = $contentTypeService->loadContentTypeGroups();
        $contentTypeCreateStruct = $contentTypeService->newContentTypeCreateStruct($identifier);
        $contentTypeCreateStruct->mainLanguageCode = 'eng-GB';
        $contentTypeCreateStruct->names = ['eng-GB' => 'Descendant indexing test'];
        $contentTypeCreateStruct->isContainer = true;
        $fieldDefinitionCreateStruct = $contentTypeService->newFieldDefinitionCreateStruct('name', 'ezstring');
        $fieldDefinitionCreateStruct->position = 0;
        $fieldDefinitionCreateStruct->isSearchable = true;
        $contentTypeCreateStruct->addFieldDefinition($fieldDefinitionCreateStruct);
        $contentTypeDraft = $contentTypeService->createContentType($contentTypeCreateStruct, [reset($contentTypeGroups)]);
        $contentTypeService->publishContentTypeDraft($contentTypeDraft);

        return $contentTypeService->loadContentTypeByIdentifier($identifier);

    }
}
