<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Solr\FieldMapper\ContentTranslation;

use Ibexa\Contracts\Core\Persistence\Content as SPIContent;
use Ibexa\Contracts\Solr\FieldMapper\ContentTranslationFieldMapper;
use Ibexa\Contracts\Core\Persistence\Content\Type as ContentType;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as ContentTypeHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Search\Field;
use Ibexa\Contracts\Core\Search\FieldType\FullTextField;
use Ibexa\Contracts\Core\Search\FieldType\TextField;
use Ibexa\Core\Search\Common\FieldRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use function count;
use function in_array;
use function sprintf;

class CustomFulltextFieldMapper extends ContentTranslationFieldMapper
{
    /**
     * @var array<string, mixed>
     */
    private array $fieldConfig = [];

    public function __construct(
        private readonly ContentTypeHandler $contentTypeHandler,
        private readonly FieldRegistry $fieldRegistry,
        private readonly ParameterBagInterface $parameterBag,
    ) {}

    /**
     * @param string $languageCode
     */
    public function accept(SPIContent $content, $languageCode): bool
    {
        $this->fieldConfig = $this->parameterBag->get('ibexa_search_extra.search_boost')['field_mapper_custom_fulltext_field_config'];

        return count($this->fieldConfig) > 0;
    }

    public function mapFields(SPIContent $content, $languageCode): array
    {
        $fields = [];

        try {
            $contentType = $this->contentTypeHandler->load($content->versionInfo->contentInfo->contentTypeId);
        } catch (NotFoundException) {
            return $fields;
        }

        foreach ($content->fields as $field) {
            if ($field->languageCode !== $languageCode) {
                continue;
            }

            foreach ($contentType->fieldDefinitions as $fieldDefinition) {
                if (!$fieldDefinition->isSearchable) {
                    continue;
                }

                if ($fieldDefinition->id !== $field->fieldDefinitionId) {
                    continue;
                }

                $fieldNames = $this->getFieldNames($fieldDefinition, $contentType);

                if (count($fieldNames) === 0) {
                    continue;
                }

                $fieldType = $this->fieldRegistry->getType($field->type);
                $indexFields = $fieldType->getIndexData($field, $fieldDefinition);

                foreach ($indexFields as $indexField) {
                    if ($indexField->value === null) {
                        continue;
                    }

                    if (!$indexField->getType() instanceof FullTextField) {
                        continue;
                    }

                    $this->appendField($fields, $indexField, $fieldNames);
                }
            }
        }

        return $fields;
    }

    /**
     * @param array<string, mixed> $fields
     * @param array<string, mixed> $fieldNames
     */
    private function appendField(
        array &$fields,
        Field $indexField,
        array $fieldNames,
    ): void {
        foreach ($fieldNames as $fieldName) {
            $fields[] = new Field(
                sprintf('meta_%s__text', $fieldName),
                (string) $indexField->value,
                new TextField(),
            );
        }
    }

    /**
     * @return array<string>
     */
    private function getFieldNames(FieldDefinition $fieldDefinition, ContentType $contentType): array
    {
        $fieldNames = [];

        foreach ($this->fieldConfig as $fieldName => $fieldIdentifiers) {
            if ($this->isMapped($fieldDefinition, $contentType, $fieldIdentifiers)) {
                $fieldNames[] = $fieldName;
            }
        }

        return $fieldNames;
    }

    /**
     * @param array<string> $fieldIdentifiers
     */
    private function isMapped(FieldDefinition $fieldDefinition, ContentType $contentType, array $fieldIdentifiers): bool
    {
        if (in_array($fieldDefinition->identifier, $fieldIdentifiers, true)) {
            return true;
        }

        $needle = sprintf('%s/%s', $contentType->identifier, $fieldDefinition->identifier);

        if (in_array($needle, $fieldIdentifiers, true)) {
            return true;
        }

        return false;
    }
}
