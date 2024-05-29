<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Solr\FieldMapper;

use Ibexa\Contracts\Core\Persistence\Content as SPIContent;
use Ibexa\Contracts\Core\Persistence\Content\ContentInfo;
use Ibexa\Contracts\Core\Persistence\Content\Handler as ContentHandler;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as ContentTypeHandler;
use Ibexa\Contracts\Core\Persistence\Content\Location\Handler as LocationHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\BadStateException;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidCriterionArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\ContentTypeIdentifier;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LogicalAnd;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\ParentLocationId;
use Ibexa\Contracts\Core\Repository\Values\Filter\Filter;
use Ibexa\Contracts\Core\Persistence\Filter\Content\Handler;
use Ibexa\Contracts\Solr\FieldMapper\ContentTranslationFieldMapper;

use function array_key_exists;
use function array_keys;
use function array_merge;
use function count;

final class ParentChildFieldMapper extends ContentTranslationFieldMapper
{
    /**
     * @var array<int, ?string>
     */
    private array $contentTypeIdIdentifierCache;

    /**
     * @param array<string, mixed> $configuration
     */
    public function __construct(
        private readonly array $configuration,
        private readonly FullTextFieldResolver $fullTextFieldResolver,
        private readonly ContentTypeHandler $contentTypeHandler,
        private readonly ContentHandler $contentHandler,
        private readonly Handler $contentFilteringHandler,
        private readonly LocationHandler $locationHandler,
        private readonly int $childrenLimit = 99,
    ) {}

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function accept(SPIContent $content, $languageCode): bool
    {
        $contentTypeId = $content->versionInfo->contentInfo->contentTypeId;
        $contentType = $this->contentTypeHandler->load($contentTypeId);
        $contentTypeIdentifier = $contentType->identifier;

        return array_key_exists($contentTypeIdentifier, $this->configuration);
    }

    /**
     * @param string $languageCode
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidCriterionArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     *
     * @return \Ibexa\Contracts\Core\Search\Field[]
     */
    public function mapFields(SPIContent $content, $languageCode): array
    {
        $contentTypeId = $content->versionInfo->contentInfo->contentTypeId;
        $contentType = $this->contentTypeHandler->load($contentTypeId);
        $contentTypeIdentifier = $contentType->identifier;

        return $this->recursiveMapFields(
            $content->versionInfo->contentInfo,
            $languageCode,
            $this->configuration[$contentTypeIdentifier],
            false,
        );
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
        $isIndexed = !isset($configuration['indexed']) || (bool) $configuration['indexed'];
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
            $childConfiguration = $childrenConfiguration[$contentTypeIdentifier] ?? null;

            if ($childConfiguration === null) {
                continue;
            }

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
