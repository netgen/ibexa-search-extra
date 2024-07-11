<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Tests\Integration\API;

use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Core\Repository\SiteAccessAware\ContentTypeService;
use Ibexa\Tests\Integration\Core\Repository\BaseTest;

/**
 * @group descendant-indexing
 */
final class DescendantIndexingContentTest extends BaseTest
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
    public function testPublishVersion()
    {
        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();

        $content = $this->createContentForTesting();

        $this->refreshSearch($repository);

        $query = new Query([
            'filter' => new Query\Criterion\LogicalAnd([
                new Query\Criterion\ContentId($content['parentContentId']),
                new Query\Criterion\FullText('šćenac'),
                new Query\Criterion\FullText('more'),
            ]),
        ]);

        $searchResult = $searchService->findContent($query);

        self::assertEquals(1, $searchResult->totalCount);

        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Content $parentContentFound */
        $parentContentFound = $searchResult->searchHits[0]->valueObject;

        self::assertEquals($parentContentFound->id, $content['parentContentId']);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentFieldValidationException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentTypeFieldDefinitionValidationException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentValidationException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidCriterionArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     *
     * @group delete
     * @depends testPublishVersion
     */
    public function testDeleteContent(): void
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();
        $searchService = $repository->getSearchService();

        $content = $this->createContentForTesting();
        $grandChildContent = $contentService->loadContent($content['grandChildContentId']);
        $parentContent = $contentService->loadContent($content['parentContentId']);

        $contentService->deleteContent($grandChildContent->contentInfo);

        $this->refreshSearch($repository);

        $query = new Query([
            'filter' => new Query\Criterion\LogicalAnd([
                new Query\Criterion\ContentId($parentContent->id),
                new Query\Criterion\FullText('more')
            ]),
        ]);

        $searchResult = $searchService->findContent($query);

        self::assertEquals(0, $searchResult->totalCount);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentFieldValidationException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentTypeFieldDefinitionValidationException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentValidationException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidCriterionArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     *
     * @depends testPublishVersion
     */
    public function testDeleteTranslation(): void
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();
        $searchService = $repository->getSearchService();

        $content = $this->createContentForTesting();
        $grandChildContent = $contentService->loadContent($content['grandChildContentId']);
        $parentContent = $contentService->loadContent($content['parentContentId']);

        $contentService->deleteTranslation($grandChildContent->contentInfo, 'ger-DE');

        $this->refreshSearch($repository);

        $query = new Query([
            'filter' => new Query\Criterion\LogicalAnd([
                new Query\Criterion\ContentId($parentContent->id),
                new Query\Criterion\FullText('mogorush')
            ]),
        ]);

        $searchResult = $searchService->findContent($query);

        self::assertEquals(0, $searchResult->totalCount);
    }


    public function testHideContent(): int
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();
        $searchService = $repository->getSearchService();

        $content = $this->createContentForTesting();
        $grandChildContent = $contentService->loadContent($content['grandChildContentId']);
        $parentContent = $contentService->loadContent($content['parentContentId']);

        $contentService->hideContent($grandChildContent->contentInfo);

        $this->refreshSearch($repository);

        $query = new Query([
            'filter' => new Query\Criterion\LogicalAnd([
                new Query\Criterion\ContentId($parentContent->id),
                new Query\Criterion\FullText('more')
            ]),
        ]);

        $searchResult = $searchService->findContent($query);

        self::assertEquals(0, $searchResult->totalCount);

        return $grandChildContent->id;
    }

    /**
     * @return void
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidCriterionArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     *
     * @depends testHideContent
     */
    public function testRevealContent(int $hiddenContentId): void
    {
        $repository = $this->getRepository(false);
        $contentService = $repository->getContentService();
        $searchService = $repository->getSearchService();

        $hiddenContent = $contentService->loadContent($hiddenContentId);
        $parentLocation = $hiddenContent->contentInfo->getMainLocation()->getParentLocation()->getParentLocation();

        $contentService->revealContent($hiddenContent->contentInfo);

        $this->refreshSearch($repository);

        $query = new Query([
            'filter' => new Query\Criterion\LogicalAnd([
                new Query\Criterion\ContentId($parentLocation->contentId),
                new Query\Criterion\FullText('more')
            ]),
        ]);

        $searchResult = $searchService->findContent($query);
        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Content $parentContentFound */
        $parentContentFound = $searchResult->searchHits[0]->valueObject;

        self::assertEquals(1, $searchResult->totalCount);
        self::assertEquals($parentContentFound->id, $parentLocation->contentId);

    }

    public function testUpdateContentMetadataHandler(): void
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();
        $searchService = $repository->getSearchService();

        $content = $this->createContentForTesting();
        $grandChildContent = $contentService->loadContent($content['grandChildContentId']);
        $parentContent = $contentService->loadContent($content['parentContentId']);

        // Creates a metadata update struct
        $metadataUpdate = $contentService->newContentMetadataUpdateStruct();

        $metadataUpdate->remoteId = 'aaaabbbbccccddddeeeeffff11112222';
        $metadataUpdate->mainLanguageCode = 'eng-GB';
        $metadataUpdate->alwaysAvailable = false;
        $metadataUpdate->publishedDate = $this->createDateTime(441759600); // 1984/01/01
        $metadataUpdate->modificationDate = $this->createDateTime(441759600); // 1984/01/01

        // Update the metadata of the published content object
        $grandChildContent = $contentService->updateContentMetadata(
            $grandChildContent->contentInfo,
            $metadataUpdate
        );


        $this->assertInstanceOf(
            Content::class,
            $grandChildContent
        );

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
        //for testDeleteTranslation()
        $contentCreateStruct->setField('name', 'mogorush', 'ger-DE');
        $contentDraft = $contentService->createContent($contentCreateStruct, [$locationCreateStruct]);
        $grandChildContentCreated = $contentService->publishVersion($contentDraft->versionInfo);

        return [
            'parentContentId' => $parentContentCreated->id,
            'childContentId' => $childContentCreated->id,
            'grandChildContentId' => $grandChildContentCreated->id
        ];
    }

}
