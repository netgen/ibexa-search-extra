<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Elasticsearch\DocumentMapper\BlockTranslationFieldMapper;

use Ibexa\Contracts\Core\Persistence\Content as SPIContent;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as ContentTypeHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Search\Field;
use Ibexa\Contracts\Core\Search\FieldType\FullTextField;
use Netgen\IbexaSearchExtra\Core\Search\Common\PageTextExtractor;
use Netgen\IbexaSearchExtra\Core\Search\Common\PageIndexingConfigResolver;
use Netgen\IbexaSearchExtra\Core\Search\Elasticsearch\DocumentMapper\BlockTranslationFieldMapper;

use function in_array;

class BlockPageTextFieldMapper extends BlockTranslationFieldMapper
{
    public function __construct(
        private readonly PageTextExtractor $pageTextExtractor,
        private readonly ContentTypeHandler $contentTypeHandler,
        private readonly PageIndexingConfigResolver $configResolver,
    ) {}

    public function accept(SPIContent $content, string $languageCode): bool
    {
        return true;
    }

    /**
     * @throws NotFoundException
     */
    public function mapFields(SPIContent $content, string $languageCode): array
    {
        $fields = [];

        $siteConfig = $this->configResolver->getSiteConfigForContent($content->versionInfo->contentInfo->id);
        $contentType = $this->contentTypeHandler->load($content->versionInfo->contentInfo->contentTypeId);

        if (in_array($contentType->identifier, $siteConfig['allowed_content_types'], true)) {
            $text = $this->pageTextExtractor->extractPageText($content->versionInfo->contentInfo->id, $languageCode);

            foreach ($text as $level => $value) {
                $fields[] = new Field('page_text_' . $level, $value, new FullTextField());
            }
        }

        return $fields;
    }
}
