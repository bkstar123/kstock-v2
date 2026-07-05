<?php
/**
 * GrowthCalculator
 *
 * @author: tuanha
 * @date: 24-Aug-2022
 */
namespace App\Jobs\Financials\Calculators;

use App\Jobs\Financials\Calculators\BaseCalculator;

class GrowthCalculator extends BaseCalculator
{
    public $revenueGrowthQoQ; //Tăng trưởng doanh thu thuần so với quý trước trong cùng năm tài chính

    public $revenueGrowthYoY; //Tăng trưởng doanh thu thuần so với cùng kỳ năm tài chính trước

    public $grossProfitGrowthQoQ; //Tăng trưởng lợi nhuận gộp so với quý trước trong cùng năm tài chính

    public $grossProfitGrowthYoY; //Tăng trưởng lợi nhuận gộp so với cùng kỳ năm tài chính trước

    public $eBTGrowthQoQ; //Tăng trưởng lợi nhuận trước thuế so với quý trước trong cùng năm tài chính

    public $eBTGrowthYoY; //Tăng trưởng lợi nhuận trước thuế so với cùng kỳ năm tài chính trước

    public $netProfitOfParentShareHolderGrowthQoQ; //Tăng trưởng lợi nhuận sau thuế của cổ đông công ty mẹ so với quý trước trong cùng năm tài chính

    public $netProfitOfParentShareHolderGrowthYoY; //Tăng trưởng lợi nhuận sau thuế của cổ đông công ty mẹ so với cùng kỳ năm tài chính trước

    public $totalAssetGrowthQoQ; //Tăng trưởng tổng tài sản so với quý trước trong cùng năm tài chính

    public $totalAssetGrowthYoY; //Tăng trưởng tổng tài sản so với cùng kỳ năm tài chính trước

    public $longTermLiabilityGrowthQoQ; //Tăng trưởng nợ dài hạn so với quý trước trong cùng năm tài chính

    public $longTermLiabilityGrowthYoY; //Tăng trưởng nợ dài hạn so với cùng kỳ năm tài chính trước

    public $liabilityGrowthQoQ; //Tăng trưởng nợ phải trả so với quý trước trong cùng năm tài chính

    public $liabilityGrowthYoY; //Tăng trưởng nợ phải trả so với cùng kỳ năm tài chính trước

    public $equityGrowthQoQ; //Tăng trưởng VCSH so với quý trước trong cùng năm tài chính

    public $equityGrowthYoY; //Tăng trưởng VCSH so với cùng kỳ năm tài chính trước

    public $charterCapitalGrowthQoQ; //Tăng trưởng vốn điều lệ so với quý trước trong cùng năm tài chính

    public $charterCapitalGrowthYoY; //Tăng trưởng vốn điều lệ so với cùng kỳ năm tài chính trước

    public $inventoryGrowthQoQ; //Tăng trưởng hang ton kho so với quý trước trong cùng năm tài chính

    public $inventoryGrowthYoY; //Tăng trưởng hang ton kho so với cùng kỳ năm tài chính trước

    public $fcfGrowthQoQ; //Tang truong dong tien tu do so voi quý trước trong cùng năm tài chính

    public $fcfGrowthYoY; //Tang truong dong tien tu do so voi cùng kỳ năm tài chính trước

    public $cogsGrowthQoQ; //Tang truong gia von ban hang so voi quý trước trong cùng năm tài chính

    public $cogsGrowthYoY; //Tang truong gia von ban hang so voi cùng kỳ năm tài chính trước

    public $operationExpenseGrowthQoQ; //Tang truong chi phi hoat dong (chi phi ban hang & QLDN) so voi quý trước trong cùng năm tài chính

    public $operationExpenseGrowthYoY; //Tang truong chi phi hoat dong (chi phi ban hang & QLDN) so voi cùng kỳ năm tài chính trước

    public $interestExpenseGrowthQoQ; //Tang truong chi phi lai vay so voi quý trước trong cùng năm tài chính

    public $interestExpenseGrowthYoY; //Tang truong chi phi lai vay so voi cùng kỳ năm tài chính trước

    public $debtGrowthQoQ; //Tang truong no vay so voi quý trước trong cùng năm tài chính

    public $debtGrowthYoY; //Tang truong no vay so voi cùng kỳ năm tài chính trước

    /**
     * Calculate Revenue Growth
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\GrowthCalculator $this
     */
    public function calculateRevenueGrowth($year = null, $quarter = null)
    {
        $this->revenueGrowthYoY = null;
        $this->revenueGrowthQoQ = null;
        if (!empty($this->financialStatement->income_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $selectedPeriodRevenue = $this->financialStatement->income_statement->getItem('3')->getValue($selectedYear, $selectedQuarter);
            $revenueYoY = $this->financialStatement->income_statement->getItem('3')->getValue($selectedYear-1, $selectedQuarter);
            if ($revenueYoY != 0) {
                $this->revenueGrowthYoY = round(100 * ($selectedPeriodRevenue - $revenueYoY) / abs($revenueYoY), 2);
            }
            $previous = getPreviousPeriod($selectedYear, $selectedQuarter);
            $revenueQoQ = $this->financialStatement->income_statement->getItem('3')->getValue($previous['year'], $previous['quarter']);
            if ($revenueQoQ != 0) {
                $this->revenueGrowthQoQ = round(100 * ($selectedPeriodRevenue - $revenueQoQ) / abs($revenueQoQ), 2);
            }
        }
        return $this;
    }

    /**
     * Calculate Gross Profit Growth
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\GrowthCalculator $this
     */
    public function calculateGrossProfitGrowth($year = null, $quarter = null)
    {
        $this->grossProfitGrowthYoY = null;
        $this->grossProfitGrowthQoQ = null;
        if (!empty($this->financialStatement->income_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $selectedPeriodGrossProfit = $this->financialStatement->income_statement->getItem('5')->getValue($selectedYear, $selectedQuarter);
            $grossProfitYoY = $this->financialStatement->income_statement->getItem('5')->getValue($selectedYear-1, $selectedQuarter);
            if ($grossProfitYoY != 0) {
                $this->grossProfitGrowthYoY = round(100 * ($selectedPeriodGrossProfit - $grossProfitYoY) / abs($grossProfitYoY), 2);
            }
            $previous = getPreviousPeriod($selectedYear, $selectedQuarter);
            $grossProfitQoQ = $this->financialStatement->income_statement->getItem('5')->getValue($previous['year'], $previous['quarter']);
            if ($grossProfitQoQ != 0) {
                $this->grossProfitGrowthQoQ = round(100 * ($selectedPeriodGrossProfit - $grossProfitQoQ) / abs($grossProfitQoQ), 2);
            }
        }
        return $this;
    }

    /**
     * Calculate Earning Before Tax (EBT) Growth
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\GrowthCalculator $this
     */
    public function calculateEBTGrowth($year = null, $quarter = null)
    {
        $this->eBTGrowthYoY = null;
        $this->eBTGrowthQoQ = null;
        if (!empty($this->financialStatement->income_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $selectedPeriodEBT = $this->financialStatement->income_statement->getItem('15')->getValue($selectedYear, $selectedQuarter);
            $eBTYoY = $this->financialStatement->income_statement->getItem('15')->getValue($selectedYear-1, $selectedQuarter);
            if ($eBTYoY != 0) {
                $this->eBTGrowthYoY = round(100 * ($selectedPeriodEBT - $eBTYoY) / abs($eBTYoY), 2);
            }
            $previous = getPreviousPeriod($selectedYear, $selectedQuarter);
            $eBTQoQ = $this->financialStatement->income_statement->getItem('15')->getValue($previous['year'], $previous['quarter']);
            if ($eBTQoQ != 0) {
                $this->eBTGrowthQoQ = round(100 * ($selectedPeriodEBT - $eBTQoQ) / abs($eBTQoQ), 2);
            }
        }
        return $this;
    }

    /**
     * Calculate Net Profit Of Parent Shareholders Growth
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\GrowthCalculator $this
     */
    public function calculateNetProfitOfParentShareHolderGrowth($year = null, $quarter = null)
    {
        $this->netProfitOfParentShareHolderGrowthYoY = null;
        $this->netProfitOfParentShareHolderGrowthQoQ = null;
        if (!empty($this->financialStatement->income_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $selectedPeriodNetProfitOfParentShareHolder = $this->financialStatement->income_statement->getItem('21')->getValue($selectedYear, $selectedQuarter);
            $netProfitOfParentShareHolderYoY = $this->financialStatement->income_statement->getItem('21')->getValue($selectedYear-1, $selectedQuarter);
            if ($netProfitOfParentShareHolderYoY != 0) {
                $this->netProfitOfParentShareHolderGrowthYoY = round(100 * ($selectedPeriodNetProfitOfParentShareHolder - $netProfitOfParentShareHolderYoY) / abs($netProfitOfParentShareHolderYoY), 2);
            }
            $previous = getPreviousPeriod($selectedYear, $selectedQuarter);
            $netProfitOfParentShareHolderQoQ = $this->financialStatement->income_statement->getItem('21')->getValue($previous['year'], $previous['quarter']);
            if ($netProfitOfParentShareHolderQoQ != 0) {
                $this->netProfitOfParentShareHolderGrowthQoQ = round(100 * ($selectedPeriodNetProfitOfParentShareHolder - $netProfitOfParentShareHolderQoQ) / abs($netProfitOfParentShareHolderQoQ), 2);
            }
        }
        return $this;
    }

    /**
     * Calculate Total Asset Growth
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\GrowthCalculator $this
     */
    public function calculateTotalAssetGrowth($year = null, $quarter = null)
    {
        $this->totalAssetGrowthYoY = null;
        $this->totalAssetGrowthQoQ = null;
        if (!empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $selectedPeriodTotalAssets = $this->financialStatement->balance_statement->getItem('2')->getValue($selectedYear, $selectedQuarter);
            $totalAssetsYoY = $this->financialStatement->balance_statement->getItem('2')->getValue($selectedYear-1, $selectedQuarter);
            if ($totalAssetsYoY != 0) {
                $this->totalAssetGrowthYoY = round(100 * ($selectedPeriodTotalAssets - $totalAssetsYoY) / abs($totalAssetsYoY), 2);
            }
            $previous = getPreviousPeriod($selectedYear, $selectedQuarter);
            $totalAssetsQoQ = $this->financialStatement->balance_statement->getItem('2')->getValue($previous['year'], $previous['quarter']);
            if ($totalAssetsQoQ != 0) {
                $this->totalAssetGrowthQoQ = round(100 * ($selectedPeriodTotalAssets - $totalAssetsQoQ) / abs($totalAssetsQoQ), 2);
            }
        }
        return $this;
    }

    /**
     * Calculate Long Term Liability Growth
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\GrowthCalculator $this
     */
    public function calculateLongTermLiabilityGrowth($year = null, $quarter = null)
    {
        $this->longTermLiabilityGrowthYoY = null;
        $this->longTermLiabilityGrowthQoQ = null;
        if (!empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $selectedPeriodLongTermLiability = $this->financialStatement->balance_statement->getItem('30102')->getValue($selectedYear, $selectedQuarter);
            $longTermLiabilityYoY = $this->financialStatement->balance_statement->getItem('30102')->getValue($selectedYear-1, $selectedQuarter);
            if ($longTermLiabilityYoY != 0) {
                $this->longTermLiabilityGrowthYoY = round(100 * ($selectedPeriodLongTermLiability - $longTermLiabilityYoY) / abs($longTermLiabilityYoY), 2);
            }
            $previous = getPreviousPeriod($selectedYear, $selectedQuarter);
            $longTermLiabilityQoQ = $this->financialStatement->balance_statement->getItem('30102')->getValue($previous['year'], $previous['quarter']);
            if ($longTermLiabilityQoQ != 0) {
                $this->longTermLiabilityGrowthQoQ = round(100 * ($selectedPeriodLongTermLiability - $longTermLiabilityQoQ) / abs($longTermLiabilityQoQ), 2);
            }
        }
        return $this;
    }

    /**
     * Calculate Liability Growth
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\GrowthCalculator $this
     */
    public function calculateLiabilityGrowth($year = null, $quarter = null)
    {
        $this->liabilityGrowthYoY = null;
        $this->liabilityGrowthQoQ = null;
        if (!empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $selectedPeriodLiability = $this->financialStatement->balance_statement->getItem('301')->getValue($selectedYear, $selectedQuarter);
            $liabilityYoY = $this->financialStatement->balance_statement->getItem('301')->getValue($selectedYear-1, $selectedQuarter);
            if ($liabilityYoY != 0) {
                $this->liabilityGrowthYoY = round(100 * ($selectedPeriodLiability - $liabilityYoY) / abs($liabilityYoY), 2);
            }
            $previous = getPreviousPeriod($selectedYear, $selectedQuarter);
            $liabilityQoQ = $this->financialStatement->balance_statement->getItem('301')->getValue($previous['year'], $previous['quarter']);
            if ($liabilityQoQ != 0) {
                $this->liabilityGrowthQoQ = round(100 * ($selectedPeriodLiability - $liabilityQoQ) / abs($liabilityQoQ), 2);
            }
        }
        return $this;
    }

    /**
     * Calculate Equity Growth
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\GrowthCalculator $this
     */
    public function calculateEquityGrowth($year = null, $quarter = null)
    {
        $this->equityGrowthYoY = null;
        $this->equityGrowthQoQ = null;
        if (!empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $selectedPeriodEquity = $this->financialStatement->balance_statement->getItem('302')->getValue($selectedYear, $selectedQuarter);
            $equityYoY = $this->financialStatement->balance_statement->getItem('302')->getValue($selectedYear-1, $selectedQuarter);
            if ($equityYoY != 0) {
                $this->equityGrowthYoY = round(100 * ($selectedPeriodEquity - $equityYoY) / abs($equityYoY), 2);
            }
            $previous = getPreviousPeriod($selectedYear, $selectedQuarter);
            $equityQoQ = $this->financialStatement->balance_statement->getItem('302')->getValue($previous['year'], $previous['quarter']);
            if ($equityQoQ != 0) {
                $this->equityGrowthQoQ = round(100 * ($selectedPeriodEquity - $equityQoQ) / abs($equityQoQ), 2);
            }
        }
        return $this;
    }

    /**
     * Calculate Charter Capital Growth
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\GrowthCalculator $this
     */
    public function calculateCharterCapitalGrowth($year = null, $quarter = null)
    {
        $this->charterCapitalGrowthYoY = null;
        $this->charterCapitalGrowthQoQ = null;
        if (!empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $selectedPeriodCharterCapital = $this->financialStatement->balance_statement->getItem('3020101')->getValue($selectedYear, $selectedQuarter);
            $charterCapitalYoY = $this->financialStatement->balance_statement->getItem('3020101')->getValue($selectedYear-1, $selectedQuarter);
            if ($charterCapitalYoY != 0) {
                $this->charterCapitalGrowthYoY = round(100 * ($selectedPeriodCharterCapital - $charterCapitalYoY) / abs($charterCapitalYoY), 2);
            }
            $previous = getPreviousPeriod($selectedYear, $selectedQuarter);
            $charterCapitalQoQ = $this->financialStatement->balance_statement->getItem('3020101')->getValue($previous['year'], $previous['quarter']);
            if ($charterCapitalQoQ != 0) {
                $this->charterCapitalGrowthQoQ = round(100 * ($selectedPeriodCharterCapital - $charterCapitalQoQ) / abs($charterCapitalQoQ), 2);
            }
        }
        return $this;
    }

    /**
     * Calculate Inventory Growth
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\GrowthCalculator $this
     */
    public function calculateInventoryGrowth($year = null, $quarter = null)
    {
        $this->inventoryGrowthYoY = null;
        $this->inventoryGrowthQoQ = null;
        if (!empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $selectedPeriodInventory = $this->financialStatement->balance_statement->getItem('10104')->getValue($selectedYear, $selectedQuarter);
            $inventoryYoY = $this->financialStatement->balance_statement->getItem('10104')->getValue($selectedYear-1, $selectedQuarter);
            if ($inventoryYoY != 0) {
                $this->inventoryGrowthYoY = round(100 * ($selectedPeriodInventory - $inventoryYoY) / abs($inventoryYoY), 2);
            }
            $previous = getPreviousPeriod($selectedYear, $selectedQuarter);
            $inventoryQoQ = $this->financialStatement->balance_statement->getItem('10104')->getValue($previous['year'], $previous['quarter']);
            if ($inventoryQoQ != 0) {
                $this->inventoryGrowthQoQ = round(100 * ($selectedPeriodInventory - $inventoryQoQ) / abs($inventoryQoQ), 2);
            }
        }
        return $this;
    }

    /**
     * Calculate FCF Growth
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\GrowthCalculator $this
     */
    public function calculateFcfGrowth($year = null, $quarter = null)
    {
        $this->fcfGrowthYoY = null;
        $this->fcfGrowthQoQ = null;
        if (!empty($this->financialStatement->cash_flow_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $selectedPeriodFCF = $this->financialStatement->cash_flow_statement->getItem('104')->getValue($selectedYear, $selectedQuarter) + $this->financialStatement->cash_flow_statement->getItem('201')->getValue($selectedYear, $selectedQuarter) + $this->financialStatement->cash_flow_statement->getItem('202')->getValue($selectedYear, $selectedQuarter);
            $fcfYoY = $this->financialStatement->cash_flow_statement->getItem('104')->getValue($selectedYear-1, $selectedQuarter) + $this->financialStatement->cash_flow_statement->getItem('201')->getValue($selectedYear-1, $selectedQuarter) + $this->financialStatement->cash_flow_statement->getItem('202')->getValue($selectedYear-1, $selectedQuarter);
            if ($fcfYoY != 0) {
                $this->fcfGrowthYoY = round(100 * ($selectedPeriodFCF - $fcfYoY) / abs($fcfYoY), 2);
            }
            $previous = getPreviousPeriod($selectedYear, $selectedQuarter);
            $fcfQoQ = $this->financialStatement->cash_flow_statement->getItem('104')->getValue($previous['year'], $previous['quarter']) + $this->financialStatement->cash_flow_statement->getItem('201')->getValue($previous['year'], $previous['quarter']) + $this->financialStatement->cash_flow_statement->getItem('202')->getValue($previous['year'], $previous['quarter']);
            if ($fcfQoQ != 0) {
                $this->fcfGrowthQoQ = round(100 * ($selectedPeriodFCF - $fcfQoQ) / abs($fcfQoQ), 2);
            }
        }
        return $this;
    }

    /**
     * Calculate COGS Growth
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\GrowthCalculator $this
     */
    public function calculateCogsGrowth($year = null, $quarter = null)
    {
        $this->cogsGrowthYoY = null;
        $this->cogsGrowthQoQ = null;
        if (!empty($this->financialStatement->income_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $selectedPeriodCogs = $this->financialStatement->income_statement->getItem('4')->getValue($selectedYear, $selectedQuarter);
            $cogsYoY = $this->financialStatement->income_statement->getItem('4')->getValue($selectedYear-1, $selectedQuarter);
            if ($cogsYoY != 0) {
                $this->cogsGrowthYoY = round(100 * ($selectedPeriodCogs - $cogsYoY) / abs($cogsYoY), 2);
            }
            $previous = getPreviousPeriod($selectedYear, $selectedQuarter);
            $cogsQoQ = $this->financialStatement->income_statement->getItem('4')->getValue($previous['year'], $previous['quarter']);
            if ($cogsQoQ != 0) {
                $this->cogsGrowthQoQ = round(100 * ($selectedPeriodCogs - $cogsQoQ) / abs($cogsQoQ), 2);
            }
        }
        return $this;
    }

    /**
     * Calculate Operation Expense Growth
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\GrowthCalculator $this
     */
    public function calculateOperationExpenseGrowth($year = null, $quarter = null)
    {
        $this->operationExpenseGrowthYoY = null;
        $this->operationExpenseGrowthQoQ = null;
        if (!empty($this->financialStatement->income_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $selectedPeriodOperationExpense = $this->financialStatement->income_statement->getItem('9')->getValue($selectedYear, $selectedQuarter) + $this->financialStatement->income_statement->getItem('10')->getValue($selectedYear, $selectedQuarter);
            $operationExpenseYoY = $this->financialStatement->income_statement->getItem('9')->getValue($selectedYear-1, $selectedQuarter) + $this->financialStatement->income_statement->getItem('10')->getValue($selectedYear-1, $selectedQuarter);
            if ($operationExpenseYoY != 0) {
                $this->operationExpenseGrowthYoY = round(100 * ($selectedPeriodOperationExpense - $operationExpenseYoY) / abs($operationExpenseYoY), 2);
            }
            $previous = getPreviousPeriod($selectedYear, $selectedQuarter);
            $operationExpenseQoQ = $this->financialStatement->income_statement->getItem('9')->getValue($previous['year'], $previous['quarter']) + $this->financialStatement->income_statement->getItem('10')->getValue($previous['year'], $previous['quarter']);
            if ($operationExpenseQoQ != 0) {
                $this->operationExpenseGrowthQoQ = round(100 * ($selectedPeriodOperationExpense - $operationExpenseQoQ) / abs($operationExpenseQoQ), 2);
            }
        }
        return $this;
    }

    /**
     * Calculate Interest Expense Growth
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\GrowthCalculator $this
     */
    public function calculateInterestExpenseGrowth($year = null, $quarter = null)
    {
        $this->interestExpenseGrowthYoY = null;
        $this->interestExpenseGrowthQoQ = null;
        if (!empty($this->financialStatement->income_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $selectedPeriodInterestExpense = $this->financialStatement->income_statement->getItem('701')->getValue($selectedYear, $selectedQuarter);
            $interestExpenseYoY = $this->financialStatement->income_statement->getItem('701')->getValue($selectedYear-1, $selectedQuarter);
            if ($interestExpenseYoY != 0) {
                $this->interestExpenseGrowthYoY = round(100 * ($selectedPeriodInterestExpense - $interestExpenseYoY) / abs($interestExpenseYoY), 2);
            }
            $previous = getPreviousPeriod($selectedYear, $selectedQuarter);
            $interestExpenseQoQ = $this->financialStatement->income_statement->getItem('701')->getValue($previous['year'], $previous['quarter']);
            if ($interestExpenseQoQ != 0) {
                $this->interestExpenseGrowthQoQ = round(100 * ($selectedPeriodInterestExpense - $interestExpenseQoQ) / abs($interestExpenseQoQ), 2);
            }
        }
        return $this;
    }

    /**
     * Calculate Debt Growth
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\GrowthCalculator $this
     */
    public function calculateDebtGrowth($year = null, $quarter = null)
    {
        $this->debtGrowthYoY = null;
        $this->debtGrowthQoQ = null;
        if (!empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $selectedPeriodDebt = $this->financialStatement->balance_statement->getItem('3010101')->getValue($selectedYear, $selectedQuarter) + $this->financialStatement->balance_statement->getItem('3010206')->getValue($selectedYear, $selectedQuarter);
            $debtYoY = $this->financialStatement->balance_statement->getItem('3010101')->getValue($selectedYear-1, $selectedQuarter) + $this->financialStatement->balance_statement->getItem('3010206')->getValue($selectedYear-1, $selectedQuarter);
            if ($debtYoY != 0) {
                $this->debtGrowthYoY = round(100 * ($selectedPeriodDebt - $debtYoY) / abs($debtYoY), 2);
            }
            $previous = getPreviousPeriod($selectedYear, $selectedQuarter);
            $debtQoQ = $this->financialStatement->balance_statement->getItem('3010101')->getValue($previous['year'], $previous['quarter']) + $this->financialStatement->balance_statement->getItem('3010206')->getValue($previous['year'], $previous['quarter']);
            if ($debtQoQ != 0) {
                $this->debtGrowthQoQ = round(100 * ($selectedPeriodDebt - $debtQoQ) / abs($debtQoQ), 2);
            }
        }
        return $this;
    }
}
