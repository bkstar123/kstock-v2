<?php
/**
 * StatementItem class
 *
 * Supposed to be used by StatementRepository
 *
 * @author: tuanha
 * @date: 03-Aug-2022
 */
namespace App\ContentObjects;

class StatementItem
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $parent_id;

    /**
     * @var bool
     */
    public $expanded;

    /**
     * @var integer
     */
    public $level;

    /**
     * @var string
     */
    public $field;

    /**
     * @var array
     */
    public $values;

    /**
     * Initialize StatementItem instance
     *
     * @param string $id,
     * @param string $name
     * @param string $parentID
     * @param bool $expanded
     * @param integer $level
     * @param string $field
     * @param array $values
     */
    public function __construct($id, $name, $parent_id, $expanded, $level, $field, $values)
    {
        $this->id = $id;
        $this->name = $name;
        $this->parent_id = $parent_id;
        $this->expanded = $expanded;
        $this->level= $level;
        $this->field = $field;
        $this->values = $values;
    }

    /**
     * Get all values of a statement content item
     *
     * @return array
     */
    public function getValues()
    {
        $count = count($this->values);
        return array_merge([], \Arr::where(array_map(function ($value) {
            return [
                $value['period'],
                $value['value']
            ];
        }, $this->values), function ($value, $key) use ($count) {
            return $key >= $count - (int) config('settings.limits', 5);
        }));
    }

    /**
     * Get the value of a statement content item by year and quarter
     *
     * @param integer $year
     * @param integer $quarter
     * @return float
     */
    public function getValue($year, $quarter)
    {
        $res = array_first(\Arr::where(
            $this->values,
            function ($value) use ($year, $quarter) {
                return $value['year'] == $year && $value['quarter'] == $quarter;
            }
        ));
        return (float) ($res['value'] ?? '');
    }

    /**
     * Get the average value of a statement content item between the given period and a past period
     *
     * @param integer $year
     * @param integer $quarter
     * @return float
     */
    public function getAverageValue($year, $quarter, $step = 1)
    {
        $currentValue = $this->getValue($year, $quarter);
        if ($step <= 0) {
            return $currentValue;
        }
        for ($i = 1; $i <= $step; $i++) {
            $pastPeriod = getPreviousPeriod($year, $quarter);
            $year = $pastPeriod['year'];
            $quarter = $pastPeriod['quarter'];
        }
        if ($this->checkDataForPeriodExisted($pastPeriod['year'], $pastPeriod['quarter'])) {
            $pastValue = $this->getValue($pastPeriod['year'], $pastPeriod['quarter']);
            return ($currentValue + $pastValue) / 2;
        } else {
            return $currentValue;
        }
    }

    /**
     * Get the differential value of a statement content item between the given period and a past period
     *
     * @param integer $year
     * @param integer $quarter
     * @param integer $step
     * @return float
     */
    public function getDifferentialValueFromPastPeriod($year, $quarter, $step = 1)
    {
        $currentValue = $this->getValue($year, $quarter);
        if ($step <= 0) {
            return $currentValue;
        }
        for ($i = 1; $i <= $step; $i++) {
            $pastPeriod = getPreviousPeriod($year, $quarter);
            $year = $pastPeriod['year'];
            $quarter = $pastPeriod['quarter'];
        }
        if ($this->checkDataForPeriodExisted($pastPeriod['year'], $pastPeriod['quarter'])) {
            $pastValue = $this->getValue($pastPeriod['year'], $pastPeriod['quarter']);
            return $currentValue - $pastValue;
        } else {
            return $currentValue;
        }
    }

    /**
     * Get the accumulated value of a statement content item from a past period to the given period
     *
     * @param integer $year
     * @param integer $quarter
     * @param integer $step
     * @return float
     */
    public function getAccumulatedValueFromPastPeriod($year, $quarter, $step = 1)
    {
        $accumulated_value = $this->getValue($year, $quarter);
        if ($step < 0) {
            return $currentValue;
        }
        for ($i = 1; $i <= $step; $i++) {
            $pastPeriod = getPreviousPeriod($year, $quarter);
            $year = $pastPeriod['year'];
            $quarter = $pastPeriod['quarter'];
            if ($this->checkDataForPeriodExisted($year, $quarter)) {
                $accumulated_value += $this->getValue($year, $quarter);
            }
        }
        return $accumulated_value;
    }

    /**
     * Check whether the data for the concern period existed or not
     *
     * @param integer $year
     * @param integer $quarter
     * @return boolean
     */
    private function checkDataForPeriodExisted($year, $quarter)
    {
        $concernPeriodData = \Arr::where(
            $this->values,
            function ($value) use ($year, $quarter) {
                return $value['year'] == $year && $value['quarter'] == $quarter;
            }
        );
        return !empty($concernPeriodData);
    }
}
