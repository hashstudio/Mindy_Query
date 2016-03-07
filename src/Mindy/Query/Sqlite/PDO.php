<?php

namespace Mindy\Query\Sqlite;

use Mindy\Query\Exception;

/**
 * Class PDO
 * @package Mindy\Query
 */
class PDO extends \PDO
{
    public function __construct($dsn, $username, $passwd, $options)
    {
        parent::__construct($dsn, $username, $passwd, $options);

        $regexCreated = $this->sqliteCreateFunction('regexp', function($pattern, $value) {
            return preg_match($pattern, $value);
        }, 2);

        if($regexCreated === false) {
            // TODO logging "Failed creating function regexp"
            throw new Exception("Failed creating function regexp");
        }
    }
}
