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
 * @date 05/02/15 19:59
 */

class OtherTest extends DatabaseTestCase
{
    /**
     * Issue #11
     * https://github.com/studio107/Mindy_Query/issues/11
     */
    public function testUpdate()
    {
        $query = new Query();
        $query->using('sqlite')->from('customer')->where(['id' => 1]);
        $command = $query->createCommand();
        $command->update('customer', ['status' => 2], $query->where, $query->params);
        $this->assertEquals('UPDATE `customer` SET `status`=2 WHERE `id`=1', $command->getRawSql());
    }

    public function testOne()
    {
        $result = (new Query())->using('sqlite')->from('customer')->where(['status' => 2])->one();
        $this->assertEquals('user3', $result['name']);

        $result = (new Query())->using('sqlite')->from('customer')->where(['status' => 3])->one();
        $this->assertFalse($result);
    }
}
