<?php
/**
 * CostStructureCalculator
 *
 * @author: tuanha
 * @date: 23-Aug-2022
 */
namespace App\Jobs\Financials\Calculators;

use App\Jobs\Financials\Calculators\BaseCalculator;

class CostStructureCalculator extends BaseCalculator
{
    public $cOGSToRevenueRatio; //Hệ số giá vốn bán hàng / doanh thu thuần

    public $sellingExpenseToRevenueRatio; //Hệ số giá vốn bán hàng / doanh thu thuần

    public $administrationExpenseToRevenueRatio; //Hệ số chi phí quản lý doanh nghiệp / doanh thu thuần

    public $interestCostToRevenueRatio; //Hệ số chi phí chi phí lãi vay / doanh thu thuần

    public $sellingAndEnperpriseManagementToGrossProfitRatio; //He so chi phi ban hang va quan ly doanh nghiep / Loi nhuan gop

    public $grossProfitUsedForSellingAndEnperpriseManagementToGrossProfit; // Lợi nhuận gộp dùng làm mẫu số - phục vụ banner cảnh báo khi âm

    /**
     * Calculate Cost of goods sale / Revenue Ratio
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\CostStructureCalculator $this
     */
    public function calculateCOGSToRevenueRatio($year = null, $quarter = null)
    {
        $this->cOGSToRevenueRatio = null;
        if (!empty($this->financialStatement->income_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $cogs = $this->financialStatement->income_statement->getItem('4')->getValue($selectedYear, $selectedQuarter);
            $revenue = $this->financialStatement->income_statement->getItem('3')->getValue($selectedYear, $selectedQuarter);
            if ($revenue != 0) {
                $this->cOGSToRevenueRatio = round(100 * $cogs / $revenue, 2);
            }
        }
        return $this;
    }

    /**
     * Calculate Selling Expense / Revenue Ratio
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\CostStructureCalculator $this
     */
    public function calculateSellingExpenseToRevenueRatio($year = null, $quarter = null)
    {
        $this->sellingExpenseToRevenueRatio = null;
        if (!empty($this->financialStatement->income_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $selling_expenses = $this->financialStatement->income_statement->getItem('9')->getValue($selectedYear, $selectedQuarter);
            $revenue = $this->financialStatement->income_statement->getItem('3')->getValue($selectedYear, $selectedQuarter);
            if ($revenue != 0) {
                $this->sellingExpenseToRevenueRatio = round(100 * $selling_expenses / $revenue, 2);
            }
        }
        return $this;
    }

    /**
     * Calculate Administration Expense / Revenue Ratio
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\CostStructureCalculator $this
     */
    public function calculateAdministrationExpenseToRevenueRatio($year = null, $quarter = null)
    {
        $this->administrationExpenseToRevenueRatio = null;
        if (!empty($this->financialStatement->income_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $administration_expenses = $this->financialStatement->income_statement->getItem('10')->getValue($selectedYear, $selectedQuarter);
            $revenue = $this->financialStatement->income_statement->getItem('3')->getValue($selectedYear, $selectedQuarter);
            if ($revenue != 0) {
                $this->administrationExpenseToRevenueRatio = round(100 * $administration_expenses / $revenue, 2);
            }
        }
        return $this;
    }

    /**
     * Calculate Interest Cost / Revenue Ratio
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\CostStructureCalculator $this
     */
    public function calculateInterestCostToRevenueRatio($year = null, $quarter = null)
    {
        $this->interestCostToRevenueRatio = null;
        if (!empty($this->financialStatement->income_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $interest_cost = $this->financialStatement->income_statement->getItem('701')->getValue($selectedYear, $selectedQuarter);
            $revenue = $this->financialStatement->income_statement->getItem('3')->getValue($selectedYear, $selectedQuarter);
            if ($revenue != 0) {
                $this->interestCostToRevenueRatio = round(100 * $interest_cost / $revenue, 2);
            }
        }
        return $this;
    }

    /**
     * Calculate Selling & Enterprise Management Expenses To Gross Profit Ratio
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\CostStructureCalculator $this
     */
    public function calculateSellingAndEnperpriseManagementToGrossProfitRatio($year = null, $quarter = null)
    {
        $this->sellingAndEnperpriseManagementToGrossProfitRatio = null;
        $this->grossProfitUsedForSellingAndEnperpriseManagementToGrossProfit = null;
        if (!empty($this->financialStatement->income_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $selling_expense = $this->financialStatement->income_statement->getItem('9')->getValue($selectedYear, $selectedQuarter);
            $enterprise_management_expense = $this->financialStatement->income_statement->getItem('10')->getValue($selectedYear, $selectedQuarter);
            $grossProfit = $this->financialStatement->income_statement->getItem('5')->getValue($selectedYear, $selectedQuarter);
            $this->grossProfitUsedForSellingAndEnperpriseManagementToGrossProfit = $grossProfit;
            if ($grossProfit != 0) {
                $this->sellingAndEnperpriseManagementToGrossProfitRatio = round(100 * ($selling_expense + $enterprise_management_expense) / $grossProfit, 2);
            }
        }
        return $this;
    }
}
