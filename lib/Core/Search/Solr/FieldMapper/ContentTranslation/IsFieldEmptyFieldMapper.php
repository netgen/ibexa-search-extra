<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Solr\FieldMapper\ContentTranslation;

use Ibexa\Contracts\Core\Persistence\Content;
use Ibexa\Contracts\Core\Persistence\Content\Field as PersistenceField;
use Ibexa\Contracts\Core\Persistence\Content\Type as ContentType;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as ContentTypeHandler;
use Ibexa\Contracts\Core\Search\Field;
use Ibexa\Contracts\Core\Search\FieldType\BooleanField;
use Ibexa\Contracts\Solr\FieldMapper\ContentTranslationFieldMapper;
use Ibexa\Core\Persistence\FieldTypeRegistry;
use Ibexa\Core\Search\Common\FieldNameGenerator;
use function array_merge;

/**
 * Indexes information on whether Content field value is empty.
 */
class IsFieldEmptyFieldMapper extends ContentTranslationFieldMapper
{
    private ContentTypeHandler $contentTypeHandler;
    private FieldNameGenerator $fieldNameGenerator;
    private FieldTypeRegistry $fieldTypeRegistry;

    public function __construct(
        ContentTypeHandler $contentTypeHandler,
        FieldNameGenerator $fieldNameGenerator,
        FieldTypeRegistry $fieldTypeRegistry
    ) {
        $this->contentTypeHandler = $contentTypeHandler;
        $this->fieldNameGenerator = $fieldNameGenerator;
        $this->fieldTypeRegistry = $fieldTypeRegistry;
    }

    public function accept(Content $content, $languageCode): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function mapFields(Content $content, $languageCode): array
    {
        $fieldsGrouped = [[]];
        $contentType = $this->contentTypeHandler->load(
            $content->versionInfo->contentInfo->contentTypeId,
        );

        foreach ($content->fields as $field) {
            if ($field->languageCode !== $languageCode) {
                continue;
            }

            $fieldsGrouped[] = $this->mapField($contentType, $field);
        }

        return array_merge(...$fieldsGrouped);
    }

    private function mapField(ContentType $contentType, PersistenceField $field): array
    {
        $fields = [];

        foreach ($contentType->fieldDefinitions as $fieldDefinition) {
            if ($fieldDefinition->id !== $field->fieldDefinitionId) {
                continue;
            }

            $fieldType = $this->fieldTypeRegistry->getFieldType($fieldDefinition->fieldType);

            $fields[] = new Field(
                $this->fieldNameGenerator->getName(
                    'ng_is_empty',
                    $fieldDefinition->identifier,
                    $contentType->identifier,
                ),
                $fieldType->isEmptyValue($field->value),
                new BooleanField(),
            );
        }

        return $fields;
    }
}
