<?php
namespace Kotchasan\QueryBuilder;

/**
 * Class UpdateBuilder
 *
 * Builder for UPDATE queries.
 *
 * @package Kotchasan\QueryBuilder
 */
class UpdateBuilder extends QueryBuilder
{
    /**
     * {@inheritdoc}
     */
    public function toSql(): string
    {
        if (!empty($this->joins)) {
            // Dropping the join would run the update without its conditions
            throw new \LogicException('UPDATE does not support join(); use a where condition on a subquery instead');
        }

        $sqlBuilder = $this->getSqlBuilder();

        // Build UPDATE statement using SqlBuilder
        $query = 'UPDATE '.$sqlBuilder->quoteIdentifier($this->table).' SET ';

        // Add SET clause
        $sets = [];
        foreach ($this->values as $column => $value) {
            $quotedColumn = $sqlBuilder->quoteIdentifier($column);

            // Check if value is a Sql object or SqlFunction (raw SQL expression)
            if ($value instanceof \Kotchasan\Database\Sql) {
                // Use raw SQL expression directly
                $sets[] = $quotedColumn.' = '.$value->toSql();
                // Merge any bindings from the Sql object
                foreach ($value->getValues([]) as $v) {
                    $this->namedBindings[':'.$column.'_'.$this->paramCounter++] = $v;
                }
            } elseif ($value instanceof \Kotchasan\QueryBuilder\SqlFunction) {
                // Use SqlFunction's toSql method
                $sets[] = $quotedColumn.' = '.$value->toSql();
            } elseif ($value instanceof \Kotchasan\QueryBuilder\RawExpression) {
                // Use RawExpression's toSql method
                $sets[] = $quotedColumn.' = '.$value->toSql();
            } else {
                // Regular value - use placeholder
                // A placeholder name cannot contain the dot of a table.column key
                $placeholder = ':'.preg_replace('/[^a-zA-Z0-9_]/', '_', $column);
                $sets[] = $quotedColumn.' = '.$placeholder;
                $this->namedBindings[$placeholder] = $value;
            }
        }
        $query .= implode(', ', $sets);

        // Add WHERE clauses (use existing logic for now)
        if (!empty($this->wheres)) {
            $whereSql = $this->buildWhereClauses();
            if ($whereSql !== '') {
                $query .= ' WHERE '.$whereSql;
            }
        }

        // Add ORDER BY clause using SqlBuilder
        if (!empty($this->orders)) {
            $query .= ' '.$sqlBuilder->buildOrderByClause($this->orders);
        }

        // Add row LIMIT in the dialect-correct way (MySQL/SQLite LIMIT,
        // SQL Server TOP, PostgreSQL throws — UPDATE..LIMIT is non-portable).
        if ($this->limit !== null) {
            $query = $sqlBuilder->applyUpdateDeleteLimit($query, 'UPDATE', $this->limit);
        }

        return $query;
    }

    // use parent's buildWhereClauses
}
