<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Solr\FieldMapper\ContentTranslation\DescendantFieldMapper;

use Ibexa\Contracts\Core\Persistence\Content as SPIContent;
use Ibexa\Contracts\Core\Persistence\Content\ContentInfo;
use Ibexa\Contracts\Core\Persistence\Content\Handler as ContentHandler;
use Ibexa\Contracts\Core\Persistence\Content\Location\Handler as LocationHandler;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as ContentTypeHandler;
use Ibexa\Contracts\Core\Persistence\Filter\Content\Handler;
use Ibexa\Contracts\Core\Repository\Exceptions\BadStateException;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidCriterionArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\ContentTypeIdentifier;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LogicalAnd;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\ParentLocationId;
use Ibexa\Contracts\Core\Repository\Values\Filter\Filter;
use Ibexa\Contracts\Solr\FieldMapper\ContentTranslationFieldMapper;
use Netgen\IbexaSearchExtra\Core\Search\Solr\FieldMapper\ContentTranslation\DescendantFieldMapper\FullTextFieldResolver;

use Netgen\IbexaSearchExtra\Core\Search\Solr\FieldMapper\IdentifiableFieldMapper;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function count;

final class FullTextFieldMapper extends BaseFieldMapper
{
    /**
     * @var array<int, ?string>
     */
    private array $contentTypeIdIdentifierCache;

    /**
     * @param array<string, mixed> $configuration
     */
    public function __construct(
        private readonly FullTextFieldResolver $fullTextFieldResolver,
        private readonly ContentTypeHandler $contentTypeHandler,
        private readonly ContentHandler $contentHandler,
        private readonly Handler $contentFilteringHandler,
        private readonly LocationHandler $locationHandler,
        private readonly array $configuration,
        private readonly int $childrenLimit = 99,
    ) {
        parent::__construct(
            $contentTypeHandler,
            $this->configuration,
        );
    }

    public function doAccept(SPIContent $content, $languageCode): bool
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
    public function mapFields(SPIContent $content, $languageCode): array
    {
        $contentTypeId = $content->versionInfo->contentInfo->contentTypeId;
        $contentTypeIdentifier = $this->getContentTypeIdentifier($contentTypeId);

        return $this->recursiveMapFields(
            $content->versionInfo->contentInfo,
            $languageCode,
            $this->configuration['map'][$contentTypeIdentifier] ?? [],
            false,
        );
    }

    public function getIdentifier(): string
    {
        return 'ng_descendant_indexing_fulltext';
    }

    /**
     * @param array<string, mixed>|null $configuration
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidCriterionArgumentException
     *
     * @return \Ibexa\Contracts\Core\Search\Field[]
     */
    private function recursiveMapFields(
        ContentInfo $contentInfo,
        string $languageCode,
        ?array $configuration,
        bool $doIndex = true,
    ): array {
        $fieldsGrouped = [[]];
        $isIndexed = !isset($configuration['indexed']) || $configuration['indexed'];
        $childrenConfiguration = $configuration['children'] ?? [];

        if ($isIndexed && $doIndex) {
            $content = $this->contentHandler->load($contentInfo->id);
            $fieldsGrouped[] = $this->fullTextFieldResolver->resolveFields($content, $languageCode);
        }

        $childrenContentInfoList = $this->loadChildrenContentInfoList(
            $contentInfo,
            $childrenConfiguration,
        );

        foreach ($childrenContentInfoList as $childContentInfo) {
            $contentTypeId = $childContentInfo->contentTypeId;
            $contentTypeIdentifier = $this->getContentTypeIdentifier($contentTypeId);
            $childConfiguration = $childrenConfiguration[$contentTypeIdentifier] ?? [];

            $fieldsGrouped[] = $this->recursiveMapFields(
                $childContentInfo,
                $languageCode,
                $childConfiguration,
            );
        }

        return array_merge(...$fieldsGrouped);
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

    /**
     * @param array<string, mixed> $configuration
     *
     * @throws BadStateException
     * @throws InvalidCriterionArgumentException
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\ContentInfo[]
     */
    private function loadChildrenContentInfoList(
        ContentInfo $contentInfo,
        array $configuration,
    ): array {
        $contentTypeIdentifiers = array_keys($configuration);

        if (count($contentTypeIdentifiers) === 0) {
            return [];
        }

        $filter = new Filter();
        $filter
            ->withCriterion(
                new LogicalAnd([
                    new ContentTypeIdentifier($contentTypeIdentifiers),
                    new ParentLocationId($contentInfo->mainLocationId),
                ])
            )
            ->withLimit($this->childrenLimit);

        $contentItemList = $this->contentFilteringHandler->find($filter);
        $items = [];

        foreach ($contentItemList as $contentItem) {
            $contentLocations = $this->locationHandler->loadLocationsByContent($contentItem->contentInfo->id);

            foreach ($contentLocations as $contentLocation) {
                if (
                    $contentLocation->parentId === $contentInfo->mainLocationId
                    && !$contentLocation->hidden
                    && !$contentLocation->invisible
                    && !$contentInfo->isHidden
                ) {
                    $items[] = $contentItem->contentInfo;

                    break;
                }
            }
        }

        return $items;
    }
}
