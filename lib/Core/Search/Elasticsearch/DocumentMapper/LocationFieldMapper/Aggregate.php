<?php

namespace Netgen\IbexaSearchExtra\Core\Search\Elasticsearch\DocumentMapper\LocationFieldMapper;

use Ibexa\Contracts\Core\Persistence\Content as SPIContent;
use Ibexa\Contracts\Core\Persistence\Content\Location as SPILocation;

use Netgen\IbexaSearchExtra\Core\Search\Elasticsearch\DocumentMapper\LocationFieldMapper;

class Aggregate extends LocationFieldMapper
{
    /**
     * An array of aggregated field mappers.
     *
     * @var LocationFieldMapper[]
     */
    protected $mappers = [];

    /**
     * @param LocationFieldMapper[] $mappers
     *        An array of mappers.
     */
    public function __construct(array $mappers = [])
    {
        foreach ($mappers as $mapper) {
            $this->addMapper($mapper);
        }
    }

    /**
     * Adds given $mapper to the internal array.
     */
    public function addMapper(LocationFieldMapper $mapper): void
    {
        $this->mappers[] = $mapper;
    }

    public function accept(SPILocation $location): bool
    {
        return true;
    }

    public function mapFields(SPILocation $location): array
    {

        $fields = [];

        foreach ($this->mappers as $mapper) {
            if ($mapper->accept($location)) {
                $fields = [...$fields, ...$mapper->mapFields($location)];
            }
        }

        return $fields;
    }
}