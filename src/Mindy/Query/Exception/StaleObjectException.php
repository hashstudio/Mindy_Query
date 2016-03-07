<?php

namespace Mindy\Query\Exception;

/**
 * Class StaleObjectException
 * @package Mindy\Query
 */
class StaleObjectException extends Exception
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Stale Object Exception';
    }
}
