<?php
use Mindy\Query\Query;

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 05/02/15 17:05
 */
class MssqlQueryBuilderTest extends QueryBuilderTest
{
    public $driverName = 'sqlsrv';

    public function testOffsetLimit()
    {
        $expectedQuerySql = 'SELECT `id` FROM `exapmle` OFFSET 5 ROWS FETCH NEXT 10 ROWS ONLY';
        $expectedQueryParams = null;
        $query = new Query();
        $query->select('id')->from('example')->limit(10)->offset(5);
        list($actualQuerySql, $actualQueryParams) = $this->getQueryBuilder()->build($query);
        $this->assertEquals($expectedQuerySql, $actualQuerySql);
        $this->assertEquals($expectedQueryParams, $actualQueryParams);
    }

    public function testLimit()
    {
        $expectedQuerySql = 'SELECT `id` FROM `exapmle` OFFSET 0 ROWS FETCH NEXT 10 ROWS ONLY';
        $expectedQueryParams = null;
        $query = new Query();
        $query->select('id')->from('example')->limit(10);
        list($actualQuerySql, $actualQueryParams) = $this->getQueryBuilder()->build($query);
        $this->assertEquals($expectedQuerySql, $actualQuerySql);
        $this->assertEquals($expectedQueryParams, $actualQueryParams);
    }

    public function testOffset()
    {
        $expectedQuerySql = 'SELECT `id` FROM `exapmle` OFFSET 10 ROWS';
        $expectedQueryParams = null;
        $query = new Query();
        $query->select('id')->from('example')->offset(10);
        list($actualQuerySql, $actualQueryParams) = $this->getQueryBuilder()->build($query);
        $this->assertEquals($expectedQuerySql, $actualQuerySql);
        $this->assertEquals($expectedQueryParams, $actualQueryParams);
    }
}