<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Elasticsearch\DocumentMapper\BlockTranslationFieldMapper;

use Ibexa\Contracts\Core\Persistence\Content;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as ContentTypeHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Search\Field;
use Ibexa\Contracts\Core\Search\FieldType\FullTextField;
use Netgen\IbexaSearchExtra\Core\Search\Common\PageIndexing\ConfigResolver;
use Netgen\IbexaSearchExtra\Core\Search\Common\PageIndexing\TextExtractor;
use Netgen\IbexaSearchExtra\Core\Search\Elasticsearch\DocumentMapper\BlockTranslationFieldMapper;

use function in_array;

class BlockPageTextFieldMapper extends BlockTranslationFieldMapper
{
    public function __construct(
        private readonly TextExtractor $pageTextExtractor,
        private readonly ContentTypeHandler $contentTypeHandler,
        private readonly ConfigResolver $configResolver,
        private readonly bool $isEnabled,
    ) {}

    public function accept(Content $content, string $languageCode): bool
    {
        return $this->isEnabled;
    }

    /**
     * @throws NotFoundException
     */
    public function mapFields(Content $content, string $languageCode): array
    {
        $contentInfo = $content->versionInfo->contentInfo;
        $contentType = $this->contentTypeHandler->load($content->versionInfo->contentInfo->contentTypeId);
        $contentTypeIdentifier = $contentType->identifier;

        $config = $this->configResolver->getSiteConfigForContent($contentInfo->id, $languageCode);

        if (!in_array($contentTypeIdentifier, $config->getAllowedContentTypes(), true)) {
            return [];
        }

        $text = $this->pageTextExtractor->extractPageText($contentInfo->id, $languageCode);
        $fields = [];

        foreach ($text as $level => $value) {
            $fields[] = new Field(
                'page_text_' . $level,
                $value,
                new FullTextField(),
            );
        }

        return $fields;
    }
}
