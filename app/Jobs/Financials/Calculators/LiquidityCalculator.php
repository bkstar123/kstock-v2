<?php
/**
 * LiquidityCalculator
 *
 * @author: tuanha
 * @date: 18-Aug-2022
 */
namespace App\Jobs\Financials\Calculators;

use App\Jobs\Financials\Calculators\BaseCalculator;

class LiquidityCalculator extends BaseCalculator
{
    public $overallSolvencyRatio; //He so thanh toan tong quat

    public $currentRatio; //He so thanh toan hien hanh

    public $quickRatio; // He so thanh toan nhanh

    public $quickRatio2; //He so thanh toan nhanh

    public $cashRatio; //He so thanh toan tuc thoi

    public $interestCoverageRatio; //He so thanh toan lai vay

    /**
     * Calculate Overall Solvency ratio - He so kha nang thanh toan tong quat
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\LiquidityCalculator $this
     */
    public function calculateOverallSolvencyRatio($year = null, $quarter = null)
    {
        $this->overallSolvencyRatio = null;
        if (!empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $assets = $this->financialStatement->balance_statement->getItem('2')->getValue($selectedYear, $selectedQuarter);
            $liabilities = $this->financialStatement->balance_statement->getItem('301')->getValue($selectedYear, $selectedQuarter);
            if ($liabilities != 0) {
                $this->overallSolvencyRatio = round($assets / $liabilities, 4);
            }
        }
        return $this;
    }
    
    /**
    * Calculate Current ratio - He so kha nang thanh toan hien hanh (ngan han)
    *
    * @param int $year
     * @param int $quarter
    * @return \App\Jobs\Financials\Calculators\LiquidityCalculator $this
    */
    public function calculateCurrentRatio($year = null, $quarter = null)
    {
        $this->currentRatio = null;
        if (!empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $currentAssets = $this->financialStatement->balance_statement->getItem('101')->getValue($selectedYear, $selectedQuarter);
            $currentLiabilities = $this->financialStatement->balance_statement->getItem('30101')->getValue($selectedYear, $selectedQuarter);
            if ($currentLiabilities != 0) {
                $this->currentRatio = round($currentAssets / $currentLiabilities, 4);
            }
        }
        return $this;
    }

    /**
     * Calculate Quick Ratio - He so kha nang thanh toan nhanh
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\LiquidityCalculator $this
     */
    public function calculateQuickRatio($year = null, $quarter = null)
    {
        $this->quickRatio = null;
        if (!empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $currentAssets = $this->financialStatement->balance_statement->getItem('101')->getValue($selectedYear, $selectedQuarter);
            $inventories = $this->financialStatement->balance_statement->getItem('10104')->getValue($selectedYear, $selectedQuarter);
            $currentLiabilities = $this->financialStatement->balance_statement->getItem('30101')->getValue($selectedYear, $selectedQuarter);
            if ($currentLiabilities != 0) {
                $this->quickRatio = round(($currentAssets - $inventories) / $currentLiabilities, 4);
            }
        }
        return $this;
    }

    /**
     * Calculate Quick Ratio 2 - He so kha nang thanh toan nhanh 2 (loai bo hang ton kho va phai thu ngan han)
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\LiquidityCalculator $this
     */
    public function calculateQuickRatio2($year = null, $quarter = null)
    {
        $this->quickRatio2 = null;
        if (!empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $currentAssets = $this->financialStatement->balance_statement->getItem('101')->getValue($selectedYear, $selectedQuarter);
            $inventories = $this->financialStatement->balance_statement->getItem('10104')->getValue($selectedYear, $selectedQuarter);
            $currentReceivableAccounts = $this->financialStatement->balance_statement->getItem('10103')->getValue($selectedYear, $selectedQuarter);
            $currentLiabilities = $this->financialStatement->balance_statement->getItem('30101')->getValue($selectedYear, $selectedQuarter);
            if ($currentLiabilities != 0) {
                $this->quickRatio2 = round(($currentAssets - $inventories - $currentReceivableAccounts) / $currentLiabilities, 4);
            }
        }
        return $this;
    }

    /**
     * Calculate CashRatio - He so kha nang thanh toan tuc thoi
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\LiquidityCalculator $this
     */
    public function calculateCashRatio($year = null, $quarter = null)
    {
        $this->cashRatio = null;
        if (!empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $cashAndEquivalents = $this->financialStatement->balance_statement->getItem('10101')->getValue($selectedYear, $selectedQuarter);
            $currentLiabilities = $this->financialStatement->balance_statement->getItem('30101')->getValue($selectedYear, $selectedQuarter);
            if ($currentLiabilities != 0) {
                $this->cashRatio = round($cashAndEquivalents / $currentLiabilities, 4);
            }
        }
        return $this;
    }

    /**
     * Calculate Interest Coverage Ratio - He so kha nang chi tra lai vay
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\LiquidityCalculator $this
     */
    public function calculateInterestCoverageRatio($year = null, $quarter = null)
    {
        $this->interestCoverageRatio = null;
        if (!empty($this->financialStatement->income_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $eBIT = $this->financialStatement->income_statement->getItem('15')->getValue($selectedYear, $selectedQuarter) + $this->financialStatement->income_statement->getItem('701')->getValue($selectedYear, $selectedQuarter);
            $interest = $this->financialStatement->income_statement->getItem('701')->getValue($selectedYear, $selectedQuarter);
            if ($interest != 0) {
                $this->interestCoverageRatio = round($eBIT / $interest, 4);
            }
        }
        return $this;
    }
}
