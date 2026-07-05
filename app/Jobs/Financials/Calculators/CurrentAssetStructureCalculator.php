<?php
/**
 * CurrentAssetStructureCalculator
 *
 * @author: tuanha
 * @date: 23-Aug-2022
 */
namespace App\Jobs\Financials\Calculators;

use App\Jobs\Financials\Calculators\BaseCalculator;

class CurrentAssetStructureCalculator extends BaseCalculator
{
    public $currentAssetToTotalAssetRatio; //Tài sản ngắn hạn/Tổng tài sản

    public $cashToCurrentAssetRatio; //Tiền/Tài sản ngắn hạn

    public $currentFinancialInvestingToCurrentAssetRatio; //Đầu tư tài chính ngắn hạn/Tài sản ngắn hạn

    public $currentReceivableAccountToCurrentAssetRatio; //Phải thu ngắn hạn/Tài sản ngắn hạn

    public $inventoryToCurrentAssetRatio; //Hàng tồn kho/Tài sản ngắn hạn

    public $otherCurrentAssetToCurrentAssetRatio;  //Tài sản ngắn hạn khác/Tài sản ngắn hạn

    /**
     * Calculate Current Assets / Total Assets Ratio
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\CurrentAssetStructureCalculator $this
     */
    public function calculateCurrentAssetToTotalAssetRatio($year = null, $quarter = null)
    {
        $this->currentAssetToTotalAssetRatio = null;
        if (!empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $current_assets = $this->financialStatement->balance_statement->getItem('101')->getValue($selectedYear, $selectedQuarter);
            $total_assets = $this->financialStatement->balance_statement->getItem('2')->getValue($selectedYear, $selectedQuarter);
            if ($total_assets != 0) {
                $this->currentAssetToTotalAssetRatio = round(100 * $current_assets / $total_assets, 2);
            }
        }
        return $this;
    }

    /**
     * Calculate Cash / Current Assets Ratio
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\CurrentAssetStructureCalculator $this
     */
    public function calculateCashToCurrentAssetRatio($year = null, $quarter = null)
    {
        $this->cashToCurrentAssetRatio = null;
        if (!empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $current_assets = $this->financialStatement->balance_statement->getItem('101')->getValue($selectedYear, $selectedQuarter);
            $cash = $this->financialStatement->balance_statement->getItem('10101')->getValue($selectedYear, $selectedQuarter);
            if ($current_assets != 0) {
                $this->cashToCurrentAssetRatio = round(100 * $cash / $current_assets, 2);
            }
        }
        return $this;
    }

    /**
     * Calculate Current Financial Investing / Current Assets Ratio
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\CurrentAssetStructureCalculator $this
     */
    public function calculateCurrentFinancialInvestingToCurrentAssetRatio($year = null, $quarter = null)
    {
        $this->currentFinancialInvestingToCurrentAssetRatio = null;
        if (!empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $current_assets = $this->financialStatement->balance_statement->getItem('101')->getValue($selectedYear, $selectedQuarter);
            $current_financial_investing = $this->financialStatement->balance_statement->getItem('10102')->getValue($selectedYear, $selectedQuarter);
            if ($current_assets != 0) {
                $this->currentFinancialInvestingToCurrentAssetRatio = round(100 * $current_financial_investing / $current_assets, 2);
            }
        }
        return $this;
    }

    /**
     * Calculate Current Receivable Account / Current Assets Ratio
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\CurrentAssetStructureCalculator $this
     */
    public function calculateCurrentReceivableAccountToCurrentAssetRatio($year = null, $quarter = null)
    {
        $this->currentReceivableAccountToCurrentAssetRatio = null;
        if (!empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $current_assets = $this->financialStatement->balance_statement->getItem('101')->getValue($selectedYear, $selectedQuarter);
            $current_receivable_accounts = $this->financialStatement->balance_statement->getItem('10103')->getValue($selectedYear, $selectedQuarter);
            if ($current_assets != 0) {
                $this->currentReceivableAccountToCurrentAssetRatio = round(100 * $current_receivable_accounts / $current_assets, 2);
            }
        }
        return $this;
    }

    /**
     * Calculate inventories / Current Assets Ratio
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\CurrentAssetStructureCalculator $this
     */
    public function calculateInventoryToCurrentAssetRatio($year = null, $quarter = null)
    {
        $this->inventoryToCurrentAssetRatio = null;
        if (!empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $current_assets = $this->financialStatement->balance_statement->getItem('101')->getValue($selectedYear, $selectedQuarter);
            $current_inventories = $this->financialStatement->balance_statement->getItem('10104')->getValue($selectedYear, $selectedQuarter);
            if ($current_assets != 0) {
                $this->inventoryToCurrentAssetRatio = round(100 * $current_inventories / $current_assets, 2);
            }
        }
        return $this;
    }

    /**
     * Calculate other Current Assets / Current Assets Ratio
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\CurrentAssetStructureCalculator $this
     */
    public function calculateOtherCurrentAssetToCurrentAssetRatio($year = null, $quarter = null)
    {
        $this->otherCurrentAssetToCurrentAssetRatio = null;
        if (!empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $current_assets = $this->financialStatement->balance_statement->getItem('101')->getValue($selectedYear, $selectedQuarter);
            $other_current_assets = $this->financialStatement->balance_statement->getItem('10105')->getValue($selectedYear, $selectedQuarter);
            if ($current_assets != 0) {
                $this->otherCurrentAssetToCurrentAssetRatio = round(100 * $other_current_assets / $current_assets, 2);
            }
        }
        return $this;
    }
}
