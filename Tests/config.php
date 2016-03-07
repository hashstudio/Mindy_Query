<?php

return [
    'cubrid' => [
        'class' => '\Mindy\Query\Connection',
        'dsn' => 'cubrid:dbname=demodb;host=localhost;port=33000',
        'username' => 'dba',
        'password' => '',
        'fixture' => __DIR__ . '/data/cubrid.sql',
    ],
    'mysql' => [
        'class' => '\Mindy\Query\Connection',
        'dsn' => 'mysql:host=127.0.0.1;dbname=yiitest',
        'username' => 'travis',
        'password' => '',
        'fixture' => __DIR__ . '/data/mysql.sql',
    ],
    'sqlite' => [
        'class' => '\Mindy\Query\Connection',
        'dsn' => 'sqlite::memory:',
        'fixture' => __DIR__ . '/data/sqlite.sql',
    ],
    'sqlsrv' => [
        'class' => '\Mindy\Query\Connection',
        'dsn' => 'sqlsrv:Server=localhost;Database=test',
        'username' => '',
        'password' => '',
        'fixture' => __DIR__ . '/data/mssql.sql',
    ],
    'pgsql' => [
        'class' => '\Mindy\Query\Connection',
        'dsn' => 'pgsql:host=localhost;dbname=yiitest;port=5432;',
        'username' => 'postgres',
        'password' => 'postgres',
        'fixture' => __DIR__ . '/data/postgres.sql',
    ],
//    'elasticsearch' => [
//        'dsn' => 'elasticsearch://localhost:9200'
//    ],
//    'redis' => [
//        'hostname' => 'localhost',
//        'port' => 6379,
//        'database' => 0,
//        'password' => null,
//    ],
//    'sphinx' => [
//        'sphinx' => [
//            'dsn' => 'mysql:host=127.0.0.1;port=9306;',
//            'username' => 'travis',
//            'password' => '',
//        ],
//        'db' => [
//            'dsn' => 'mysql:host=127.0.0.1;dbname=yiitest',
//            'username' => 'travis',
//            'password' => '',
//            'fixture' => __DIR__ . '/data/sphinx/source.sql',
//        ],
//    ],
//    'mongodb' => [
//        'dsn' => 'mongodb://travis:test@localhost:27017',
//        'defaultDatabaseName' => 'yii2test',
//        'options' => [],
//    ]
];
