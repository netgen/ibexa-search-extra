<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Solr\FieldMapper\ContentTranslation\DescendantFieldMapper;

use Ibexa\Contracts\Core\Persistence\Content as SPIContent;

interface FullTextFieldResolver
{
    /**
     * @return \Ibexa\Contracts\Core\Search\Field[]
     */
    public function resolveFields(SPIContent $content, string $languageCode): array;
}
