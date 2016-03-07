<?php

namespace Mindy\Query;

/**
 * Class OrmUtils
 * @package Mindy\Query
 */
trait OrmUtils
{
    private $_paramsCount = 0;

    /**
     * Makes key for param
     * @param $fieldName
     * @return string
     */
    public function makeParamKey($fieldName)
    {
        $this->_paramsCount += 1;
        $fieldName = str_replace(['`', '{{', '}}', '%', '[[', ']]', '"'], '', $fieldName);
        $fieldName = str_replace('.', '_', $fieldName);
        return $fieldName . $this->_paramsCount;
    }
}
