<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Solr\Query\Common\CriterionVisitor;

use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as ContentTypeHandler;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Search\FieldType\BooleanField;
use Ibexa\Contracts\Solr\Query\CriterionVisitor;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Search\Common\FieldNameGenerator;
use Netgen\IbexaSearchExtra\API\Values\Content\Query\Criterion\IsFieldEmpty as IsFieldEmptyCriterion;
use function implode;

/**
 * Visits IsFieldEmpty criterion.
 *
 * @see \Netgen\IbexaSearchExtra\API\Values\Content\Query\Criterion\IsFieldEmpty
 */
final class IsFieldEmpty extends CriterionVisitor
{
    private ContentTypeHandler $contentTypeHandler;
    private FieldNameGenerator $fieldNameGenerator;

    public function __construct(
        ContentTypeHandler $contentTypeHandler,
        FieldNameGenerator $fieldNameGenerator
    ) {
        $this->contentTypeHandler = $contentTypeHandler;
        $this->fieldNameGenerator = $fieldNameGenerator;
    }

    public function canVisit(Criterion $criterion): bool
    {
        return $criterion instanceof IsFieldEmptyCriterion;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentException
     */
    public function visit(Criterion $criterion, ?CriterionVisitor $subVisitor = null): string
    {
        $fieldNames = $this->getFieldNames($criterion);

        if (empty($fieldNames)) {
            throw new InvalidArgumentException(
                '$criterion->target',
                "No searchable fields found for the given criterion target '{$criterion->target}'.",
            );
        }

        $queries = [];

        foreach ($fieldNames as $fieldName) {
            $match = $criterion->value[0] === IsFieldEmptyCriterion::IS_EMPTY ? 'true' : 'false';
            $queries[] = "{$fieldName}:{$match}";
        }

        return '(' . implode(' OR ', $queries) . ')';
    }

    /**
     * Return all field names for the given criterion.
     *
     * @return string[]
     */
    protected function getFieldNames(Criterion $criterion): array
    {
        $fieldDefinitionIdentifier = $criterion->target;
        $fieldMap = $this->contentTypeHandler->getSearchableFieldMap();
        $fieldNames = [];

        foreach ($fieldMap as $contentTypeIdentifier => $fieldIdentifierMap) {
            if (!isset($fieldIdentifierMap[$fieldDefinitionIdentifier])) {
                continue;
            }

            $fieldNames[] = $this->fieldNameGenerator->getTypedName(
                $this->fieldNameGenerator->getName(
                    'ng_is_empty',
                    $fieldDefinitionIdentifier,
                    $contentTypeIdentifier,
                ),
                new BooleanField(),
            );
        }

        return $fieldNames;
    }
}
