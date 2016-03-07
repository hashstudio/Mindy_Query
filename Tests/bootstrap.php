<?php

if(is_dir(__DIR__ . '/../vendor')) {
    require __DIR__ . '/../vendor/autoload.php';
}

require __DIR__ . '/../src.php';
require __DIR__ . '/TestCase.php';
require __DIR__ . '/DatabaseTestCase.php';

require __DIR__ . '/Cases/Query/Base/QueryBuilderTest.php';
require __DIR__ . '/Cases/Query/Base/CommandTest.php';
require __DIR__ . '/Cases/Query/Base/ConnectionTest.php';
require __DIR__ . '/Cases/Query/Base/QueryTest.php';
require __DIR__ . '/Cases/Query/Base/SchemaTest.php';

function d()
{
    $debug = debug_backtrace();
    $args = func_get_args();
    $data = array(
        'data' => $args,
        'debug' => array(
            'file' => isset($debug[0]['file']) ? $debug[0]['file'] : null,
            'line' => isset($debug[0]['line']) ? $debug[0]['line'] : null,
        )
    );
    \Mindy\Helper\Dumper::dump($data);
    die();
}
