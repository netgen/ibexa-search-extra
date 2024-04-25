<?php

namespace Netgen\IbexaSearchExtra\Core\Search\Solr\FieldMapper\ContentTranslation;

use Ibexa\Contracts\Core\Persistence\Content;
use Ibexa\Contracts\Core\Search\Field;
use Ibexa\Contracts\Core\Search\FieldType\FullTextField;
use Ibexa\Contracts\Solr\FieldMapper\ContentTranslationFieldMapper;
use Netgen\IbexaSearchExtra\Core\Search\Common\PageTextExtractor;

class ContentPageTextFieldMapper extends ContentTranslationFieldMapper
{
    public function __construct(
        private readonly PageTextExtractor $pageTextExtractor,
        private readonly array $allowedContentTypes,
    ) {}
    public function accept(Content $content, $languageCode): bool
    {
        return true;
    }

    public function mapFields(Content $content, $languageCode): array
    {
        $contentTypeIdentifier = $content->versionInfo->contentInfo->contentTypeId;

        if (!in_array($contentTypeIdentifier, $this->allowedContentTypes, true)) {
            return [];
        }

        $text = $this->pageTextExtractor->extractPageText($content->versionInfo->contentInfo->id, $languageCode);
        $pageTextFields = [];
        foreach ($text as $level => $value) {
            $pageTextFields[] = new Field(
                'page_text_' . $level,
                $value,
                new FullTextField(),
            );
        }
        return $pageTextFields;
    }
}
