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
 * @date 05/02/15 17:07
 */
class PostgreSQLQueryTest extends QueryTest
{
    public $driverName = 'pgsql';

    public function testBooleanValues()
    {
        $db = $this->getConnection();
        $command = $db->createCommand();
        $command->batchInsert('bool_values', ['bool_col'], [[true], [false]])->execute();

        $this->assertEquals(1, $this->newQuery($db)->where('bool_col = TRUE')->count('*'));
        $this->assertEquals(1, $this->newQuery($db)->where('bool_col = FALSE')->count('*'));
        $this->assertEquals(2, $this->newQuery($db)->where('bool_col IN (TRUE, FALSE)')->count('*'));
        $this->assertEquals(1, $this->newQuery($db)->where(['bool_col' => true])->count('*'));
        $this->assertEquals(1, $this->newQuery($db)->where(['bool_col' => false])->count('*'));
        $this->assertEquals(2, $this->newQuery($db)->where(['bool_col' => [true, false]])->count('*'));
        $this->assertEquals(1, $this->newQuery($db)->where('bool_col = :bool_col', ['bool_col' => true])->count('*'));
        $this->assertEquals(1, $this->newQuery($db)->where('bool_col = :bool_col', ['bool_col' => false])->count('*'));
    }

    protected function newQuery($db)
    {
        $query = new Query(['from' => 'bool_values']);
        $query->using($db);
        return $query;
    }
}
