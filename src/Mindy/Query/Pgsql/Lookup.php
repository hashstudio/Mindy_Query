<?php

namespace Mindy\Query\Pgsql;

/**
 * Class Lookup
 * @package Mindy\Query
 */
trait Lookup
{
    public $dateTimeFormat = "Y-m-d H:i:s";

    /**
     * @param $field
     * @param $value
     * @return array
     */
    public function buildRange($field, $value)
    {
        list($start, $end) = $value;
        return [['between', $field, $start, $end], []];
    }

    /**
     * @param $field
     * @param $value
     * @return array
     */
    public function buildIendswith($field, $value)
    {
        return [['ilike', $field, '%' . $value, false], []];
    }

    /**
     * @param $field
     * @param $value
     * @return array
     */
    public function buildEndswith($field, $value)
    {
        return [['like', $field, '%' . $value, false], []];
    }

    /**
     * @param $field
     * @param $value
     * @return array
     */
    public function buildIStartswith($field, $value)
    {
        return [['ilike', $field, $value . '%', false], []];
    }

    /**
     * @param $field
     * @param $value
     * @return array
     */
    public function buildIcontains($field, $value)
    {
        return [['ilike', $field, $value], []];
    }

    /**
     * @param $field
     * @param $value
     * @return array
     */
    public function buildStartswith($field, $value)
    {
        return [['like', $field, $value . '%', false], []];
    }

    /**
     * @param $field
     * @param $value
     * @return array
     */
    public function buildLte($field, $value)
    {
        /* @var $this \Mindy\Query\QueryBuilder */
        $paramName = $this->makeParamKey($field);
        return [['and', $this->db->quoteColumnName($field) . ' <= :' . $paramName], [':' . $paramName => $value]];
    }

    /**
     * @param $field
     * @param $value
     * @return array
     */
    public function buildLt($field, $value)
    {
        /* @var $this \Mindy\Query\QueryBuilder */
        $paramName = $this->makeParamKey($field);
        return [['and', $this->db->quoteColumnName($field) . ' < :' . $paramName], [':' . $paramName => $value]];
    }

    /**
     * @param $field
     * @param $value
     * @return array
     */
    public function buildContains($field, $value)
    {
        return [['like', $field, $value], []];
    }

    /**
     * @param $field
     * @param $value
     * @return array
     */
    public function buildExact($field, $value)
    {
        return [[$field => $value], []];
    }

    /**
     * @param $field
     * @param $value
     * @return array
     */
    public function buildIsnull($field, $value)
    {
        if ($value) {
            return [[$field => null], []];
        } else {
            return [['not', [$field => null]], []];
        }
    }

    /**
     * @param $field
     * @param $value
     * @return array
     */
    public function buildIn($field, $value)
    {
        if (is_object($value) && ($value instanceof \Mindy\Orm\QuerySet || $value instanceof \Mindy\Orm\Manager)) {
            return [['and', $this->db->quoteColumnName($field) . ' IN (' . $value->allSql() . ')'], []];
        } else {
            return [['in', $field, $value], []];
        }
    }

    /**
     * @param $field
     * @param $value
     * @return array
     */
    public function buildGte($field, $value)
    {
        /* @var $this \Mindy\Query\QueryBuilder */
        $paramName = $this->makeParamKey($field);
        return [['and', $this->db->quoteColumnName($field) . ' >= :' . $paramName], [':' . $paramName => $value]];
    }

    /**
     * @param $field
     * @param $value
     * @return array
     */
    public function buildGt($field, $value)
    {
        /* @var $this \Mindy\Query\QueryBuilder */
        $paramName = $this->makeParamKey($field);
        return [['and', $this->db->quoteColumnName($field) . ' > :' . $paramName], [':' . $paramName => $value]];
    }

    /**
     * @param $field
     * @param $value
     * @return array
     * @throws \Mindy\Exception\Exception
     */
    public function buildIregex($field, $value)
    {
        /* @var $this \Mindy\Query\QueryBuilder */
        if (!is_string($value)) {
            $value = (string)$value;
        }

        $paramName = $this->makeParamKey($field);
        return [['and', $this->db->quoteColumnName($field) . " ~* :" . $paramName], [':' . $paramName => $value]];
    }

    /**
     * @param $field
     * @param $value
     * @return array
     * @throws \Mindy\Exception\Exception
     */
    public function buildRegex($field, $value)
    {
        /* @var $this \Mindy\Query\QueryBuilder */
        if (!is_string($value)) {
            $value = (string)$value;
        }

        $paramName = $this->makeParamKey($field);
        return [['and', $this->db->quoteColumnName($field) . " ~ :" . $paramName], [':' . $paramName => $value]];
    }

    /**
     * @param $field
     * @param $value
     * @return array
     */
    public function buildSearch($field, $value)
    {
        throw new \Exception('Not implemented');
    }

    /**
     * @param $field
     * @param $value
     * @return array
     */
    public function buildSecond($field, $value)
    {
        return $this->buildDateTimeCondition($field, $value, "SECOND");
    }

    /**
     * @param $field
     * @param $value
     * @return array
     */
    public function buildMinute($field, $value)
    {
        return $this->buildDateTimeCondition($field, $value, "MINUTE");
    }

    /**
     * @param $field
     * @param $value
     * @return array
     */
    public function buildHour($field, $value)
    {
        return $this->buildDateTimeCondition($field, $value, "HOUR");
    }

    /**
     * @param $field
     * @param $value
     * @return array
     */
    public function buildWeek_day($field, $value)
    {
        /* @var $this \Mindy\Query\QueryBuilder */
        if (!is_string($value)) {
            $value = (string)$value;
        }

        $paramName = $this->makeParamKey($field);
        return [['and', "EXTRACT(DOW FROM " . $this->db->quoteColumnName($field) . "::timestamp) = :" . $paramName], [':' . $paramName => $value]];
    }

    /**
     * @param $field
     * @param $value
     * @return array
     */
    public function buildDay($field, $value)
    {
        return $this->buildDateTimeCondition($field, $value, "DAY");
    }

    /**
     * @param $field
     * @param $value
     * @return array
     */
    public function buildMonth($field, $value)
    {
        return $this->buildDateTimeCondition($field, $value, "MONTH");
    }

    /**
     * @param $field
     * @param $value
     * @return array
     */
    public function buildYear($field, $value)
    {
        return $this->buildDateTimeCondition($field, $value, "YEAR");
    }

    /**
     * @param $field
     * @param $value
     * @param $extract
     * @return array
     */
    public function buildDateTimeCondition($field, $value, $extract = "YEAR")
    {
        /* @var $this \Mindy\Query\QueryBuilder */
        if (!is_string($value)) {
            $value = (string)$value;
        }

        $paramName = $this->makeParamKey($field);
        return [['and', "EXTRACT(" . $extract . " FROM " . $this->db->quoteColumnName($field) . "::timestamp) = :" . $paramName], [':' . $paramName => $value]];
    }

    public function convertToDateTime($value = null)
    {
        /* @var $this \Mindy\Query\Mysql\QueryBuilder */
        if ($value === null) {
            $value = date($this->dateTimeFormat);
        } elseif (is_numeric($value)) {
            $value = date($this->dateTimeFormat, $value);
        } elseif (is_string($value)) {
            $value = date($this->dateTimeFormat, strtotime($value));
        }
        return $value;
    }

    public function convertToBoolean($value)
    {
        return (bool)$value ? 'TRUE' : 'FALSE';
    }

    public function getRandomOrder()
    {
        return 'RAND()';
    }
}
