<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Tests\Integration\API;

use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidCriterionArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\TrashItem;
use Ibexa\Core\Repository\SiteAccessAware\ContentTypeService;
use Ibexa\Tests\Integration\Core\Repository\BaseTest;

final class DescendantIndexingTrashTest extends BaseTest
{

    /**
     * @throws NotFoundException
     * @throws InvalidCriterionArgumentException
     * @throws InvalidArgumentException
     * @throws UnauthorizedException
     */
    public function testTrash()
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();
        $searchService = $repository->getSearchService();
        $trashService = $repository->getTrashService();

        $content = $this->createContentForTesting();
        $grandChildContent = $contentService->loadContent($content['grandChildContentId']);
        $parentContent = $contentService->loadContent($content['parentContentId']);

        $trashItem = $trashService->trash($grandChildContent->contentInfo->getMainLocation());

        $this->refreshSearch($repository);

        $query = new Query([
            'filter' => new Query\Criterion\LogicalAnd([
                new Query\Criterion\ContentId($parentContent->id),
                new Query\Criterion\FullText('more')
            ]),
        ]);

        $searchResult = $searchService->findContent($query);

        self::assertEquals(0, $searchResult->totalCount);

        return [
            "trashItem" => $trashItem,
            "content" => $content
        ];
    }

    /**
     * @param array $sharedObjects
     * @return void
     * @throws InvalidArgumentException
     * @throws InvalidCriterionArgumentException
     * @throws NotFoundException
     * @throws UnauthorizedException
     * @depends testTrash
     */
    public function testRecover(array $sharedObjects): void
    {
        $trashItem = $sharedObjects['trashItem'];
        $content = $sharedObjects['content'];

        $repository = $this->getRepository(false);
        $contentService = $repository->getContentService();
        $searchService = $repository->getSearchService();
        $trashService = $repository->getTrashService();

        $parentContent = $contentService->loadContent($content['parentContentId']);

        $location = $trashService->recover($trashItem);

        $this->refreshSearch($repository);

        $query = new Query([
            'filter' => new Query\Criterion\LogicalAnd([
                new Query\Criterion\ContentId($parentContent->id),
                new Query\Criterion\FullText('more')
            ]),
        ]);

        $searchResult = $searchService->findContent($query);
        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Content $parentContentFound */
        $parentContentFound = $searchResult->searchHits[0]->valueObject;

        self::assertEquals(1, $searchResult->totalCount);
        self::assertEquals($parentContentFound->id, $parentContent->id);
    }



    private function createContentType(ContentTypeService $contentTypeService, string $identifier) {

        $contentTypeGroups = $contentTypeService->loadContentTypeGroups();
        $contentTypeCreateStruct = $contentTypeService->newContentTypeCreateStruct($identifier);
        $contentTypeCreateStruct->mainLanguageCode = 'eng-GB';
        $contentTypeCreateStruct->names = ['eng-GB' => 'Descendant indexing test'];
        $fieldDefinitionCreateStruct = $contentTypeService->newFieldDefinitionCreateStruct('name', 'ezstring');
        $fieldDefinitionCreateStruct->position = 0;
        $fieldDefinitionCreateStruct->isSearchable = true;
        $contentTypeCreateStruct->addFieldDefinition($fieldDefinitionCreateStruct);
        $contentTypeDraft = $contentTypeService->createContentType($contentTypeCreateStruct, [reset($contentTypeGroups)]);
        $contentTypeService->publishContentTypeDraft($contentTypeDraft);

        return $contentTypeService->loadContentTypeByIdentifier($identifier);

    }

    private function createContentForTesting() {
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

}
