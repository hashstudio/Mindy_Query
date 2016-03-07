<?php

namespace Mindy\Query;

use Mindy\Helper\Creator;
use Mindy\Helper\Traits\Accessors;
use Mindy\Helper\Traits\Configurator;
use Mindy\Query\Exception\UnknownDatabase;

/**
 * Class ConnectionManager
 * @package Mindy\Query
 */
class ConnectionManager
{
    use Accessors, Configurator;
    /**
     * @var array
     */
    public $databases = [];
    /**
     * @var string
     */
    public static $defaultDatabase = 'default';
    /**
     * @var Connection[]
     */
    protected static $_databases = [];

    public function init()
    {
        foreach ($this->databases as $name => $config) {
            if (is_array($config)) {
                self::$_databases[$name] = Creator::createObject($config);
            } elseif ($config instanceof Connection) {
                self::$_databases[$name] = $config;
            }
        }
    }

    /**
     * @param null $db
     * @return Connection
     * @throws UnknownDatabase
     */
    public static function getDb($db = null)
    {
        if ($db instanceof Connection) {
            return $db;
        }

        if ($db === null) {
            $db = self::$defaultDatabase;
        }

        if (!isset(self::$_databases[$db])) {
            throw new UnknownDatabase();
        }

        return self::$_databases[$db];
    }

    public static function setDefaultDatabase($name)
    {
        self::$defaultDatabase = $name;
    }

    public static function setDb($name, Connection $db)
    {
        self::$_databases[$name] = $db;
    }
}
