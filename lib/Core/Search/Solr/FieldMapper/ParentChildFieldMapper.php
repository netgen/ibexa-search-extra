<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Solr\FieldMapper;

use Ibexa\Contracts\Core\Persistence\Content as SPIContent;
use Ibexa\Contracts\Core\Persistence\Content\ContentInfo;
use Ibexa\Contracts\Core\Persistence\Content\Handler as ContentHandler;
use Ibexa\Contracts\Core\Persistence\Content\Location\Handler as LocationHandler;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as ContentTypeHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\ContentTypeIdentifier;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LogicalAnd;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\ParentLocationId;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit;
use Ibexa\Contracts\Solr\FieldMapper\ContentTranslationFieldMapper;
use Ibexa\Core\Search\Legacy\Content\Handler;
use Netgen\IbexaSearchExtra\API\Values\Content\Query\Criterion\LocationQuery as LocationQueryCriterion;
use Netgen\IbexaSearchExtra\API\Values\Content\Query\Criterion\Visible;
use Netgen\IbexaSiteApi\Core\Traits\SearchResultExtractorTrait;


use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function count;

final class ParentChildFieldMapper extends ContentTranslationFieldMapper
{
    use SearchResultExtractorTrait;

    /**
     * @var array<int, ?string>
     */
    private array $contentTypeIdIdentifierCache;

    /**
     * @param array<string, mixed> $configuration
     */
    public function __construct(
        private readonly array $configuration,
        private readonly FulltextFieldResolver $fulltextFieldResolver,
        private readonly ContentTypeHandler $contentTypeHandler,
        private readonly ContentHandler $contentHandler,
        private readonly LocationHandler $locationHandler,
        private readonly Handler $searchHandler,
        private readonly int $childrenLimit = 99,
    ) {}

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
     * @param array<string, mixed> $configuration
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
            $fieldsGrouped[] = $this->fulltextFieldResolver->resolveFields($content, $languageCode);
        }

        $childrenContentInfoList = $this->loadChildrenContentInfoList(
            $contentInfo,
            $languageCode,
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
     * @return \Ibexa\Contracts\Core\Persistence\Content\ContentInfo[]
     */
    private function loadChildrenContentInfoList(
        ContentInfo $contentInfo,
        string $languageCode,
        array $configuration,
    ): array {
        $contentTypeIdentifiers = array_keys($configuration);

        if (count($contentTypeIdentifiers) === 0) {
            return [];
        }

        $searchResult = $this->searchHandler->findContent(
            new Query([
                'filter' => new LocationQueryCriterion(
                    new LogicalAnd([
                        new ContentTypeIdentifier($contentTypeIdentifiers),
                        new ParentLocationId($contentInfo->mainLocationId),
                        new Visible(true),
                    ]),
                ),
                'limit' => $this->childrenLimit,
            ]),
            [
                'languages' => [
                    $languageCode,
                ],
            ],
        );

        /** @var \Ibexa\Contracts\Core\Persistence\Content\ContentInfo[] $result */
        $result = array_map(
            static fn (SearchHit $searchHit) => $searchHit->valueObject,
            $searchResult->searchHits,
        );

        return $result;
    }
}
