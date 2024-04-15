<?php

namespace Netgen\IbexaSearchExtra\Core\Search\Elasticsearch\DocumentMapper\LocationTranslationFieldMapper;

use Ibexa\Contracts\Core\Persistence\Content as SPIContent;
use Ibexa\Contracts\Core\Persistence\Content\Location as SPILocation;
use Netgen\IbexaSearchExtra\Core\Search\Elasticsearch\DocumentMapper\LocationTranslationFieldMapper;

class Aggregate extends LocationTranslationFieldMapper
{
    /**
     * An array of aggregated field mappers, sorted by priority.
     *
     * @var LocationTranslationFieldMapper[]
     */
    protected $mappers = [];

    /**
     * @param LocationTranslationFieldMapper[] $mappers
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
    public function addMapper(LocationTranslationFieldMapper $mapper): void
    {
        $this->mappers[] = $mapper;
    }

    public function accept(SPILocation $location, string $languageCode): bool
    {
        return true;
    }

    public function mapFields(SPILocation $location, string $languageCode): array
    {

        $fields = [];

        foreach ($this->mappers as $mapper) {
            if ($mapper->accept($location, $languageCode)) {
                $fields = [...$fields, ...$mapper->mapFields($location, $languageCode)];
            }
        }

        return $fields;
    }
}