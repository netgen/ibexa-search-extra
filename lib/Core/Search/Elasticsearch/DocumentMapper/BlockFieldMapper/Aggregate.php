<?php

namespace Netgen\IbexaSearchExtra\Core\Search\Elasticsearch\DocumentMapper\BlockFieldMapper;

use Ibexa\Contracts\Core\Persistence\Content as SPIContent;
use Netgen\IbexaSearchExtra\Core\Search\Elasticsearch\DocumentMapper\BlockFieldMapper;

class Aggregate extends BlockFieldMapper
{
    /**
     * An array of aggregated field mappers, sorted by priority.
     *
     * @var BlockFieldMapper[]
     */
    protected $mappers = [];

    /**
     * @param BlockFieldMapper[] $mappers
     * An array of mappers, sorted by priority.
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
    public function addMapper(BlockFieldMapper $mapper): void
    {
        $this->mappers[] = $mapper;
    }

    public function accept(SPIContent $content): bool
    {
        return true;
    }

    public function mapFields(SPIContent $content): array
    {

        $fields = [];

        foreach ($this->mappers as $mapper) {
            if ($mapper->accept($content)) {
                $fields = [...$fields, ...$mapper->mapFields($content)];
            }
        }

        return $fields;
    }
}