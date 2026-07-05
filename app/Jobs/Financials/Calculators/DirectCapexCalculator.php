<?php
/**
 * DirectCapexCalculator
 *
 * Bản CFO/CAPEX cho LCTT theo phương pháp trực tiếp — xem giải thích chi tiết ở
 * DirectCashFlowCalculator (item id không dùng chung với phương pháp gián tiếp nên tra
 * theo tên dòng trong đúng mục "I. HĐKD"/"II. HĐ đầu tư" thay vì literal id).
 *
 * @author: kstock
 */
namespace App\Jobs\Financials\Calculators;

class DirectCapexCalculator extends CapexCalculator
{
    private function childrenOf($statement, $parentId)
    {
        return $statement->getItems()->filter(function ($it) use ($parentId) {
            return (string) $it->parent_id === (string) $parentId;
        })->values();
    }

    private function findChildByName($statement, $parentId, $needle)
    {
        return $this->childrenOf($statement, $parentId)->first(function ($it) use ($needle) {
            return mb_stripos($it->name, $needle) !== false;
        });
    }

    private function cfoValue($statement, $year, $quarter)
    {
        $item = $this->findChildByName($statement, '1', 'Lưu chuyển tiền thuần');
        return $item ? $item->getValue($year, $quarter) : null;
    }

    private function capexValue($statement, $year, $quarter)
    {
        $out = $this->findChildByName($statement, '2', 'mua sắm, xây dựng TSCĐ');
        $in = $this->findChildByName($statement, '2', 'thanh lý, nhượng bán TSCĐ');
        return ($out ? $out->getValue($year, $quarter) : 0) + ($in ? $in->getValue($year, $quarter) : 0);
    }

    public function calculateCfoToCapexRatio($year = null, $quarter = null)
    {
        $this->cfoToCapexRatio = null;
        if (!empty($this->financialStatement->cash_flow_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $statement = $this->financialStatement->cash_flow_statement;
            $cfo = $this->cfoValue($statement, $selectedYear, $selectedQuarter);
            $capex = $this->capexValue($statement, $selectedYear, $selectedQuarter);
            if ($cfo !== null && $capex < 0) {
                $this->cfoToCapexRatio = round($cfo / abs($capex), 4);
            }
        }
        return $this;
    }

    public function calculateCapexToNetProfitRatio($year = null, $quarter = null)
    {
        $this->capexToNetProfitRatio = null;
        $this->netProfitUsedForCapexToNetProfit = null;
        if (!empty($this->financialStatement->cash_flow_statement) && !empty($this->financialStatement->income_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $net_profit = $this->financialStatement->income_statement->getItem('19')->getValue($selectedYear, $selectedQuarter);
            $capex = $this->capexValue($this->financialStatement->cash_flow_statement, $selectedYear, $selectedQuarter);
            $this->netProfitUsedForCapexToNetProfit = $net_profit;
            if ($capex < 0 && $net_profit != 0) {
                $this->capexToNetProfitRatio = round(100 * abs($capex) / $net_profit, 2);
            }
        }
        return $this;
    }
}
