<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Tests\Integration\API;

use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator;
use Ibexa\Contracts\Core\Repository\Exceptions\BadStateException;
use Ibexa\Contracts\Core\Repository\Exceptions\ContentFieldValidationException;
use Ibexa\Contracts\Core\Repository\Exceptions\ContentValidationException;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;

use Ibexa\Core\Repository\SiteAccessAware\ContentTypeService;
use Ibexa\Tests\Integration\Core\Repository\BaseTest;

/**
 * @group descendant-indexing
 */
final class DescendantIndexingLocationTest extends BaseTest
{

    /**
     * @return void
     * @throws UnauthorizedException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @group assign-section-to-subtree
     */
    public function testAssignSectionToSubtree()
    {
        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();
        $sectionService = $repository->getSectionService();

        $mediaSectionId = $this->generateId('section', 3);

        /* BEGIN: Use Case */
        $locationService = $repository->getLocationService();

        // Load a location instance
        $locations = $this->createLocationsForTesting();
        $childLocationId = $locations['childLocationId'];
        $parentLocationId = $locations['parentLocationId'];
        $grandChildLocationId = $locations['grandChildLocationId'];

        $parentLocation = $locationService->loadLocation($parentLocationId);
        // Load the "Media" section
        $section = $sectionService->loadSection($mediaSectionId);

        // Assign Section to ContentInfo
        $sectionService->assignSectionToSubtree($parentLocation, $section);

        $this->refreshSearch($repository);

        $query = new Query([
            'filter' => new Query\Criterion\LogicalAnd([
                new Query\Criterion\LocationId($parentLocationId),
                new Query\Criterion\CustomField(
                    'ng_child_section_field_1_i',
                    Operator::EQ,
                    $mediaSectionId),
                new Query\Criterion\SectionId($mediaSectionId),
            ]),
        ]);

        $searchResult = $searchService->findContent($query);

        self::assertEquals(1, $searchResult->totalCount);
        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Content $parentContentFound */
        $parentContentFound = $searchResult->searchHits[0]->valueObject;
        self::assertEquals($parentLocation->contentId, $parentContentFound->id);

    }


    public function testCopySubtree()
    {
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();
        $searchService = $repository->getSearchService();

        $locations = $this->createLocationsForTesting();
        $childLocationId = $locations['childLocationId'];
        $siblingLocationId = $locations['siblingLocationId'];

        // Load location to copy
        $locationToCopy = $locationService->loadLocation($childLocationId);

        // Load new parent location
        $newParentLocation = $locationService->loadLocation($siblingLocationId);

        $locationService->copySubtree(
            $locationToCopy,
            $newParentLocation
        );

        $siblingLocation = $locationService->loadLocation($siblingLocationId);

        $this->refreshSearch($repository);

        $query = new Query([
            'filter' => new Query\Criterion\LogicalAnd([
                new Query\Criterion\LocationId($siblingLocationId),
                new Query\Criterion\FullText('more')
            ]),
        ]);

        $searchResult = $searchService->findContent($query);
        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Content $parentContentFound */
        $parentContentFound = $searchResult->searchHits[0]->valueObject;

        self::assertEquals(1, $searchResult->totalCount);
        self::assertEquals($siblingLocation->contentId, $parentContentFound->id);

    }

    public function testCreateLocation()
    {
        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();
        $locationService = $repository->getLocationService();

        $locations = $this->createLocationsForTesting();
        $parentLocationId = $locations['parentLocationId'];

        $parentLocation = $locationService->loadLocation($parentLocationId);

        $this->refreshSearch($repository);

        $query = new Query([
            'filter' => new Query\Criterion\LogicalAnd([
                new Query\Criterion\LocationId($parentLocationId),
                new Query\Criterion\FullText('more')
            ]),
        ]);

        $searchResult = $searchService->findContent($query);
        $parentContentFound = $searchResult->searchHits[0]->valueObject;

        self::assertEquals(1, $searchResult->totalCount);
        self::assertEquals($parentContentFound->id, $parentLocation->contentId);
    }

    public function testDeleteLocation()
    {
        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();
        $locationService = $repository->getLocationService();

        $locations = $this->createLocationsForTesting();
        $parentLocationId = $locations['parentLocationId'];
        $grandChildLocationId = $locations['grandChildLocationId'];
        $grandChildLocation = $locationService->loadLocation($grandChildLocationId);

        $locationService->deleteLocation($grandChildLocation);

        $this->refreshSearch($repository);

        $query = new Query([
            'filter' => new Query\Criterion\LogicalAnd([
                new Query\Criterion\LocationId($parentLocationId),
                new Query\Criterion\FullText('more')
            ]),
        ]);

        $searchResult = $searchService->findContent($query);

        self::assertEquals(0, $searchResult->totalCount);
    }


    public function testMoveSubtree()
    {
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();
        $searchService = $repository->getSearchService();

        $locations = $this->createLocationsForTesting();
        $parentLocationId = $locations['parentLocationId'];
        $childLocationId = $locations['childLocationId'];
        $siblingLocationId = $locations['siblingLocationId'];
        $grandChildLocationId = $locations['grandChildLocationId'];

        // Load location to copy
        $locationToMove = $locationService->loadLocation($childLocationId);

        // Load new parent location
        $newParentLocation = $locationService->loadLocation($siblingLocationId);

        $locationService->moveSubtree(
            $locationToMove,
            $newParentLocation
        );

        $siblingLocation = $locationService->loadLocation($siblingLocationId);

        $this->refreshSearch($repository);

        $query = new Query([
            'filter' => new Query\Criterion\LogicalAnd([
                new Query\Criterion\LocationId($siblingLocationId),
                new Query\Criterion\FullText('more')
            ]),
        ]);

        $searchResult = $searchService->findContent($query);
        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Content $parentContentFound */
        $parentContentFound = $searchResult->searchHits[0]->valueObject;

        self::assertEquals(1, $searchResult->totalCount);
        self::assertEquals($siblingLocation->contentId, $parentContentFound->id);

        $query = new Query([
            'filter' => new Query\Criterion\LogicalAnd([
                new Query\Criterion\LocationId($parentLocationId),
                new Query\Criterion\FullText('more')
            ]),
        ]);

        $searchResult = $searchService->findContent($query);

        self::assertEquals(0, $searchResult->totalCount);
    }

    /**
     * @return void
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidCriterionArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     * @group swap
     */
    public function testSwapLocation()
    {
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();
        $searchService = $repository->getSearchService();

        $locations = $this->createLocationsForTesting();

        $siblingLocationId = $locations['siblingLocationId'];
        $grandChildLocationId = $locations['grandChildLocationId'];
        $parentLocationId = $locations['parentLocationId'];

        $siblingLocation = $locationService->loadLocation($siblingLocationId);
        $grandChildLocation = $locationService->loadLocation($grandChildLocationId);

        $locationService->swapLocation(
            $siblingLocation,
            $grandChildLocation
        );

        $this->refreshSearch($repository);

        $query = new Query([
            'filter' => new Query\Criterion\LogicalAnd([
                new Query\Criterion\LocationId($parentLocationId),
                new Query\Criterion\FullText('more')
            ]),
        ]);

        $searchResult = $searchService->findContent($query);

        self::assertEquals(0, $searchResult->totalCount);

        $query = new Query([
            'filter' => new Query\Criterion\LogicalAnd([
                new Query\Criterion\LocationId($parentLocationId),
                new Query\Criterion\FullText('sibling')
            ]),
        ]);

        $searchResult = $searchService->findContent($query);

        self::assertEquals(0, $searchResult->totalCount);
    }

    public function testHideLocation()
    {
        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();
        $locationService = $repository->getLocationService();

        $locations = $this->createLocationsForTesting();
        $parentLocationId = $locations['parentLocationId'];
        $grandChildLocationId = $locations['grandChildLocationId'];
        $grandChildLocation = $locationService->loadLocation($grandChildLocationId);

        $locationService->hideLocation($grandChildLocation);

        $this->refreshSearch($repository);

        $query = new Query([
            'filter' => new Query\Criterion\LogicalAnd([
                new Query\Criterion\LocationId($parentLocationId),
                new Query\Criterion\FullText('more')
            ]),
        ]);

        $searchResult = $searchService->findContent($query);

        self::assertEquals(0, $searchResult->totalCount);

        return [
            'parentLocationId' => $parentLocationId,
            'grandChildLocationId' => $grandChildLocationId
        ];
    }

    /**
     * @return void
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidCriterionArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     *
     * @depends testHideLocation
     */
    public function testUnhideLocation(array $locations)
    {
        $repository = $this->getRepository(false);
        $searchService = $repository->getSearchService();
        $locationService = $repository->getLocationService();

        $parentLocationId = $locations['parentLocationId'];
        $grandChildLocationId = $locations['grandChildLocationId'];
        $grandChildLocation = $locationService->loadLocation($grandChildLocationId);

        $parentLocation = $locationService->loadLocation($parentLocationId);

        $locationService->unhideLocation($grandChildLocation);

        $this->refreshSearch($repository);

        $query = new Query([
            'filter' => new Query\Criterion\LogicalAnd([
                new Query\Criterion\LocationId($parentLocationId),
                new Query\Criterion\FullText('more')
            ]),
        ]);

        $searchResult = $searchService->findContent($query);

        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Content $parentContentFound */

        $parentContentFound = $searchResult->searchHits[0]->valueObject;

        self::assertEquals(1, $searchResult->totalCount);
        self::assertEquals($parentLocation->contentId, $parentContentFound->id);
    }

    /**
     * @return void
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidCriterionArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     * @group update-location
     */
    public function testUpdateLocation()
    {
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();
        $searchService = $repository->getSearchService();

        $locations = $this->createLocationsForTesting();
        $grandChildLocationId = $locations['grandChildLocationId'];
        $parentLocationId = $locations['parentLocationId'];

        $grandChildLocation = $locationService->loadLocation($grandChildLocationId);
        $parentLocation = $locationService->loadLocation($parentLocationId);

        $updateStruct = $locationService->newLocationUpdateStruct();
        $updateStruct->priority = 3;

        $updatedLocation = $locationService->updateLocation($grandChildLocation, $updateStruct);

        $this->refreshSearch($repository);

        $query = new LocationQuery([
            'filter' => new Query\Criterion\LogicalAnd([
                new Query\Criterion\LocationId($parentLocationId),
                new Query\Criterion\CustomField(
                    'ng_child_location_priority_field_2_i',
                    Operator::EQ,
                    3)
            ]),
        ]);

        $searchResult = $searchService->findLocations($query);
        self::assertEquals(1, $searchResult->totalCount);

        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Content $parentContentFound */
        $parentContentFound = $searchResult->searchHits[0]->valueObject;

        self::assertEquals($parentLocation->contentId, $parentContentFound->contentInfo->id);
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
}
