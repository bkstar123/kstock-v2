<?php
/**
 * LongTermAssetStructureCalculator
 *
 * @author: tuanha
 * @date: 23-Aug-2022
 */
namespace App\Jobs\Financials\Calculators;

use App\Jobs\Financials\Calculators\BaseCalculator;

class LongTermAssetStructureCalculator extends BaseCalculator
{
    public $longTermAssetToTotalAssetRatio; //Tài sản dài hạn/Tổng tài sản

    public $fixedAssetToLongTermAssetRatio; //Tài sản cố định/Tài sản dai han

    public $tangibleFixedAssetToFixedAssetRatio; //Tài sản cố định hữu hình/Tài sản cố định

    public $financialLendingAssetToFixedAssetRatio; //Tài sản thuê tài chính/Tài sản cố định

    public $intangibleAssetToFixedAssetRatio; //Tài sản vô hình/Tài sản cố định

    public $constructionInProgressToLongTermAssetRatio; //Chi phí xây dựng cơ bản dở dang dài hạn / Tài sản dai han

    public $longTermReceivableToLongTermAssetRatio; //Phai thu dai han/Tai dan dai han

    public $investingRealEstateToLongTermAssetRatio; //Bat dong san dau tu / Tai san dai han

    public $longTermFinancialInvestingToLongTermRatio; //Dau tu tai chinh dai han / Tai san dai han

    public $otherLongTermAssetToLongTermRatio; //Tai san dai han khac / Tai san dai han

    /**
     * Calculate Long Term Asset / Total Asset Ratio
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\LongTermAssetStructureCalculator $this
     */
    public function calculateLongTermAssetToTotalAssetRatio($year = null, $quarter = null)
    {
        $this->longTermAssetToTotalAssetRatio = null;
        if (!empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $long_term_assets = $this->financialStatement->balance_statement->getItem('102')->getValue($selectedYear, $selectedQuarter);
            $total_assets = $this->financialStatement->balance_statement->getItem('2')->getValue($selectedYear, $selectedQuarter);
            if ($total_assets != 0) {
                $this->longTermAssetToTotalAssetRatio = round(100 * $long_term_assets / $total_assets, 2);
            }
        }
        return $this;
    }

    /**
     * Calculate Long Term Receivable / Long Term Asset Ratio
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\LongTermAssetStructureCalculator $this
     */
    public function calculateLongTermReceivableToLongTermAssetRatio($year = null, $quarter = null)
    {
        $this->longTermReceivableToLongTermAssetRatio = null;
        if (!empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $long_term_assets = $this->financialStatement->balance_statement->getItem('102')->getValue($selectedYear, $selectedQuarter);
            $long_term_receivables = $this->financialStatement->balance_statement->getItem('10201')->getValue($selectedYear, $selectedQuarter);
            if ($long_term_assets != 0) {
                $this->longTermReceivableToLongTermAssetRatio = round(100 * $long_term_receivables / $long_term_assets, 2);
            }
        }
        return $this;
    }

    /**
     * Calculate Fixed Asset / Long Term Asset Ratio
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\LongTermAssetStructureCalculator $this
     */
    public function calculateFixedAssetToLongTermAssetRatio($year = null, $quarter = null)
    {
        $this->fixedAssetToLongTermAssetRatio = null;
        if (!empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $long_term_assets = $this->financialStatement->balance_statement->getItem('102')->getValue($selectedYear, $selectedQuarter);
            $fixed_assets = $this->financialStatement->balance_statement->getItem('10202')->getValue($selectedYear, $selectedQuarter);
            if ($long_term_assets != 0) {
                $this->fixedAssetToLongTermAssetRatio = round(100 * $fixed_assets / $long_term_assets, 2);
            }
        }
        return $this;
    }

    /**
     * Calculate Tangible Fixed Asset / Fixed Asset Ratio
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\LongTermAssetStructureCalculator $this
     */
    public function calculateTangibleFixedAssetToFixedAssetRatio($year = null, $quarter = null)
    {
        $this->tangibleFixedAssetToFixedAssetRatio = null;
        if (!empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $fixed_assets = $this->financialStatement->balance_statement->getItem('10202')->getValue($selectedYear, $selectedQuarter);
            $tangible_fixed_assets = $this->financialStatement->balance_statement->getItem('1020201')->getValue($selectedYear, $selectedQuarter);
            if ($fixed_assets != 0) {
                $this->tangibleFixedAssetToFixedAssetRatio = round(100 * $tangible_fixed_assets / $fixed_assets, 2);
            }
        }
        return $this;
    }

    /**
     * Calculate Financial Lending Asset / Fixed Asset Ratio
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\LongTermAssetStructureCalculator $this
     */
    public function calculateFinancialLendingAssetToFixedAssetRatio($year = null, $quarter = null)
    {
        $this->financialLendingAssetToFixedAssetRatio = null;
        if (!empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $fixed_assets = $this->financialStatement->balance_statement->getItem('10202')->getValue($selectedYear, $selectedQuarter);
            $financial_lending_assets = $this->financialStatement->balance_statement->getItem('1020202')->getValue($selectedYear, $selectedQuarter);
            if ($fixed_assets != 0) {
                $this->financialLendingAssetToFixedAssetRatio = round(100 * $financial_lending_assets / $fixed_assets, 2);
            }
        }
        return $this;
    }

    /**
     * Calculate Intangible Asset / Fixed Asset Ratio
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\LongTermAssetStructureCalculator $this
     */
    public function calculateIntangibleAssetToFixedAssetRatio($year = null, $quarter = null)
    {
        $this->intangibleAssetToFixedAssetRatio = null;
        if (!empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $fixed_assets = $this->financialStatement->balance_statement->getItem('10202')->getValue($selectedYear, $selectedQuarter);
            $intangible_assets = $this->financialStatement->balance_statement->getItem('1020203')->getValue($selectedYear, $selectedQuarter);
            ;
            if ($fixed_assets != 0) {
                $this->intangibleAssetToFixedAssetRatio = round(100 * $intangible_assets / $fixed_assets, 2);
            }
        }
        return $this;
    }

    /**
     * Calculate Investing Real Estate / Long Term Asset Ratio
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\LongTermAssetStructureCalculator $this
     */
    public function calculateInvestingRealEstateToLongTermAssetRatio($year = null, $quarter = null)
    {
        $this->investingRealEstateToLongTermAssetRatio = null;
        if (!empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $long_term_assets = $this->financialStatement->balance_statement->getItem('102')->getValue($selectedYear, $selectedQuarter);
            $investingRealEstate = $this->financialStatement->balance_statement->getItem('10203')->getValue($selectedYear, $selectedQuarter);
            if ($long_term_assets != 0) {
                $this->investingRealEstateToLongTermAssetRatio = round(100 * $investingRealEstate / $long_term_assets, 2);
            }
        }
        return $this;
    }

    /**
     * Calculate Construction In Progress / Fixed Asset Ratio
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\LongTermAssetStructureCalculator $this
     */
    public function calculateConstructionInProgressToLongTermRatio($year = null, $quarter = null)
    {
        $this->constructionInProgressToLongTermAssetRatio = null;
        if (!empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $long_term_assets = $this->financialStatement->balance_statement->getItem('102')->getValue($selectedYear, $selectedQuarter);
            $constructionInProgress = $this->financialStatement->balance_statement->getItem('10204')->getValue($selectedYear, $selectedQuarter);
            if ($long_term_assets != 0) {
                $this->constructionInProgressToLongTermAssetRatio = round(100 * $constructionInProgress / $long_term_assets, 2);
            }
        }
        return $this;
    }

    /**
     * Calculate Long Term Financial Investing / Fixed Asset Ratio
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\LongTermAssetStructureCalculator $this
     */
    public function calculateLongTermFinancialInvestingToLongTermRatio($year = null, $quarter = null)
    {
        $this->longTermFinancialInvestingToLongTermRatio = null;
        if (!empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $long_term_assets = $this->financialStatement->balance_statement->getItem('102')->getValue($selectedYear, $selectedQuarter);
            $long_term_financial_investing = $this->financialStatement->balance_statement->getItem('10205')->getValue($selectedYear, $selectedQuarter);
            if ($long_term_assets != 0) {
                $this->longTermFinancialInvestingToLongTermRatio = round(100 * $long_term_financial_investing / $long_term_assets, 2);
            }
        }
        return $this;
    }

    /**
     * Calculate Long Term Financial Investing / Fixed Asset Ratio
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\LongTermAssetStructureCalculator $this
     */
    public function calculateOtherLongTermAssetToLongTermRatio($year = null, $quarter = null)
    {
        $this->otherLongTermAssetToLongTermRatio = null;
        if (!empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $long_term_assets = $this->financialStatement->balance_statement->getItem('102')->getValue($selectedYear, $selectedQuarter);
            $other_long_term_assets = $this->financialStatement->balance_statement->getItem('10206')->getValue($selectedYear, $selectedQuarter);
            if ($long_term_assets != 0) {
                $this->otherLongTermAssetToLongTermRatio = round(100 * $other_long_term_assets / $long_term_assets, 2);
            }
        }
        return $this;
    }
}
