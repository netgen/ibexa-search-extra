<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Solr\FieldMapper\ContentTranslation;

use eZ\Publish\Core\Persistence\Legacy\Content\Type\Handler;
use Ibexa\Contracts\Core\Persistence\Content;
use Ibexa\Contracts\Core\Search\Field;
use Ibexa\Contracts\Core\Search\FieldType\FullTextField;
use Ibexa\Contracts\Solr\FieldMapper\ContentTranslationFieldMapper;
use Netgen\IbexaSearchExtra\Core\Search\Common\PageIndexing\ConfigResolver;
use Netgen\IbexaSearchExtra\Core\Search\Common\PageIndexing\TextExtractor;

use function in_array;

class ContentPageTextFieldMapper extends ContentTranslationFieldMapper
{
    public function __construct(
        private readonly TextExtractor $pageTextExtractor,
        private readonly ConfigResolver $configResolver,
        private readonly Handler $contentTypeHandler,
    ) {}

    public function accept(Content $content, $languageCode): bool
    {
        return true;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function mapFields(Content $content, $languageCode): array
    {
        $contentInfo = $content->versionInfo->contentInfo;
        $contentType = $this->contentTypeHandler->loadByIdentifier($contentInfo->contentTypeId);
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
