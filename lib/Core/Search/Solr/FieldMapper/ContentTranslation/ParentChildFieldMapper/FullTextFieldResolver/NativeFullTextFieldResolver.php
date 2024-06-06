<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Solr\FieldMapper\ContentTranslation\ParentChildFieldMapper\FullTextFieldResolver;

use Ibexa\Contracts\Core\Persistence\Content as SPIContent;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as ContentTypeHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Search\Field;
use Ibexa\Contracts\Core\Search\FieldType\FullTextField;
use Ibexa\Contracts\Core\Search\FieldType\TextField;
use Ibexa\Core\Search\Common\FieldRegistry;
use Netgen\IbexaSearchExtra\Core\Search\Solr\FieldMapper\ContentTranslation\ParentChildFieldMapper\FullTextFieldResolver;

final class NativeFullTextFieldResolver implements FullTextFieldResolver
{
    public function __construct(
        private readonly ContentTypeHandler $contentTypeHandler,
        private readonly FieldRegistry $fieldRegistry,
    ) {}

    /**
     * @return \Ibexa\Contracts\Core\Search\Field[]
     */
    public function resolveFields(SPIContent $content, string $languageCode): array
    {
        $fields = [];

        try {
            $contentTypeId = $content->versionInfo->contentInfo->contentTypeId;
            $contentType = $this->contentTypeHandler->load($contentTypeId);
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

                $fieldType = $this->fieldRegistry->getType($field->type);
                $indexFields = $fieldType->getIndexData($field, $fieldDefinition);

                foreach ($indexFields as $indexField) {
                    if ($indexField->getValue() === null) {
                        continue;
                    }

                    if (!$indexField->getType() instanceof FullTextField) {
                        continue;
                    }

                    $fields[] = new Field(
                        'meta_content__text',
                        (string) $indexField->getValue(),
                        new TextField(),
                    );
                }
            }
        }

        return $fields;
    }
}
