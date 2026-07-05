<?php
/**
 * CapexCalculator
 *
 * @author: tuanha
 * @date: 18-Aug-2022
 */
namespace App\Jobs\Financials\Calculators;

use App\Jobs\Financials\Calculators\BaseCalculator;

class CapexCalculator extends BaseCalculator
{
    public $cfoToCapexRatio; //CFO/CAPEX

    public $capexToNetProfitRatio; //CAPEX/Loi nhuan rong

    public $netProfitUsedForCapexToNetProfit; // Lợi nhuận ròng dùng làm mẫu số - phục vụ banner cảnh báo khi âm/bằng 0

    /**
     * Calculate CFO/CAPEX Ratio
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\CapexCalculator $this
     */
    public function calculateCfoToCapexRatio($year = null, $quarter = null)
    {
        $this->cfoToCapexRatio = null;
        if (!empty($this->financialStatement->cash_flow_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $cfo = $this->financialStatement->cash_flow_statement->getItem('104')->getValue($selectedYear, $selectedQuarter);
            $capex = $this->financialStatement->cash_flow_statement->getItem('201')->getValue($selectedYear, $selectedQuarter) + $this->financialStatement->cash_flow_statement->getItem('202')->getValue($selectedYear, $selectedQuarter);
            if ($capex < 0) {
                $this->cfoToCapexRatio = round($cfo / abs($capex), 4);
            }
        }
        return $this;
    }

    /**
     * Calculate Capex/Net Profit Ratio
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\CapexCalculator $this
     */
    public function calculateCapexToNetProfitRatio($year = null, $quarter = null)
    {
        $this->capexToNetProfitRatio = null;
        $this->netProfitUsedForCapexToNetProfit = null;
        if (!empty($this->financialStatement->cash_flow_statement) && !empty($this->financialStatement->income_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $net_profit = $this->financialStatement->income_statement->getItem('19')->getValue($selectedYear, $selectedQuarter);
            $capex = $this->financialStatement->cash_flow_statement->getItem('201')->getValue($selectedYear, $selectedQuarter) + $this->financialStatement->cash_flow_statement->getItem('202')->getValue($selectedYear, $selectedQuarter);
            $this->netProfitUsedForCapexToNetProfit = $net_profit;
            if ($capex < 0 && $net_profit != 0) {
                $this->capexToNetProfitRatio = round(100 * abs($capex) / $net_profit, 2);
            }
        }
        return $this;
    }
}
