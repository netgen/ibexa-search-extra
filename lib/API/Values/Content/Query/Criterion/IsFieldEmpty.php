<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\API\Values\Content\Query\Criterion;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator\Specifications;
use InvalidArgumentException;

/**
 * IsFieldEmpty criterion matches Content field based on if its value is empty or not.
 */
class IsFieldEmpty extends Criterion
{
    /**
     * Indicates that the field value should be empty.
     *
     * @var int
     */
    public const IS_EMPTY = 0;

    /**
     * Indicates that the field value shouldn't be empty.
     *
     * @var int
     */
    public const IS_NOT_EMPTY = 1;

    /**
     * @throws \InvalidArgumentException
     */
    public function __construct(string $fieldDefinitionIdentifier, int $value)
    {
        if ($value !== self::IS_EMPTY && $value !== self::IS_NOT_EMPTY) {
            throw new InvalidArgumentException(
                "Invalid has field content value $value"
            );
        }

        parent::__construct($fieldDefinitionIdentifier, null, $value);
    }

    public function getSpecifications(): array
    {
        return [
            new Specifications(
                Operator::EQ,
                Specifications::FORMAT_SINGLE,
                Specifications::TYPE_INTEGER
            ),
        ];
    }
}
