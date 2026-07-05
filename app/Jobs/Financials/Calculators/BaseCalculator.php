<?php
/**
 * BaseCalculator
 *
 * @author: tuanha
 * @date: 18-Aug-2022
 */
namespace App\Jobs\Financials\Calculators;

class BaseCalculator
{
    /**
     * @var \App\Models\FinancialStatement
     */
    protected $financialStatement;

    /**
     * Create a new instance
     */
    public function __construct($financialStatement)
    {
        $this->financialStatement = $financialStatement;
    }

    /**
     * Quy đổi một khoản mục "flow" (KQKD/LCTT) về năm: báo cáo năm (quarter=0) giữ nguyên
     * giá trị kỳ; báo cáo quý thì lũy kế 4 quý gần nhất (TTM) để so được với các khoản mục
     * "stock" (CĐKT, không phụ thuộc độ dài kỳ) — tránh hiểu nhầm 1 quý là cả năm.
     *
     * @param  \App\ContentObjects\StatementItem|null  $item
     * @param  int  $year
     * @param  int  $quarter
     * @return float|null
     */
    protected function ttmOrAnnual($item, $year, $quarter)
    {
        if (!$item) {
            return null;
        }
        return $quarter == 0
            ? $item->getValue($year, $quarter)
            : $item->getAccumulatedValueFromPastPeriod($year, $quarter, 3);
    }

    /**
     * Execute all calculation methods
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\BaseCalculator
     */
    public function execute($year = null, $quarter = null)
    {
        $methods = array_filter(get_class_methods($this), function ($method) {
            return str_starts_with($method, 'calculate');
        });
        foreach ($methods as $method) {
            call_user_func([$this, $method], $year, $quarter);
        }
        return $this;
    }
}
