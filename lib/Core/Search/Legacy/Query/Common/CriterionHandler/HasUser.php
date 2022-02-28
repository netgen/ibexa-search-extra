<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Legacy\Query\Common\CriterionHandler;

use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;
use Netgen\IbexaSearchExtra\API\Values\Content\Query\Criterion\HasUser as HasUserCriterion;
use function reset;

/**
 * Handles the HasUser criterion.
 *
 * @see \Netgen\IbexaSearchExtra\API\Values\Content\Query\Criterion\HasUser
 */
final class HasUser extends CriterionHandler
{
    public function accept(Criterion $criterion): bool
    {
        return $criterion instanceof HasUserCriterion;
    }

    public function handle(
        CriteriaConverter $converter,
        QueryBuilder $queryBuilder,
        Criterion $criterion,
        array $languageSettings
    ) {
        $subQuery = $this->connection->createQueryBuilder();
        $hasUser = reset($criterion->value);

        $subQuery
            ->select('t1.contentobject_id')
            ->from('ezuser', 't1')
            ->where(
                $subQuery->expr()->eq('t1.contentobject_id', 'c.id'),
            );

        if ($hasUser === true) {
            return $queryBuilder->expr()->in(
                'c.id',
                $subQuery->getSQL(),
            );
        }

        return $queryBuilder->expr()->notIn(
            'c.id',
            $subQuery->getSQL(),
        );
    }
}
