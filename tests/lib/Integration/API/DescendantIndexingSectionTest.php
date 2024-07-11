<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Tests\Integration\API;

use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator;
use Ibexa\Contracts\Core\Repository\Exceptions\BadStateException;
use Ibexa\Contracts\Core\Repository\Exceptions\ContentFieldValidationException;
use Ibexa\Contracts\Core\Repository\Exceptions\ContentValidationException;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Core\Repository\SiteAccessAware\ContentTypeService;
use Ibexa\Tests\Integration\Core\Repository\BaseTest;

/**
 * @group descendant-indexing
 */
final class DescendantIndexingSectionTest extends BaseTest
{

    public function testAssignSection()
    {
        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();
        $sectionService = $repository->getSectionService();
        $locationService = $repository->getLocationService();

        $mediaSectionId = $this->generateId('section', 3);

        $locations = $this->createLocationsForTesting();
        $childLocationId = $locations['childLocationId'];
        $parentLocationId = $locations['parentLocationId'];

        $parentLocation = $locationService->loadLocation($parentLocationId);
        $childLocation = $locationService->loadLocation($childLocationId);

        $section = $sectionService->loadSection($mediaSectionId);

        $sectionService->assignSection($childLocation->contentInfo, $section);

        $this->refreshSearch($repository);

        $query = new Query([
            'filter' => new Query\Criterion\LogicalAnd([
                new Query\Criterion\LocationId($parentLocationId),
                new Query\Criterion\CustomField(
                    'ng_child_section_field_1_i',
                    Operator::EQ,
                    $mediaSectionId)
            ]),
        ]);

        $searchResult = $searchService->findContent($query);

        self::assertEquals(1, $searchResult->totalCount);
        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Content $parentContentFound */
        $parentContentFound = $searchResult->searchHits[0]->valueObject;
        self::assertEquals($parentLocation->contentId, $parentContentFound->id);
    }


    /**
     * @throws ContentFieldValidationException
     * @throws InvalidArgumentException
     * @throws BadStateException
     * @throws ContentValidationException
     * @throws UnauthorizedException
     */
    private function createLocationsForTesting() {
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

        $locationCreateStruct = $locationService->newLocationCreateStruct(2);
        $contentCreateStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $contentCreateStruct->setField('name', 'sibling');
        $contentDraft = $contentService->createContent($contentCreateStruct, [$locationCreateStruct]);
        $siblingLocationCreated = $contentService->publishVersion($contentDraft->versionInfo);
        $siblingLocation = $siblingLocationCreated->contentInfo->getMainLocation();

        $locationCreateStruct = $locationService->newLocationCreateStruct($parentLocation->id);
        $contentCreateStruct = $contentService->newContentCreateStruct($contentType2, 'eng-GB');
        $contentCreateStruct->setField('name', 'šćenac');
        $contentDraft = $contentService->createContent($contentCreateStruct, [$locationCreateStruct]);
        $childContentCreated = $contentService->publishVersion($contentDraft->versionInfo);
        $childLocation = $childContentCreated->contentInfo->getMainLocation();

        $locationCreateStruct = $locationService->newLocationCreateStruct($childLocation->id);
        $contentCreateStruct = $contentService->newContentCreateStruct($contentType3, 'eng-GB');
        $contentCreateStruct->setField('name', 'more');
        $contentCreateStruct->setField('name', 'mogorush', 'ger-DE');
        $contentDraft = $contentService->createContent($contentCreateStruct, [$locationCreateStruct]);
        $grandChildContentCreated = $contentService->publishVersion($contentDraft->versionInfo);
        $grandChildLocation = $grandChildContentCreated->contentInfo->getMainLocation();

        return [
            'parentLocationId' => $parentLocation->id,
            'siblingLocationId' => $siblingLocation->id,
            'childLocationId' => $childLocation->id,
            'grandChildLocationId' => $grandChildLocation->id
        ];
    }

    private function createContentType(ContentTypeService $contentTypeService, string $identifier) {
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
