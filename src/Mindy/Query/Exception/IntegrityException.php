<?php

namespace Mindy\Query\Exception;

/**
 * Class IntegrityException
 * @package Mindy\Query
 */
class IntegrityException extends Exception
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Integrity constraint violation';
    }
}
