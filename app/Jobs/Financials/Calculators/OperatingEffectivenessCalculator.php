<?php
/**
 * OperatingEffectivenessCalculator
 *
 * @author: tuanha
 * @date: 18-Aug-2022
 */
namespace App\Jobs\Financials\Calculators;

use App\Jobs\Financials\Calculators\BaseCalculator;

class OperatingEffectivenessCalculator extends BaseCalculator
{
    public $receivableTurnoverRatio; //Vòng quay các khoản phải thu khách hàng (quy đổi năm - TTM nếu là báo cáo quý)

    public $receivableTurnoverRatioQuarterOnly;

    public $averageCollectionPeriod; //Thời gian thu tiền khách hàng bình quân

    public $averageCollectionPeriodQuarterOnly;

    public $inventoryTurnoverRatio; //Vòng quay hàng tồn kho

    public $inventoryTurnoverRatioQuarterOnly;

    public $averageAgeOfInventory;  //Thời gian tồn kho bình quân

    public $averageAgeOfInventoryQuarterOnly;

    public $accountsPayableTurnoverRatio; //Vòng quay phải trả nhà cung cấp

    public $accountsPayableTurnoverRatioQuarterOnly;

    public $averageAccountPayableDuration; //Thời gian trả tiền nhà cung cấp bình quân

    public $averageAccountPayableDurationQuarterOnly;

    public $cashConversionCycle; //Chu kỳ chuyển đổi tiền mặt

    public $cashConversionCycleQuarterOnly;

    public $fixedAssetTurnoverRatio; //Vòng quay tài sản cố định

    public $fixedAssetTurnoverRatioQuarterOnly;

    public $totalAssetTurnoverRatio; //Vòng quay tổng tài sản

    public $totalAssetTurnoverRatioQuarterOnly;

    public $equityTurnoverRatio; //Vòng quay VCSH

    public $equityTurnoverRatioQuarterOnly;

    public $averageEquityUsedForEquityTurnover; // VCSH bình quân dùng làm mẫu số - phục vụ banner cảnh báo khi âm

    /**
      * Calculate Receivable Turn-over Ratio
      *
      * @param int $year
      * @param int $quarter
      * @return \App\Jobs\Financials\Calculators\OperatingEffectivenessCalculator $this
      */
    public function calculateReceivableTurnoverRatio($year = null, $quarter = null)
    {
        $this->receivableTurnoverRatio = null;
        $this->averageCollectionPeriod = null;
        $this->receivableTurnoverRatioQuarterOnly = null;
        $this->averageCollectionPeriodQuarterOnly = null;
        if (!empty($this->financialStatement->income_statement) && !empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $revenueItem = $this->financialStatement->income_statement->getItem('3');
            $revenue = $this->ttmOrAnnual($revenueItem, $selectedYear, $selectedQuarter);
            $revenueQuarter = $revenueItem->getValue($selectedYear, $selectedQuarter);
            $averageCurrentCustomerReceivables = $this->financialStatement->balance_statement->getItem('1010301')->getAverageValue($selectedYear, $selectedQuarter);
            if ($averageCurrentCustomerReceivables != 0 && $revenue != 0) {
                $this->receivableTurnoverRatio = round($revenue / $averageCurrentCustomerReceivables, 4);
                $this->averageCollectionPeriod = round(365 * $averageCurrentCustomerReceivables / $revenue, 0);
            }
            if ($averageCurrentCustomerReceivables != 0 && $revenueQuarter != 0) {
                $this->receivableTurnoverRatioQuarterOnly = round($revenueQuarter / $averageCurrentCustomerReceivables, 4);
                $this->averageCollectionPeriodQuarterOnly = round(365 * $averageCurrentCustomerReceivables / $revenueQuarter, 0);
            }
        }
        return $this;
    }

    /**
     * Calculate Inventory Turn-over Ratio
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\OperatingEffectivenessCalculator $this
     */
    public function calculateInventoryTurnoverRatio($year = null, $quarter = null)
    {
        $this->inventoryTurnoverRatio = null;
        $this->averageAgeOfInventory = null;
        $this->inventoryTurnoverRatioQuarterOnly = null;
        $this->averageAgeOfInventoryQuarterOnly = null;
        if (!empty($this->financialStatement->income_statement) && !empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $cogsItem = $this->financialStatement->income_statement->getItem('4');
            $cogs = $this->ttmOrAnnual($cogsItem, $selectedYear, $selectedQuarter);
            $cogsQuarter = $cogsItem->getValue($selectedYear, $selectedQuarter);
            $averageInventories = $this->financialStatement->balance_statement->getItem('10104')->getAverageValue($selectedYear, $selectedQuarter);
            if ($averageInventories != 0 && $cogs != 0) {
                $this->inventoryTurnoverRatio = round($cogs / $averageInventories, 4);
                $this->averageAgeOfInventory = round(365 * $averageInventories / $cogs, 0);
            }
            if ($averageInventories != 0 && $cogsQuarter != 0) {
                $this->inventoryTurnoverRatioQuarterOnly = round($cogsQuarter / $averageInventories, 4);
                $this->averageAgeOfInventoryQuarterOnly = round(365 * $averageInventories / $cogsQuarter, 0);
            }
        }
        return $this;
    }

    /**
     * Calculate Accounts Payable Turnover Ratio
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\OperatingEffectivenessCalculator $this
     */
    public function calculateAccountsPayableTurnoverRatio($year = null, $quarter = null)
    {
        $this->accountsPayableTurnoverRatio = null;
        $this->averageAccountPayableDuration = null;
        $this->accountsPayableTurnoverRatioQuarterOnly = null;
        $this->averageAccountPayableDurationQuarterOnly = null;
        if (!empty($this->financialStatement->income_statement) && !empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $cogsItem = $this->financialStatement->income_statement->getItem('4');
            $cogs = $this->ttmOrAnnual($cogsItem, $selectedYear, $selectedQuarter);
            $cogsQuarter = $cogsItem->getValue($selectedYear, $selectedQuarter);
            $averageCurrentAccountPayables = $this->financialStatement->balance_statement->getItem('3010103')->getAverageValue($selectedYear, $selectedQuarter);
            if ($averageCurrentAccountPayables != 0 && $cogs != 0) {
                $this->accountsPayableTurnoverRatio = round($cogs / $averageCurrentAccountPayables, 4);
                $this->averageAccountPayableDuration = round(365 * $averageCurrentAccountPayables / $cogs, 0);
            }
            if ($averageCurrentAccountPayables != 0 && $cogsQuarter != 0) {
                $this->accountsPayableTurnoverRatioQuarterOnly = round($cogsQuarter / $averageCurrentAccountPayables, 4);
                $this->averageAccountPayableDurationQuarterOnly = round(365 * $averageCurrentAccountPayables / $cogsQuarter, 0);
            }
        }
        return $this;
    }

    /**
     * Calculate Cash Conversion Cycle
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\OperatingEffectivenessCalculator $this
     */
    public function calculateCashConversionCycle($year = null, $quarter = null)
    {
        $this->cashConversionCycle = null;
        $this->cashConversionCycleQuarterOnly = null;
        if (!empty($this->financialStatement->income_statement) && !empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $revenueItem = $this->financialStatement->income_statement->getItem('3');
            $cogsItem = $this->financialStatement->income_statement->getItem('4');
            $revenue = $this->ttmOrAnnual($revenueItem, $selectedYear, $selectedQuarter);
            $revenueQuarter = $revenueItem->getValue($selectedYear, $selectedQuarter);
            $cogs = $this->ttmOrAnnual($cogsItem, $selectedYear, $selectedQuarter);
            $cogsQuarter = $cogsItem->getValue($selectedYear, $selectedQuarter);
            $averageCurrentCustomerReceivables = $this->financialStatement->balance_statement->getItem('1010301')->getAverageValue($selectedYear, $selectedQuarter);
            $averageInventories = $this->financialStatement->balance_statement->getItem('10104')->getAverageValue($selectedYear, $selectedQuarter);
            $averageCurrentAccountPayables = $this->financialStatement->balance_statement->getItem('3010103')->getAverageValue($selectedYear, $selectedQuarter);
            if ($revenue != 0 && $cogs != 0) {
                $dso = round(365 * $averageCurrentCustomerReceivables / $revenue, 0);
                $dpo = round(365 * $averageCurrentAccountPayables / $cogs, 0);
                $dio = round(365 * $averageInventories / $cogs, 0);
                $this->cashConversionCycle = $dso + $dio - $dpo;
            }
            if ($revenueQuarter != 0 && $cogsQuarter != 0) {
                $dsoQ = round(365 * $averageCurrentCustomerReceivables / $revenueQuarter, 0);
                $dpoQ = round(365 * $averageCurrentAccountPayables / $cogsQuarter, 0);
                $dioQ = round(365 * $averageInventories / $cogsQuarter, 0);
                $this->cashConversionCycleQuarterOnly = $dsoQ + $dioQ - $dpoQ;
            }
        }
        return $this;
    }

    /**
     * Calculate Fixed Asset Turnover Ratio
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\OperatingEffectivenessCalculator $this
     */
    public function calculateFixedAssetTurnoverRatio($year = null, $quarter = null)
    {
        $this->fixedAssetTurnoverRatio = null;
        $this->fixedAssetTurnoverRatioQuarterOnly = null;
        if (!empty($this->financialStatement->income_statement) && !empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $revenueItem = $this->financialStatement->income_statement->getItem('3');
            $revenue = $this->ttmOrAnnual($revenueItem, $selectedYear, $selectedQuarter);
            $revenueQuarter = $revenueItem->getValue($selectedYear, $selectedQuarter);
            $averageFixedAssets = $this->financialStatement->balance_statement->getItem('10202')->getAverageValue($selectedYear, $selectedQuarter);
            if ($averageFixedAssets != 0) {
                $this->fixedAssetTurnoverRatio = round($revenue / $averageFixedAssets, 4);
                $this->fixedAssetTurnoverRatioQuarterOnly = round($revenueQuarter / $averageFixedAssets, 4);
            }
        }
        return $this;
    }

    /**
     * Calculate Total Asset Turnover Ratio
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\OperatingEffectivenessCalculator $this
     */
    public function calculateTotalAssetTurnoverRatio($year = null, $quarter = null)
    {
        $this->totalAssetTurnoverRatio = null;
        $this->totalAssetTurnoverRatioQuarterOnly = null;
        if (!empty($this->financialStatement->income_statement) && !empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $revenueItem = $this->financialStatement->income_statement->getItem('3');
            $revenue = $this->ttmOrAnnual($revenueItem, $selectedYear, $selectedQuarter);
            $revenueQuarter = $revenueItem->getValue($selectedYear, $selectedQuarter);
            $averageTotalAssets = $this->financialStatement->balance_statement->getItem('2')->getAverageValue($selectedYear, $selectedQuarter);
            if ($averageTotalAssets != 0) {
                $this->totalAssetTurnoverRatio = round($revenue / $averageTotalAssets, 4);
                $this->totalAssetTurnoverRatioQuarterOnly = round($revenueQuarter / $averageTotalAssets, 4);
            }
        }
        return $this;
    }

    /**
     * Calculate Total Asset Turnover Ratio
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\OperatingEffectivenessCalculator $this
     */
    public function calculateEquityTurnoverRatio($year = null, $quarter = null)
    {
        $this->equityTurnoverRatio = null;
        $this->equityTurnoverRatioQuarterOnly = null;
        $this->averageEquityUsedForEquityTurnover = null;
        if (!empty($this->financialStatement->income_statement) && !empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $revenueItem = $this->financialStatement->income_statement->getItem('3');
            $revenue = $this->ttmOrAnnual($revenueItem, $selectedYear, $selectedQuarter);
            $revenueQuarter = $revenueItem->getValue($selectedYear, $selectedQuarter);
            $averageEquity = $this->financialStatement->balance_statement->getItem('302')->getAverageValue($selectedYear, $selectedQuarter);
            $this->averageEquityUsedForEquityTurnover = $averageEquity;
            if ($averageEquity != 0) {
                $this->equityTurnoverRatio = round($revenue / $averageEquity, 4);
                $this->equityTurnoverRatioQuarterOnly = round($revenueQuarter / $averageEquity, 4);
            }
        }
        return $this;
    }
}
