<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Solr\FieldMapper\Content;

use Ibexa\Contracts\Core\Persistence\Content;
use Ibexa\Contracts\Solr\FieldMapper\ContentFieldMapper;
use Netgen\IbexaSearchExtra\Core\Search\Solr\FieldMapper\Content\DescendantFieldMapper\BaseFieldMapper;

use function array_merge;

final class DescendantFieldMapper extends ContentFieldMapper
{
    /**
     * @param \Netgen\IbexaSearchExtra\Core\Search\Solr\FieldMapper\Content\DescendantFieldMapper\BaseFieldMapper[] $fieldMappers
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

    public function accept(Content $content): bool
    {
        return $this->configuration['enabled'] ?? false;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function mapFields(Content $content): array
    {
        $fieldsGrouped = [[]];

        foreach ($this->fieldMappers as $fieldMapper) {
            if ($fieldMapper->accept($content)) {
                $fieldsGrouped[] = $fieldMapper->mapFields($content);
            }
        }

        return array_merge(...$fieldsGrouped);
    }
}
