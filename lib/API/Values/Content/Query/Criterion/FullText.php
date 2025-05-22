<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\API\Values\Content\Query\Criterion;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator\Specifications;
use InvalidArgumentException;
use Netgen\IbexaSearchExtra\API\Values\Content\SpellcheckQuery;
use RuntimeException;

class FullText extends Criterion implements FulltextSpellcheck
{
    /**
     * Fuzziness of the fulltext search.
     *
     * Can be a value between 0.0 (fuzzy) and 1.0 (sharp).
     */
    public float $fuzziness = 1.;

    /**
     * Boost for certain fields.
     *
     * Array of boosts to apply for certain fields – the array should look like
     * this:
     *
     * ```php
     * [
     *     'title' => 2,
     * ]
     * ```
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
     * ```php
     * [
     *     'meta_content__name_t' => 2.1,
     * ]
     * ```
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
     * ```php
     * [
     *      'content_type_identifier' => [
     *         'id' => 2.5,
     *         'boost' => 3,
     *     ]
     * ]
     * ```
     *
     * @var array<string, mixed>
     */
    public array $contentTypeBoost = [];

    /**
     * Boost for fulltext meta-fields.
     *
     * Array of boosts to apply for meta-fields – the array should look like this:
     *
     * ```php
     * [
     *      'meta_field_key' => 2,
     * ]
     * ```
     *
     * @var array<string, mixed>
     */
    public array $metaFieldsBoost = [];

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
