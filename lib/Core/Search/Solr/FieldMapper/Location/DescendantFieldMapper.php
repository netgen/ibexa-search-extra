<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Solr\FieldMapper\Location;

use Ibexa\Contracts\Core\Persistence\Content\Location;
use Ibexa\Contracts\Solr\FieldMapper\LocationFieldMapper;
use Netgen\IbexaSearchExtra\Core\Search\Solr\FieldMapper\Location\DescendantFieldMapper\BaseFieldMapper;

use function array_merge;

final class DescendantFieldMapper extends LocationFieldMapper
{
    /**
     * @param \Netgen\IbexaSearchExtra\Core\Search\Solr\FieldMapper\Location\DescendantFieldMapper\BaseFieldMapper[] $fieldMappers
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

    public function accept(Location $location): bool
    {
        return $this->configuration['enabled'] ?? false;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function mapFields(Location $location): array
    {
        $fieldsGrouped = [[]];

        foreach ($this->fieldMappers as $fieldMapper) {
            if ($fieldMapper->accept($location)) {
                $fieldsGrouped[] = $fieldMapper->mapFields($location);
            }
        }

        return array_merge(...$fieldsGrouped);
    }
}
