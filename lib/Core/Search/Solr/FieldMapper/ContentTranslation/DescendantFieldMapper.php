<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Solr\FieldMapper\ContentTranslation;

use Ibexa\Contracts\Core\Persistence\Content;
use Ibexa\Contracts\Solr\FieldMapper\ContentTranslationFieldMapper;
use Netgen\IbexaSearchExtra\Core\Search\Solr\FieldMapper\ContentTranslation\DescendantFieldMapper\BaseFieldMapper;

use function array_merge;

final class DescendantFieldMapper extends ContentTranslationFieldMapper
{
    /**
     * @param \Netgen\IbexaSearchExtra\Core\Search\Solr\FieldMapper\ContentTranslation\DescendantFieldMapper\BaseFieldMapper[] $fieldMappers
     */
    public function __construct(
        private readonly array $configuration,
        private array $fieldMappers = [],
    ) {
        foreach ($this->fieldMappers as $fieldMapper) {
            $this->addFieldMapper($fieldMapper);
        }
    }

    public function addFieldMapper(BaseFieldMapper $fieldMapper): void
    {
        $this->fieldMappers[] = $fieldMapper;
    }

    public function accept(Content $content, $languageCode): bool
    {
        return $this->configuration['enabled'] ?? false;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function mapFields(Content $content, $languageCode): array
    {
        $fieldsGrouped = [[]];

        foreach ($this->fieldMappers as $fieldMapper) {
            if ($fieldMapper->accept($content, $languageCode)) {
                $fieldsGrouped[] = $fieldMapper->mapFields($content, $languageCode);
            }
        }

        return array_merge(...$fieldsGrouped);
    }
}
