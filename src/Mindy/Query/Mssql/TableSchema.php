<?php

namespace Mindy\Query\Mssql;

/**
 * TableSchema represents the metadata of a database table.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 * @package Mindy\Query
 */
class TableSchema extends \Mindy\Query\TableSchema
{
    /**
     * @var string name of the catalog (database) that this table belongs to.
     * Defaults to null, meaning no catalog (or the current database).
     */
    public $catalogName;
}
