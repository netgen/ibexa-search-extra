<?php

namespace Netgen\IbexaSearchExtra\API\Values\Content\Query\Criterion;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator\Specifications;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CustomFieldInterface;
use InvalidArgumentException;
use Netgen\IbexaSearchExtra\API\Values\Content\SpellcheckQuery;
use RuntimeException;

class FullText extends Criterion implements CustomFieldInterface, FulltextSpellcheck
{
    /**
     * Fuzziness of the fulltext search.
     *
     * May be a value between 0. (fuzzy) and 1. (sharp).
     */
    public float $fuzziness = 1.;
    /**
     * Boost for certain fields.
     *
     * Array of boosts to apply for certain fields – the array should look like
     * this:
     *
     * <code>
     *  array(
     *      'title' => 2,
     *      …
     *  )
     * </code>
     *
     * @var array<string, mixed>
     */
    public array $boost = [];
    /**
     * Boost for certain solr fields.
     *
     * Array of boosts to apply for certain fields – the array should look like
     * this:
     *
     * <code>
     *  array(
     *      'meta_content__name_t' => 2,
     *      …
     *  )
     * </code>
     *
     * @var array<string, mixed>
     */
    public array $solrFieldsBoost = [];
    /**
     * Boost for certain content types.
     *
     * Array of boosts to apply for certain content type – the array should look like
     * this:
     *
     * <code>
     *  array(
     *       'content_type_identifier' => array(
     *          'id' => 2,
     *          'boost' => 3
     *      )
     *      …
     *  )
     * </code>
     *
     * @var array<string, mixed>
     */
    public array $contentTypeBoost = [];
    /**
     * Boost for certain fulltext meta fields.
     *
     * Array of boosts to apply for certain meta fields – the array should look like
     * this:
     *
     * <code>
     * array(
     *       'meta_field_key' => 2,
     *       …
     *   )
     * </code>
     *
     * @var array<string, mixed>
     */
    public array $metaFieldsBoost = [];
    /**
     * Analyzer configuration.
     */
    public mixed $analyzers;
    /**
     * Analyzer wildcard handling configuration.
     */
    public mixed $wildcards;
    /**
     * Custom field definitions to query instead of default field.
     *
     * @var array<string, mixed>
     */
    private array $customFields = [];
    /**
     * @param array<string, mixed> $properties
     */
    public function __construct(mixed $value, array $properties = [])
    {
        parent::__construct(null, Operator::LIKE, $value);
        foreach ($properties as $name => $propertyValue) {
            if (!property_exists($this, $name)) {
                throw new InvalidArgumentException(sprintf('Unknown property %s.', $name));
            }
            $this->{$name} = $propertyValue;
        }

    }
    public function getSpecifications(): array
    {
        return [
            new Specifications(Operator::LIKE, Specifications::FORMAT_SINGLE),
        ];
    }
    public function setCustomField(string $type, string $field, string $customField): void
    {
        $this->customFields[$type][$field] = $customField;
    }
    public function getCustomField(string $type, string $field): ?string
    {
        return $this->customFields[$type][$field] ?? null;
    }
    public function getSpellcheckQuery(): SpellcheckQuery
    {
        if (!is_string($this->value)) {
            throw new RuntimeException(
                sprintf('FullText criterion value should be a string, %s given', get_debug_type($this->value)),
            );
        }
        $spellcheckQuery = new SpellcheckQuery();
        $spellcheckQuery->query = $this->value;
        $spellcheckQuery->count = 10;
        return $spellcheckQuery;
    }
}
