<?php

namespace Netgen\IbexaSearchExtra\Tests\Integration\Implementation\Solr\FieldMapper;

use eZ\Publish\SPI\Search\FieldType\StringField;
use Ibexa\Contracts\Core\Persistence\Content;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as ContentTypeHandler;
use Ibexa\Contracts\Core\Persistence\Content\ObjectState\Handler as ObjectStateHandler;

use Ibexa\Contracts\Core\Persistence\Filter\Content\Handler;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\ContentTypeIdentifier;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LogicalAnd;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\ParentLocationId;
use Ibexa\Contracts\Core\Repository\Values\Filter\Filter;
use Ibexa\Contracts\Core\Search\Field;
use Netgen\IbexaSearchExtra\Core\Search\Solr\FieldMapper\Content\DescendantFieldMapper\BaseFieldMapper;

class ObjectStateFieldMapper extends BaseFieldMapper
{
    /**
     * @var array<int, ?string>
     */
    private array $contentTypeIdIdentifierCache;

    /**
     * @param array<string, mixed> $configuration
     */
    public function __construct(
        private readonly ContentTypeHandler $contentTypeHandler,
        private readonly Handler $contentFilteringHandler,
        private readonly ObjectStateHandler $objectStateHandler,
        private readonly array $configuration,
        private readonly int $childrenLimit = 99,
    ) {
        parent::__construct(
            $contentTypeHandler,
            $this->configuration,
        );
    }

    public function doAccept(Content $content): bool
    {
        return true;
    }

    /**
     * @param string $languageCode
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidCriterionArgumentException
     *
     * @return \Ibexa\Contracts\Core\Search\Field[]
     */
    public function mapFields(Content $content): array
    {
        $contentInfo = $content->versionInfo->contentInfo;
        $contentTypeId = $content->versionInfo->contentInfo->contentTypeId;
        $contentTypeIdentifier = $this->getContentTypeIdentifier($contentTypeId);

        $childrenConfiguration = $this->configuration['map'][$contentTypeIdentifier]['children'];
        $contentTypeIdentifier = array_keys($childrenConfiguration)[0];
        $filter = new Filter();
        $filter
            ->withCriterion(
                new LogicalAnd([
                    new ContentTypeIdentifier($contentTypeIdentifier),
                    new ParentLocationId($contentInfo->mainLocationId),
                ])
            );

        $contentItemList = $this->contentFilteringHandler->find($filter);
        $fieldsGrouped = [[]];

        foreach ($contentItemList as $contentItem) {
            $contentTypeId = $contentItem->contentInfo->contentTypeId;
            $childContentTypeIdentifier = $this->getContentTypeIdentifier($contentTypeId);

            $childConfiguration = $childrenConfiguration[$childContentTypeIdentifier] ?? [];
            $stateGroup = $this->objectStateHandler->loadGroupByIdentifier(
                "ez_lock",
            );
            $objectState = $this->objectStateHandler->getContentState(
                $contentItem->contentInfo->id,
                $stateGroup->id,
            );
            if (isset($childConfiguration['indexed']) && $childConfiguration['indexed'] === true) {
                $fieldsGrouped[] = [
                    new Field(
                        'ng_child_object_state_1',
                        $objectState->identifier,
                        new StringField(),
                    ),
                ];
            }

            $grandChildContentTypeIdentifier = array_keys($childConfiguration['children'])[0];
            $filter = new Filter();
            $filter
                ->withCriterion(
                    new LogicalAnd([
                        new ContentTypeIdentifier($grandChildContentTypeIdentifier),
                        new ParentLocationId($contentItem->contentInfo->mainLocationId),
                    ])
                )
                ->withLimit($this->childrenLimit);

            $grandChildContentItemList = $this->contentFilteringHandler->find($filter);
            foreach ($grandChildContentItemList as $grandChildContentItem) {

                $stateGroup = $this->objectStateHandler->loadGroupByIdentifier(
                    "ez_lock",
                );
                $objectState = $this->objectStateHandler->getContentState(
                    $grandChildContentItem->contentInfo->id,
                    $stateGroup->id,
                );

                $grandChildConfiguration = $childConfiguration['children'][$grandChildContentTypeIdentifier] ?? [];
                if (isset($grandChildConfiguration['indexed']) && $grandChildConfiguration['indexed'] === true) {
                    $fieldsGrouped[] = [
                        new Field(
                            'ng_child_object_state_2',
                            $objectState->identifier,
                            new StringField(),
                        ),
                    ];

                }

            }

        }

        return array_merge(...$fieldsGrouped);
    }

    public function getIdentifier(): string
    {
        return 'ng_descendant_indexing_object_state';
    }

    private function getContentTypeIdentifier(int $contentTypeId): ?string
    {
        if (isset($this->contentTypeIdIdentifierCache[$contentTypeId])) {
            return $this->contentTypeIdIdentifierCache[$contentTypeId];
        }

        try {
            $contentType = $this->contentTypeHandler->load($contentTypeId);
            $identifier = $contentType->identifier;
        } catch (NotFoundException) {
            $identifier = null;
        }

        $this->contentTypeIdIdentifierCache[$contentTypeId] = $identifier;

        return $identifier;
    }
}
