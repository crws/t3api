<?php

declare(strict_types=1);

namespace SourceBroker\T3api\Filter;

use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use SourceBroker\T3api\Domain\Model\ApiFilter;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnexpectedTypeException;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * Class ContainFilter
 */
class ContainFilter extends AbstractFilter implements OpenApiSupportingFilterInterface
{
    /**
     * @return Parameter[]
     */
    public static function getOpenApiParameters(ApiFilter $apiFilter): array
    {
        return [
            Parameter::create()
                ->name($apiFilter->getParameterName())
                ->schema(Schema::string()),
        ];
    }

    /**
     * @inheritDoc
     */
    public function filterProperty(
        string $property,
        $values,
        QueryInterface $query,
        ApiFilter $apiFilter
    ): ?ConstraintInterface {
        $ids = $this->findContainingIds(
            $property,
            $values,
            $query,
            $apiFilter
        );

        return $query->in('uid', $ids + [0]);
    }

    /**
     * @return int[]
     * @throws UnexpectedTypeException
     */
    protected function findContainingIds(
        string $property,
        array $values,
        QueryInterface $query,
        ApiFilter $apiFilter
    ): array {
        $tableName = $this->getTableName($query);
        $conditions = [];
        $rootAlias = 'o';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);

        if ($this->isPropertyNested($property)) {
            [$tableAlias, $propertyName] = $this->addJoinsForNestedProperty(
                $property,
                $rootAlias,
                $query,
                $queryBuilder
            );
        } else {
            $tableAlias = $rootAlias;
            $propertyName = $property;
        }

        foreach ($values as $value) {
            $conditions[] = sprintf(
                'FIND_IN_SET(%s, %s) > 0',
                $queryBuilder->createNamedParameter($value),
                $queryBuilder->quoteIdentifier(
                    $tableAlias . '.' . GeneralUtility::makeInstance(DataMapper::class)
                        ->convertPropertyNameToColumnName($propertyName, $apiFilter->getFilterClass())
                )
            );
        }

        return $queryBuilder
            ->select($rootAlias . '.uid')
            ->from($tableName, $rootAlias)
            ->andWhere($queryBuilder->expr()->or(...$conditions))
            ->executeQuery()
            ->fetchFirstColumn();
    }
}
