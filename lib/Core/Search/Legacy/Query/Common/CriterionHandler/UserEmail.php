<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Legacy\Query\Common\CriterionHandler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Types;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;
use Netgen\IbexaSearchExtra\API\Values\Content\Query\Criterion\UserEmail as UserEmailCriterion;
use RuntimeException;
use function addcslashes;
use function str_replace;

/**
 * Handles the UserEmail criterion.
 *
 * @see \Netgen\IbexaSearchExtra\API\Values\Content\Query\Criterion\UserEmail
 */
final class UserEmail extends CriterionHandler
{
    public function accept(Criterion $criterion): bool
    {
        return $criterion instanceof UserEmailCriterion;
    }

    public function handle(
        CriteriaConverter $converter,
        QueryBuilder $queryBuilder,
        Criterion $criterion,
        array $languageSettings
    ) {
        $subQuery = $this->connection->createQueryBuilder();

        switch ($criterion->operator) {
            case Operator::EQ:
            case Operator::IN:
                $expression = $subQuery->expr()->in(
                    't1.email',
                    $queryBuilder->createNamedParameter((array) $criterion->value, Connection::PARAM_STR_ARRAY),
                );

                break;

            case Operator::LIKE:
                $string = $this->prepareLikeString($criterion->value);
                $expression = $subQuery->expr()->like(
                    't1.email',
                    $queryBuilder->createNamedParameter($string, Types::STRING),
                );

                break;

            default:
                throw new RuntimeException(
                    "Unknown operator '{$criterion->operator}' for UserEmail criterion handler",
                );
        }

        $subQuery
            ->select('t1.contentobject_id')
            ->from('ezuser', 't1')
            ->where($expression);

        return $queryBuilder->expr()->in(
            'c.id',
            $subQuery->getSQL(),
        );
    }

    /**
     * Returns the given $string prepared for use in SQL LIKE clause.
     *
     * LIKE clause wildcards '%' and '_' contained in the given $string will be escaped.
     */
    protected function prepareLikeString(string $string): string
    {
        $string = addcslashes($string, '%_');

        return str_replace('*', '%', $string);
    }
}
