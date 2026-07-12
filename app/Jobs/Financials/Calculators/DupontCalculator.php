<?php
/**
 * DupontCalculator
 *
 * @author: tuanha
 * @date: 27-Aug-2022
 */
namespace App\Jobs\Financials\Calculators;

use App\Jobs\Financials\Calculators\BaseCalculator;
use App\Jobs\Financials\Calculators\ProfitabilityCalculator;
use App\Jobs\Financials\Calculators\FinancialLeverageCalculator;
use App\Jobs\Financials\Calculators\OperatingEffectivenessCalculator;

class DupontCalculator extends BaseCalculator
{
    public $roaa; // Ti suat sinh loi cua tong tai san binh quan

    public $averageFinancialLeverage; // He so don bay tai chinh binh quan

    public $ros2; // Ti suat loi nhuan rong co dong cong ty me

    public $averageTotalAssetTurnOver; // Vong quay tong tai san binh quan

    public $earningAfterTaxParentCompanyToEarningBeforeTax; // LNST co dong cong ty me / LNTT

    public $earningAfterTaxToEarningBeforeTax; // LNST / LNTT

    public $earningBeforeTaxToEBIT; // Loi nhuan truoc thue / EBIT

    public $ebitMargin; // Ti suat EBIT tren doanh thu thuan

    public $roea; //Ti suat sinh loi tren VCSH binh quan

    /**
     * Calculate Dupont components
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\DupontCalculator $this
     */
    public function calculateDupontComponents($year = null, $quarter = null)
    {
        $this->roaa = null;
        $this->averageFinancialLeverage = null;
        $this->ros2 = null;
        $this->ebitMargin = null;
        $this->averageTotalAssetTurnOver = null;
        $this->earningAfterTaxParentCompanyToEarningBeforeTax = null;
        $this->earningAfterTaxToEarningBeforeTax = null;
        $this->earningBeforeTaxToEBIT = null;
        if (!empty($this->financialStatement->balance_statement) && !empty($this->financialStatement->income_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $profitabilityCalculator = new ProfitabilityCalculator($this->financialStatement);
            $financialLeverageCalculator = new FinancialLeverageCalculator($this->financialStatement);
            $operatingEffectivenessCalculator = new OperatingEffectivenessCalculator($this->financialStatement);
            $this->roaa = $profitabilityCalculator->calculateROAA($selectedYear, $selectedQuarter)->roaa;
            $this->averageFinancialLeverage = $financialLeverageCalculator->calculateAverageTotalAssetToAverageEquityRatio($selectedYear, $selectedQuarter)->averageTotalAssetToAverageEquityRatio;
            $this->averageTotalAssetTurnOver = $operatingEffectivenessCalculator->calculateTotalAssetTurnoverRatio($selectedYear, $selectedQuarter)->totalAssetTurnoverRatio;
            // The margin/burden factors below are computed on a TTM basis (trailing 4
            // quarters via ttmOrAnnual) so they share the same time frame as ROAA, the
            // asset turnover and the leverage above. A quarter-only margin multiplied by
            // an annualised (TTM) turnover breaks the DuPont identity, which is why
            // Levels 3/5 previously reported an ROEA inconsistent with Level 2. The
            // global calculateROS2()/calculateEBITMargin() (quarter-only) are left
            // untouched — this TTM basis is specific to the DuPont decomposition.
            $incomeStatement = $this->financialStatement->income_statement;
            $netProfitParentTtm  = $this->ttmOrAnnual($incomeStatement->getItem('21'), $selectedYear, $selectedQuarter);
            $netProfitTtm        = $this->ttmOrAnnual($incomeStatement->getItem('19'), $selectedYear, $selectedQuarter);
            $earningBeforeTaxTtm = $this->ttmOrAnnual($incomeStatement->getItem('15'), $selectedYear, $selectedQuarter);
            $interestTtm         = $this->ttmOrAnnual($incomeStatement->getItem('701'), $selectedYear, $selectedQuarter);
            $revenueTtm          = $this->ttmOrAnnual($incomeStatement->getItem('3'), $selectedYear, $selectedQuarter);
            $eBitTtm             = $earningBeforeTaxTtm + $interestTtm;
            if ($revenueTtm != 0) {
                $this->ros2 = round(100 * $netProfitParentTtm / $revenueTtm, 2);
                $this->ebitMargin = round(100 * $eBitTtm / $revenueTtm, 2);
            }
            if ($earningBeforeTaxTtm != 0) {
                $this->earningAfterTaxParentCompanyToEarningBeforeTax = round($netProfitParentTtm / $earningBeforeTaxTtm, 4);
                $this->earningAfterTaxToEarningBeforeTax = round($netProfitTtm / $earningBeforeTaxTtm, 4);
            }
            if ($eBitTtm != 0) {
                $this->earningBeforeTaxToEBIT = round($earningBeforeTaxTtm / $eBitTtm, 4);
            }
        }
        return $this;
    }
}
