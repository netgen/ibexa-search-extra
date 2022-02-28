<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Legacy\Query\Common\CriterionHandler;

use Doctrine\DBAL\Connection;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;
use Netgen\IbexaSearchExtra\API\Values\Content\Query\Criterion\UserId as UserIdCriterion;

/**
 * Handles the UserId criterion.
 *
 * @see \Netgen\IbexaSearchExtra\API\Values\Content\Query\Criterion\UserId
 */
final class UserId extends CriterionHandler
{
    public function accept(Criterion $criterion): bool
    {
        return $criterion instanceof UserIdCriterion;
    }

    public function handle(
        CriteriaConverter $converter,
        QueryBuilder $queryBuilder,
        Criterion $criterion,
        array $languageSettings
    ) {
        $subQuery = $this->connection->createQueryBuilder();

        $subQuery
            ->select('t1.contentobject_id')
            ->from('ezuser', 't1')
            ->where(
                $subQuery->expr()->in(
                    't1.contentobject_id',
                    $queryBuilder->createNamedParameter((array)$criterion->value, Connection::PARAM_INT_ARRAY)
                )
            );

        return $queryBuilder->expr()->in(
            'c.id',
            $subQuery->getSQL()
        );
    }
}
