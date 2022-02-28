<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Legacy\Query\Common\CriterionHandler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Core\Persistence\TransformationProcessor;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;
use Netgen\IbexaSearchExtra\API\Values\Content\Query\Criterion\UserLogin as UserLoginCriterion;
use RuntimeException;

/**
 * Handles the UserLogin criterion.
 *
 * @see \Netgen\IbexaSearchExtra\API\Values\Content\Query\Criterion\UserLogin
 */
final class UserLogin extends CriterionHandler
{
    protected TransformationProcessor $transformationProcessor;

    public function __construct(Connection $connection, TransformationProcessor $transformationProcessor)
    {
        parent::__construct($connection);

        $this->transformationProcessor = $transformationProcessor;
    }

    public function accept(Criterion $criterion): bool
    {
        return $criterion instanceof UserLoginCriterion;
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
                    't1.login',
                    $queryBuilder->createNamedParameter((array)$criterion->value, Connection::PARAM_STR_ARRAY)
                );
                break;
            case Operator::LIKE:
                $string = $this->prepareLikeString($criterion->value);
                $expression = $subQuery->expr()->like(
                    't1.login',
                    $queryBuilder->createNamedParameter($string, Types::STRING)
                );
                break;
            default:
                throw new RuntimeException(
                    "Unknown operator '{$criterion->operator}' for UserLogin criterion handler"
                );
        }

        $subQuery
            ->select('t1.contentobject_id')
            ->from('ezuser', 't1')
            ->where($expression);

        return $queryBuilder->expr()->in(
            'c.id',
            $subQuery->getSQL()
        );
    }

    /**
     * Returns the given $string prepared for use in SQL LIKE clause.
     *
     * LIKE clause wildcards '%' and '_' contained in the given $string will be escaped.
     */
    protected function prepareLikeString(string $string): string
    {
        $string = addcslashes($this->lowercase($string), '%_');

        return str_replace('*', '%', $string);
    }

    /**
     * Downcases a given string using string transformation processor.
     *
     * @param string $string
     *
     * @return string
     */
    protected function lowerCase($string)
    {
        return $this->transformationProcessor->transformByGroup($string, 'lowercase');
    }
}
