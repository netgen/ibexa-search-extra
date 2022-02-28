<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\API\Values\Content\Query\Criterion;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator\Specifications;

/**
 * UserEnabled criterion matches Content based on matching User that is enabled or not.
 */
class UserEnabled extends Criterion
{
    /**
     * @param bool $enabled
     */
    public function __construct($enabled)
    {
        parent::__construct(null, null, $enabled);
    }

    public function getSpecifications(): array
    {
        return [
            new Specifications(
                Operator::EQ,
                Specifications::FORMAT_SINGLE,
                Specifications::TYPE_BOOLEAN,
            ),
        ];
    }
}
