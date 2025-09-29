<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\FieldType\BinaryFile;

use Ibexa\Contracts\Core\FieldType\Indexable;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;
use Ibexa\Contracts\Core\Search;
use Netgen\IbexaSearchExtra\Core\Search\Common\PageIndexing\TextExtractor\FileTextExtractor;

use function trim;

/**
 * Indexable definition for BinaryFile field type.
 */
final class SearchField implements Indexable
{
    /** @var array<int, array<int, string>> */
    private array $cache = [];

    public function __construct(
        private readonly Indexable $innerField,
        private readonly FileTextExtractor $fileTextExtractor,
        private readonly bool $fileIndexingEnabled,
    ) {}

    public function getIndexData(Field $field, FieldDefinition $fieldDefinition): array
    {
        $searchFields = $this->innerField->getIndexData($field, $fieldDefinition);

        if (!$this->fileIndexingEnabled) {
            return $searchFields;
        }

        $text = $this->extractText($field);

        if ($text !== '') {
            $searchFields[] = new Search\Field(
                'file_text',
                $text,
                new Search\FieldType\FullTextField(),
            );
        }

        return $searchFields;
    }

    public function getIndexDefinition(): array
    {
        return $this->innerField->getIndexDefinition();
    }

    public function getDefaultMatchField(): ?string
    {
        return $this->innerField->getDefaultMatchField();
    }

    public function getDefaultSortField(): ?string
    {
        return $this->innerField->getDefaultSortField();
    }

    private function extractText(Field $field): string
    {
        if (!isset($this->cache[$field->id][$field->versionNo])) {
            $this->cache = [];
            $this->cache[$field->id][$field->versionNo] = trim($this->fileTextExtractor->extractFromPersistenceField($field));
        }

        return $this->cache[$field->id][$field->versionNo];
    }
}
