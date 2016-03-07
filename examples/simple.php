<?php
/**
 * All rights reserved.
 * 
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 26/02/14.02.2014 17:00
 */

if(is_dir(__DIR__ . '/../vendor')) {
    require __DIR__ . '/../vendor/autoload.php';
}
require __DIR__ . '/../src.php';

$connection = new \Mindy\Query\Connection([
    'dsn' => 'sqlite::memory:'
]);

$value = $connection->createCommand('SELECT 1+1')->queryScalar();
assert($value, 2);
