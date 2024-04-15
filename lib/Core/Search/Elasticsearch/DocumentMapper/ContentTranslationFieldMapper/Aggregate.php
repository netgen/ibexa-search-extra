<?php

namespace Netgen\IbexaSearchExtra\Core\Search\Elasticsearch\DocumentMapper\ContentTranslationFieldMapper;

use Ibexa\Contracts\Core\Persistence\Content as SPIContent;
use Netgen\IbexaSearchExtra\Core\Search\Elasticsearch\DocumentMapper\ContentTranslationFieldMapper;

class Aggregate extends ContentTranslationFieldMapper
{
    /**
     * An array of aggregated field mappers, sorted by priority.
     *
     * @var ContentTranslationFieldMapper[]
     */
    protected $mappers = [];

    /**
     * @param ContentTranslationFieldMapper[] $mappers
     *        An array of mappers, sorted by priority.
     */
    public function __construct(array $mappers = [])
    {
        foreach ($mappers as $mapper) {
            $this->addMapper($mapper);
        }
    }

    /**
     * Adds given $mapper to the internal array as the next one in priority.
     */
    public function addMapper(ContentTranslationFieldMapper $mapper): void
    {
        $this->mappers[] = $mapper;
    }

    public function accept(SPIContent $content, string $languageCode): bool
    {
        return true;
    }

    public function mapFields(SPIContent $content, string $languageCode): array
    {
        $fields = [];

        foreach ($this->mappers as $mapper) {
            if ($mapper->accept($content, $languageCode)) {
                $fields = [...$fields, ...$mapper->mapFields($content, $languageCode)];
            }
        }

        return $fields;
    }
}