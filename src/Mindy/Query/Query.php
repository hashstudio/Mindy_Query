<?php

namespace Mindy\Query;

use Mindy\Base\Mindy;
use Mindy\Helper\Creator;
use Mindy\Helper\Traits\Accessors;
use Mindy\Helper\Traits\Configurator;

/**
 * Query represents a SELECT SQL statement in a way that is independent of DBMS.
 *
 * Query provides a set of methods to facilitate the specification of different clauses
 * in a SELECT statement. These methods can be chained together.
 *
 * By calling [[createCommand()]], we can get a [[Command]] instance which can be further
 * used to perform/execute the DB query against a database.
 *
 * For example,
 *
 * ~~~
 * $query = new Query;
 * // compose the query
 * $query->select('id, name')
 *     ->from('tbl_user')
 *     ->limit(10);
 * // build and execute the query
 * $rows = $query->all();
 * // alternatively, you can create DB command and execute it
 * $command = $query->createCommand();
 * // $command->sql returns the actual SQL
 * $rows = $command->queryAll();
 * ~~~
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Carsten Brandt <mail@cebe.cc>
 * @since 2.0
 * @package Mindy\Query
 */
class Query implements QueryInterface
{
    use Accessors, Configurator, QueryTrait;

    /**
     * @var \Mindy\Query\Connection
     */
    public $db;
    /**
     * @var array the columns being selected. For example, `['id', 'name']`.
     * This is used to construct the SELECT clause in a SQL statement. If not set, it means selecting all columns.
     * @see select()
     */
    public $select;
    /**
     * @var string additional option that should be appended to the 'SELECT' keyword. For example,
     * in MySQL, the option 'SQL_CALC_FOUND_ROWS' can be used.
     */
    public $selectOption;
    /**
     * @var boolean whether to select distinct rows of data only. If this is set true,
     * the SELECT clause would be changed to SELECT DISTINCT.
     * For Postgresql available array ['foo'] or ['foo' => 'bar'] for DISTINCT ON('foo') 'bar'.
     */
    public $distinct;
    /**
     * @var array the table(s) to be selected from. For example, `['user', 'post']`.
     * This is used to construct the FROM clause in a SQL statement.
     * @see from()
     */
    public $from;
    /**
     * @var array how to group the query results. For example, `['company', 'department']`.
     * This is used to construct the GROUP BY clause in a SQL statement.
     */
    public $groupBy;
    /**
     * @var array how to join with other tables. Each array element represents the specification
     * of one join which has the following structure:
     *
     * ~~~
     * [$joinType, $tableName, $joinCondition]
     * ~~~
     *
     * For example,
     *
     * ~~~
     * [
     *     ['INNER JOIN', 'user', 'user.id = author_id'],
     *     ['LEFT JOIN', 'team', 'team.id = team_id'],
     * ]
     * ~~~
     */
    public $join;
    /**
     * @var string|array the condition to be applied in the GROUP BY clause.
     * It can be either a string or an array. Please refer to [[where()]] on how to specify the condition.
     */
    public $having;
    /**
     * @var array this is used to construct the UNION clause(s) in a SQL statement.
     * Each array element is an array of the following structure:
     *
     * - `query`: either a string or a [[Query]] object representing a query
     * - `all`: boolean, whether it should be `UNION ALL` or `UNION`
     */
    public $union;
    /**
     * @var array list of query parameter values indexed by parameter placeholders.
     * For example, `[':name' => 'Dan', ':age' => 31]`.
     */
    public $params = [];
    /**
     * @var integer the default number of seconds that query results can remain valid in cache.
     * Use 0 to indicate that the cached data will never expire. And use a negative number to indicate
     * query cache should not be used.
     * @see cache()
     */
    public $queryCacheDuration;
    /**
     * @var \Mindy\Cache\Dependency the dependency to be associated with the cached query result for this command
     * @see cache()
     */
    public $queryCacheDependency;
    /**
     * @var bool
     */
    public $noCache = false;

    /**
     * Creates a DB command that can be used to execute this query.
     * @return Command the created DB command instance.
     */
    public function createCommand()
    {
        $db = $this->getDb();
        list ($sql, $params) = $db->getQueryBuilder()->build($this);
        $command = $db->createCommand($sql, $params);
        if ($this->queryCacheDuration) {
            $command->cache($this->queryCacheDuration, $this->queryCacheDependency);
        } elseif ($this->noCache) {
            $command->noCache();
        }
        return $command;
    }

    /**
     * Enables query cache for this command.
     * @param integer $duration the number of seconds that query result of this command can remain valid in the cache.
     * If this is not set, the value of [[Connection::queryCacheDuration]] will be used instead.
     * Use 0 to indicate that the cached data will never expire.
     * @param \Mindy\Cache\Dependency $dependency the cache dependency associated with the cached query result.
     * @return static the command object itself
     */
    public function cache($duration = null, $dependency = null)
    {
        $this->queryCacheDuration = $duration === null ? $this->db->queryCacheDuration : $duration;
        $this->queryCacheDependency = $dependency;
        return $this;
    }

    /**
     * Disables query cache for this command.
     * @return static the command object itself
     */
    public function noCache()
    {
        $this->queryCacheDuration = -1;
        return $this;
    }

    /**
     * Prepares for building SQL.
     * This method is called by [[QueryBuilder]] when it starts to build SQL from a query object.
     * You may override this method to do some final preparation work when converting a query into a SQL statement.
     * @param QueryBuilder $builder
     * @return Query a prepared query instance which will be used by [[QueryBuilder]] to build the SQL
     */
    public function prepare($builder)
    {
        return $this;
    }

    /**
     * Starts a batch query.
     *
     * A batch query supports fetching data in batches, which can keep the memory usage under a limit.
     * This method will return a [[BatchQueryResult]] object which implements the `Iterator` interface
     * and can be traversed to retrieve the data in batches.
     *
     * For example,
     *
     * ```php
     * $query = (new Query)->from('user');
     * foreach ($query->batch() as $rows) {
     *     // $rows is an array of 10 or fewer rows from user table
     * }
     * ```
     *
     * @param integer $batchSize the number of records to be fetched in each batch.
     * @return BatchQueryResult the batch query result. It implements the `Iterator` interface
     * and can be traversed to retrieve the data in batches.
     */
    public function batch($batchSize = 100)
    {
        return Creator::createObject([
            'class' => BatchQueryResult::className(),
            'query' => $this,
            'batchSize' => $batchSize,
            'db' => $this->db,
            'each' => false,
        ]);
    }

    /**
     * Starts a batch query and retrieves data row by row.
     * This method is similar to [[batch()]] except that in each iteration of the result,
     * only one row of data is returned. For example,
     *
     * ```php
     * $query = (new Query)->from('user');
     * foreach ($query->each() as $row) {
     * }
     * ```
     *
     * @param integer $batchSize the number of records to be fetched in each batch.
     * @return BatchQueryResult the batch query result. It implements the `Iterator` interface
     * and can be traversed to retrieve the data in batches.
     */
    public function each($batchSize = 100)
    {
        return Creator::createObject([
            'class' => BatchQueryResult::className(),
            'query' => $this,
            'batchSize' => $batchSize,
            'db' => $this->db,
            'each' => true,
        ]);
    }

    /**
     * Executes the query and returns all results as an array.
     * @return array the query results. If the query results in nothing, an empty array will be returned.
     */
    public function all()
    {
        $rows = $this->createCommand()->queryAll();
        return $this->populate($rows);
    }

    /**
     * Converts the raw query results into the format as specified by this query.
     * This method is internally used to convert the data fetched from database
     * into the format as required by this query.
     * @param array $rows the raw query result from database
     * @return array the converted query result
     */
    public function populate($rows)
    {
        if ($this->indexBy === null) {
            return $rows;
        }
        $result = [];
        foreach ($rows as $row) {
            if (is_string($this->indexBy)) {
                $key = $row[$this->indexBy];
            } else {
                $key = call_user_func($this->indexBy, $row);
            }
            $result[$key] = $row;
        }
        return $result;
    }

    /**
     * Executes the query and returns a single row of result.
     * @return array|boolean the first row (in terms of an array) of the query result. False is returned if the query
     * results in nothing.
     */
    public function one()
    {
        return $this->createCommand()->queryOne();
    }

    /**
     * Returns the query result as a scalar value.
     * The value returned will be the first column in the first row of the query results.
     * @return string|boolean the value of the first column in the first row of the query result.
     * False is returned if the query result is empty.
     */
    public function scalar()
    {
        return $this->createCommand()->queryScalar();
    }

    /**
     * Executes the query and returns the first column of the result.
     * @return array the first column of the query result. An empty array is returned if the query results in nothing.
     */
    public function column()
    {
        return $this->createCommand()->queryColumn();
    }

    /**
     * Returns the number of records.
     * @param string $q the COUNT expression. Defaults to '*'.
     * Make sure you properly quote column names in the expression.
     * If this parameter is not given (or null), the `db` application component will be used.
     * @return integer|string number of records. The result may be a string depending on the
     * underlying database engine and to support integer values higher than a 32bit PHP integer can handle.
     */
    public function count($q = '*')
    {
        if ($this->distinct) {
            // Prevent build distinct twice in [[QueryBuilder:buildSelect]]
            $this->distinct = null;
            return $this->queryScalar("COUNT(DISTINCT $q)");
        } else {
            return $this->queryScalar("COUNT($q)");
        }
    }

    /**
     * Returns the sum of the specified column values.
     * @param string $q the column name or expression.
     * Make sure you properly quote column names in the expression.
     * @return mixed the sum of the specified column values.
     */
    public function sum($q)
    {
        if ($this->distinct) {
            // Prevent build distinct twice in [[QueryBuilder:buildSelect]]
            $this->distinct = null;
            return $this->queryScalar("SUM(DISTINCT $q)");
        } else {
            return $this->queryScalar("SUM($q)");
        }
    }

    /**
     * Returns the sum of the specified column values.
     * @param string $q the column name or expression.
     * Make sure you properly quote column names in the expression.
     * @return mixed the sum of the specified column values.
     */
    public function sumSql($q)
    {
        if ($this->distinct) {
            // Prevent build distinct twice in [[QueryBuilder:buildSelect]]
            $this->distinct = null;
            return $this->makeQueryScalar("SUM(DISTINCT $q)")->getRawSql();
        } else {
            return $this->makeQueryScalar("SUM($q)")->getRawSql();
        }
    }

    /**
     * Returns the average of the specified column values.
     * @param string $q the column name or expression.
     * Make sure you properly quote column names in the expression.
     * @return mixed the average of the specified column values.
     */
    public function average($q)
    {
        if ($this->distinct) {
            // Prevent build distinct twice in [[QueryBuilder:buildSelect]]
            $this->distinct = null;
            return $this->queryScalar("AVG(DISTINCT $q)");
        } else {
            return $this->queryScalar("AVG($q)");
        }
    }

    /**
     * Returns the average of the specified column values.
     * @param string $q the column name or expression.
     * Make sure you properly quote column names in the expression.
     * @return mixed the average of the specified column values.
     */
    public function averageSql($q)
    {
        if ($this->distinct) {
            // Prevent build distinct twice in [[QueryBuilder:buildSelect]]
            $this->distinct = null;
            return $this->makeQueryScalar("AVG(DISTINCT $q)")->getRawSql();
        } else {
            return $this->makeQueryScalar("AVG($q)")->getRawSql();
        }
    }

    /**
     * Returns the minimum of the specified column values.
     * @param string $q the column name or expression.
     * Make sure you properly quote column names in the expression.
     * @return mixed the minimum of the specified column values.
     */
    public function min($q)
    {
        if ($this->distinct) {
            // Prevent build distinct twice in [[QueryBuilder:buildSelect]]
            $this->distinct = null;
            return $this->queryScalar("MIN(DISTINCT $q)");
        } else {
            return $this->queryScalar("MIN($q)");
        }
    }

    /**
     * Returns the minimum of the specified column values.
     * @param string $q the column name or expression.
     * Make sure you properly quote column names in the expression.
     * @return mixed the minimum of the specified column values.
     */
    public function minSql($q)
    {
        if ($this->distinct) {
            // Prevent build distinct twice in [[QueryBuilder:buildSelect]]
            $this->distinct = null;
            return $this->makeQueryScalar("MIN(DISTINCT $q)")->getRawSql();
        } else {
            return $this->makeQueryScalar("MIN($q)")->getRawSql();
        }
    }

    /**
     * Returns the maximum of the specified column values.
     * @param string $q the column name or expression.
     * Make sure you properly quote column names in the expression.
     * @return mixed the maximum of the specified column values.
     */
    public function max($q)
    {
        if ($this->distinct) {
            // Prevent build distinct twice in [[QueryBuilder:buildSelect]]
            $this->distinct = null;
            return $this->queryScalar("MAX(DISTINCT $q)");
        } else {
            return $this->queryScalar("MAX($q)");
        }
    }

    /**
     * Returns the maximum of the specified column values.
     * @param string $q the column name or expression.
     * Make sure you properly quote column names in the expression.
     * @return mixed the maximum of the specified column values.
     */
    public function maxSql($q)
    {
        if ($this->distinct) {
            // Prevent build distinct twice in [[QueryBuilder:buildSelect]]
            $this->distinct = null;
            return $this->makeQueryScalar("MAX(DISTINCT $q)")->getRawSql();
        } else {
            return $this->makeQueryScalar("MAX($q)")->getRawSql();
        }
    }

    /**
     * Returns a value indicating whether the query result contains any row of data.
     * @return boolean whether the query result contains any row of data.
     */
    public function exists()
    {
        $select = $this->select;
        $this->select = [new Expression('1')];
        $command = $this->createCommand();
        $this->select = $select;
        return $command->queryScalar() !== false;
    }

    /**
     * Queries a scalar value by setting [[select]] first.
     * Restores the value of select to make this query reusable.
     * @param string|Expression $selectExpression
     * @param Connection|null $db
     * @return \Mindy\Query\Command
     */
    protected function makeQueryScalar($selectExpression)
    {
        $orderBy = $this->orderBy;
        $select = $this->select;
        $limit = $this->limit;
        $offset = $this->offset;
        $this->select = [$selectExpression];
        $this->orderBy = null;
        $this->limit = null;
        $this->offset = null;
        $command = $this->createCommand();
        $this->select = $select;
        $this->limit = $limit;
        $this->offset = $offset;
        $this->orderBy = $orderBy;

        if (empty($this->groupBy) && empty($this->union) && !$this->distinct) {
            return $command;
        } else {
            $query = new Query();
            $query->using($command->db);
            $query->select([$selectExpression]);
            $query->from(['c' => $this]);
            return $query->createCommand();
        }
    }

    /**
     * Queries a scalar value by setting [[select]] first.
     * Restores the value of select to make this query reusable.
     * @param string|Expression $selectExpression
     * @param Connection|null $db
     * @return bool|string
     */
    protected function queryScalar($selectExpression)
    {
        return $this->makeQueryScalar($selectExpression)->queryScalar();
    }

    /**
     * Sets the SELECT part of the query.
     * @param string|array $columns the columns to be selected.
     * Columns can be specified in either a string (e.g. "id, name") or an array (e.g. ['id', 'name']).
     * Columns can be prefixed with table names (e.g. "user.id") and/or contain column aliases (e.g. "user.id AS user_id").
     * The method will automatically quote the column names unless a column contains some parenthesis
     * (which means the column contains a DB expression).
     *
     * Note that if you are selecting an expression like `CONCAT(first_name, ' ', last_name)`, you should
     * use an array to specify the columns. Otherwise, the expression may be incorrectly split into several parts.
     *
     * When the columns are specified as an array, you may also use array keys as the column aliases (if a column
     * does not need alias, do not use a string key).
     *
     * Starting from version 2.0.1, you may also select sub-queries as columns by specifying each such column
     * as a `Query` instance representing the sub-query.
     *
     * @param string $option additional option that should be appended to the 'SELECT' keyword. For example,
     * in MySQL, the option 'SQL_CALC_FOUND_ROWS' can be used.
     * @return static the query object itself
     */
    public function select($columns, $option = null)
    {
        if (!is_array($columns)) {
            $columns = preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);
        }
        $this->select = $columns;
        $this->selectOption = $option;
        return $this;
    }

    /**
     * Add more columns to the SELECT part of the query.
     * @param string|array $columns the columns to add to the select.
     * @return static the query object itself
     * @see select()
     */
    public function addSelect($columns)
    {
        if (!is_array($columns)) {
            $columns = preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);
        }
        if ($this->select === null) {
            $this->select = $columns;
        } else {
            $this->select = array_merge($this->select, $columns);
        }
        return $this;
    }

    /**
     * Sets the value indicating whether to SELECT DISTINCT or not.
     * @param mixed $fields whether to SELECT DISTINCT or not.
     * For Postgresql available array ['foo'] or ['foo' => 'bar'] for DISTINCT ON('foo') 'bar'.
     * @return static the query object itself
     */
    public function distinct($fields = true)
    {
        $this->distinct = $fields;
        return $this;
    }

    /**
     * Sets the FROM part of the query.
     * @param string|array $tables the table(s) to be selected from. This can be either a string (e.g. `'user'`)
     * or an array (e.g. `['user', 'profile']`) specifying one or several table names.
     * Table names can contain schema prefixes (e.g. `'public.user'`) and/or table aliases (e.g. `'user u'`).
     * The method will automatically quote the table names unless it contains some parenthesis
     * (which means the table is given as a sub-query or DB expression).
     *
     * When the tables are specified as an array, you may also use the array keys as the table aliases
     * (if a table does not need alias, do not use a string key).
     *
     * Use a Query object to represent a sub-query. In this case, the corresponding array key will be used
     * as the alias for the sub-query.
     *
     * @return static the query object itself
     */
    public function from($tables)
    {
        if (!is_array($tables)) {
            $tables = preg_split('/\s*,\s*/', trim($tables), -1, PREG_SPLIT_NO_EMPTY);
        }
        $this->from = $tables;
        return $this;
    }

    /**
     * Sets the WHERE part of the query.
     *
     * The method requires a `$condition` parameter, and optionally a `$params` parameter
     * specifying the values to be bound to the query.
     *
     * The `$condition` parameter should be either a string (e.g. `'id=1'`) or an array.
     *
     * @inheritdoc
     *
     * @param string|array $condition the conditions that should be put in the WHERE part.
     * @param array $params the parameters (name => value) to be bound to the query.
     * @return static the query object itself
     * @see andWhere()
     * @see orWhere()
     * @see QueryInterface::where()
     */
    public function where($condition, $params = [])
    {
        $this->where = $condition;
        $this->addParams($params);
        return $this;
    }

    /**
     * Adds an additional WHERE condition to the existing one.
     * The new condition and the existing one will be joined using the 'AND' operator.
     * @param string|array $condition the new WHERE condition. Please refer to [[where()]]
     * on how to specify this parameter.
     * @param array $params the parameters (name => value) to be bound to the query.
     * @return static the query object itself
     * @see where()
     * @see orWhere()
     */
    public function andWhere($condition, $params = [])
    {
        if ($this->where === null) {
            $this->where = $condition;
        } else {
            $this->where = ['and', $this->where, $condition];
        }
        $this->addParams($params);
        return $this;
    }

    /**
     * Adds an additional WHERE condition to the existing one.
     * The new condition and the existing one will be joined using the 'OR' operator.
     * @param string|array $condition the new WHERE condition. Please refer to [[where()]]
     * on how to specify this parameter.
     * @param array $params the parameters (name => value) to be bound to the query.
     * @return static the query object itself
     * @see where()
     * @see andWhere()
     */
    public function orWhere($condition, $params = [])
    {
        if ($this->where === null) {
            $this->where = $condition;
        } else {
            $this->where = ['or', $this->where, $condition];
        }
        $this->addParams($params);
        return $this;
    }

    /**
     * Appends a JOIN part to the query.
     * The first parameter specifies what type of join it is.
     * @param string $type the type of join, such as INNER JOIN, LEFT JOIN.
     * @param string|array $table the table to be joined.
     *
     * Use string to represent the name of the table to be joined.
     * Table name can contain schema prefix (e.g. 'public.user') and/or table alias (e.g. 'user u').
     * The method will automatically quote the table name unless it contains some parenthesis
     * (which means the table is given as a sub-query or DB expression).
     *
     * Use array to represent joining with a sub-query. The array must contain only one element.
     * The value must be a Query object representing the sub-query while the corresponding key
     * represents the alias for the sub-query.
     *
     * @param string|array $on the join condition that should appear in the ON part.
     * Please refer to [[where()]] on how to specify this parameter.
     * @param array $params the parameters (name => value) to be bound to the query.
     * @return Query the query object itself
     */
    public function join($type, $table, $on = '', $params = [])
    {
        $this->join[] = [$type, $table, $on];
        return $this->addParams($params);
    }

    /**
     * Appends an INNER JOIN part to the query.
     * @param string|array $table the table to be joined.
     *
     * Use string to represent the name of the table to be joined.
     * Table name can contain schema prefix (e.g. 'public.user') and/or table alias (e.g. 'user u').
     * The method will automatically quote the table name unless it contains some parenthesis
     * (which means the table is given as a sub-query or DB expression).
     *
     * Use array to represent joining with a sub-query. The array must contain only one element.
     * The value must be a Query object representing the sub-query while the corresponding key
     * represents the alias for the sub-query.
     *
     * @param string|array $on the join condition that should appear in the ON part.
     * Please refer to [[where()]] on how to specify this parameter.
     * @param array $params the parameters (name => value) to be bound to the query.
     * @return Query the query object itself
     */
    public function innerJoin($table, $on = '', $params = [])
    {
        $this->join[] = ['INNER JOIN', $table, $on];
        return $this->addParams($params);
    }

    /**
     * Appends a LEFT OUTER JOIN part to the query.
     * @param string|array $table the table to be joined.
     *
     * Use string to represent the name of the table to be joined.
     * Table name can contain schema prefix (e.g. 'public.user') and/or table alias (e.g. 'user u').
     * The method will automatically quote the table name unless it contains some parenthesis
     * (which means the table is given as a sub-query or DB expression).
     *
     * Use array to represent joining with a sub-query. The array must contain only one element.
     * The value must be a Query object representing the sub-query while the corresponding key
     * represents the alias for the sub-query.
     *
     * @param string|array $on the join condition that should appear in the ON part.
     * Please refer to [[where()]] on how to specify this parameter.
     * @param array $params the parameters (name => value) to be bound to the query
     * @return Query the query object itself
     */
    public function leftJoin($table, $on = '', $params = [])
    {
        $this->join[] = ['LEFT JOIN', $table, $on];
        return $this->addParams($params);
    }

    /**
     * Appends a RIGHT OUTER JOIN part to the query.
     * @param string|array $table the table to be joined.
     *
     * Use string to represent the name of the table to be joined.
     * Table name can contain schema prefix (e.g. 'public.user') and/or table alias (e.g. 'user u').
     * The method will automatically quote the table name unless it contains some parenthesis
     * (which means the table is given as a sub-query or DB expression).
     *
     * Use array to represent joining with a sub-query. The array must contain only one element.
     * The value must be a Query object representing the sub-query while the corresponding key
     * represents the alias for the sub-query.
     *
     * @param string|array $on the join condition that should appear in the ON part.
     * Please refer to [[where()]] on how to specify this parameter.
     * @param array $params the parameters (name => value) to be bound to the query
     * @return Query the query object itself
     */
    public function rightJoin($table, $on = '', $params = [])
    {
        $this->join[] = ['RIGHT JOIN', $table, $on];
        return $this->addParams($params);
    }

    /**
     * Sets the GROUP BY part of the query.
     * @param string|array $columns the columns to be grouped by.
     * Columns can be specified in either a string (e.g. "id, name") or an array (e.g. ['id', 'name']).
     * The method will automatically quote the column names unless a column contains some parenthesis
     * (which means the column contains a DB expression).
     * @return static the query object itself
     * @see addGroupBy()
     */
    public function groupBy($columns)
    {
        if (!is_array($columns)) {
            $columns = preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);
        }
        $this->groupBy = $columns;
        return $this;
    }

    /**
     * Adds additional group-by columns to the existing ones.
     * @param string|array $columns additional columns to be grouped by.
     * Columns can be specified in either a string (e.g. "id, name") or an array (e.g. ['id', 'name']).
     * The method will automatically quote the column names unless a column contains some parenthesis
     * (which means the column contains a DB expression).
     * @return static the query object itself
     * @see groupBy()
     */
    public function addGroupBy($columns)
    {
        if (!is_array($columns)) {
            $columns = preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);
        }
        if ($this->groupBy === null) {
            $this->groupBy = $columns;
        } else {
            $this->groupBy = array_merge($this->groupBy, $columns);
        }
        return $this;
    }

    /**
     * Sets the HAVING part of the query.
     * @param string|array $condition the conditions to be put after HAVING.
     * Please refer to [[where()]] on how to specify this parameter.
     * @param array $params the parameters (name => value) to be bound to the query.
     * @return static the query object itself
     * @see andHaving()
     * @see orHaving()
     */
    public function having($condition, $params = [])
    {
        $this->having = $condition;
        $this->addParams($params);
        return $this;
    }

    /**
     * Adds an additional HAVING condition to the existing one.
     * The new condition and the existing one will be joined using the 'AND' operator.
     * @param string|array $condition the new HAVING condition. Please refer to [[where()]]
     * on how to specify this parameter.
     * @param array $params the parameters (name => value) to be bound to the query.
     * @return static the query object itself
     * @see having()
     * @see orHaving()
     */
    public function andHaving($condition, $params = [])
    {
        if ($this->having === null) {
            $this->having = $condition;
        } else {
            $this->having = ['and', $this->having, $condition];
        }
        $this->addParams($params);
        return $this;
    }

    /**
     * Adds an additional HAVING condition to the existing one.
     * The new condition and the existing one will be joined using the 'OR' operator.
     * @param string|array $condition the new HAVING condition. Please refer to [[where()]]
     * on how to specify this parameter.
     * @param array $params the parameters (name => value) to be bound to the query.
     * @return static the query object itself
     * @see having()
     * @see andHaving()
     */
    public function orHaving($condition, $params = [])
    {
        if ($this->having === null) {
            $this->having = $condition;
        } else {
            $this->having = ['or', $this->having, $condition];
        }
        $this->addParams($params);
        return $this;
    }

    /**
     * Appends a SQL statement using UNION operator.
     * @param string|Query $sql the SQL statement to be appended using UNION
     * @param boolean $all TRUE if using UNION ALL and FALSE if using UNION
     * @return static the query object itself
     */
    public function union($sql, $all = false)
    {
        $this->union[] = ['query' => $sql, 'all' => $all];
        return $this;
    }

    /**
     * Sets the parameters to be bound to the query.
     * @param array $params list of query parameter values indexed by parameter placeholders.
     * For example, `[':name' => 'Dan', ':age' => 31]`.
     * @return static the query object itself
     * @see addParams()
     */
    public function params($params)
    {
        $this->params = $params;
        return $this;
    }

    /**
     * Adds additional parameters to be bound to the query.
     * @param array $params list of query parameter values indexed by parameter placeholders.
     * For example, `[':name' => 'Dan', ':age' => 31]`.
     * @return static the query object itself
     * @see params()
     */
    public function addParams($params)
    {
        if (!empty($params)) {
            if (empty($this->params)) {
                $this->params = $params;
            } else {
                foreach ($params as $name => $value) {
                    if (is_integer($name)) {
                        $this->params[] = $value;
                    } else {
                        $this->params[$name] = $value;
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Creates a new Query object and copies its property values from an existing one.
     * The properties being copies are the ones to be used by query builders.
     * @param Query $from the source query object
     * @return Query the new Query object
     */
    public static function create($from)
    {
        return new self([
            'where' => $from->where,
            'limit' => $from->limit,
            'offset' => $from->offset,
            'orderBy' => $from->orderBy,
            'indexBy' => $from->indexBy,
            'select' => $from->select,
            'selectOption' => $from->selectOption,
            'distinct' => $from->distinct,
            'from' => $from->from,
            'groupBy' => $from->groupBy,
            'join' => $from->join,
            'having' => $from->having,
            'union' => $from->union,
            'params' => $from->params,
        ]);
    }

    /**
     * @param null|string $db
     * @return $this
     */
    public function using($db = null)
    {
        $this->db = $db;
        return $this;
    }

    /**
     * @return \Mindy\Query\Connection
     */
    public function getDb()
    {
        /** @var \Mindy\Query\ConnectionManager $db */
        $db = Mindy::app()->db;
        return $db->getDb($this->db);
    }

    /**
     * @param string $q
     * @return string
     */
    public function countSql($q = '*')
    {
        if ($this->distinct) {
            // Prevent build distinct twice in [[QueryBuilder:buildSelect]]
            $this->distinct = null;
            $command = $this->makeQueryScalar("COUNT(DISTINCT $q)");
        } else {
            $command = $this->makeQueryScalar("COUNT($q)");
        }
        return $command->getRawSql();
    }

    /**
     * @return string
     */
    public function allSql()
    {
        return $this->querySql();
    }

    /**
     * @return string
     */
    public function getSql()
    {
        return $this->querySql();
    }

    /**
     * @return string
     */
    public function querySql()
    {
        return $this->createCommand()->getRawSql();
    }

    /**
     * Updates the whole table using the provided counter changes and conditions.
     * For example, to increment all customers' age by 1,
     *
     * ~~~
     * Customer::objects()->updateCounters(['age' => 1]);
     * ~~~
     *
     * @param $tableName
     * @param array $counters the counters to be updated (attribute name => increment value).
     * Use negative values if you want to decrement the counters.
     * @throws Exception
     * @return integer the number of rows updated
     */
    public function updateCountersInternal($tableName, $counters)
    {
        $n = 0;
        $newCounters = [];
        foreach ($counters as $name => $value) {
            $name = $this->getDb()->quoteColumnName($name);
            $newCounters[$name] = new Expression("$name+:bp{$n}", [":bp{$n}" => $value]);
            $n++;
        }
        $command = $this->createCommand()->update($tableName, $newCounters, $this->where, $this->params);
        return $command->execute();
    }

    /**
     * Updates the whole table using the provided attribute values and conditions.
     * For example, to change the status to be 1 for all customers whose status is 2:
     *
     * ~~~
     * Customer::objects()->filter(['status' => 2])->update(['status' => 1]);
     * ~~~
     *
     * @param $tableName
     * @param array $attributes attribute values (name-value pairs) to be saved into the table
     * @throws Exception
     * @return integer the number of rows updated
     */
    public function updateAll($tableName, array $attributes)
    {
        $command = $this->createCommand();
        $command->update($tableName, $attributes, $this->where, $this->params);
        return $command->execute();
    }

    /**
     * @return Schema
     * @throws \Mindy\Exception\NotSupportedException
     */
    public function getSchema()
    {
        return $this->getDb()->getSchema();
    }
}
