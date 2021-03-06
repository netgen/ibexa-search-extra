<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\API\Values\Content\Query\Criterion;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator\Specifications;

/**
 * SubdocumentQuery criterion is used to query Content subdocuments of a specific type.
 */
class SubdocumentQuery extends Criterion
{
    /**
     * @param string $documentTypeIdentifier
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion $filter
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($documentTypeIdentifier, Criterion $filter)
    {
        parent::__construct($documentTypeIdentifier, null, $filter);
    }

    public function getSpecifications(): array
    {
        return [
            new Specifications(Operator::EQ, Specifications::FORMAT_SINGLE),
        ];
    }
}
