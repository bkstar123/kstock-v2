<?php
/**
 * MScoreCalculator
 *
 * @author: tuanha
 * @date: 26-Sept-2022
 */
namespace App\Jobs\Financials\Calculators;

use App\Jobs\Financials\Calculators\BaseCalculator;

class MScoreCalculator extends BaseCalculator
{
    public $m8Score; // Do luong kha nang quan tri loi nhuan

    public $m5Score; // Do luong kha nang quan tri loi nhuan

    public $dsri; // Chi so phai thu khach hang so voi doanh thu

    public $gmi; // Chi so ti le lai gop

    public $aqi; // Chi so chat luong tai san

    public $sgi; // Chi so tang truong doanh thu ban hang

    public $depi; // Chi so ti le khau hao

    public $sgai; // Chi so chi phi ban hang va quan ly doanh nghiep

    public $tata; // Chi so bien don tich so voi tong tai san

    public $lvgi; // Chi so don bay tai chinh

    /**
     * Calculate Z-Scores for manufactoring enterprises
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\MScoreCalculator $this
     */
    public function calculateMScores($year = null, $quarter = null)
    {
        $this->m8Score = null;
        $this->m5Score = null;
        $this->dsri = null;
        $this->gmi = null;
        $this->aqi = null;
        $this->sgi = null;
        $this->depi = null;
        $this->sgai = null;
        $this->tata = null;
        $this->lvgi = null;
        if (!empty($this->financialStatement->balance_statement) &&
            !empty($this->financialStatement->income_statement) &&
            !empty($this->financialStatement->cash_flow_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $yearT_1 = $selectedYear - 1;
            $quarterT_1 = $selectedQuarter;
            $ppeT = $this->financialStatement->balance_statement->getItem('1020201')->getValue($selectedYear, $selectedQuarter) + $this->financialStatement->balance_statement->getItem('1020202')->getValue($selectedYear, $selectedQuarter) + $this->financialStatement->balance_statement->getItem('1020402')->getValue($selectedYear, $selectedQuarter) + $this->financialStatement->balance_statement->getItem('10203')->getValue($selectedYear, $selectedQuarter);
            $ppeT_1 = $this->financialStatement->balance_statement->getItem('1020201')->getValue($yearT_1, $quarterT_1) + $this->financialStatement->balance_statement->getItem('1020202')->getValue($yearT_1, $quarterT_1) + $this->financialStatement->balance_statement->getItem('1020402')->getValue($yearT_1, $quarterT_1) + $this->financialStatement->balance_statement->getItem('10203')->getValue($yearT_1, $quarterT_1);
            $receivablesT = $this->financialStatement->balance_statement->getItem('1010301')->getValue($selectedYear, $selectedQuarter) + $this->financialStatement->balance_statement->getItem('1020101')->getValue($selectedYear, $selectedQuarter);
            $receivablesT_1 = $this->financialStatement->balance_statement->getItem('1010301')->getValue($yearT_1, $quarterT_1) + $this->financialStatement->balance_statement->getItem('1020101')->getValue($yearT_1, $quarterT_1);
            $current_assetsT = $this->financialStatement->balance_statement->getItem('101')->getValue($selectedYear, $selectedQuarter);
            $current_assetsT_1 = $this->financialStatement->balance_statement->getItem('101')->getValue($yearT_1, $quarterT_1);
            // Long-term financial investments (included with "hard assets" in AQI)
            $lt_investT = $this->financialStatement->balance_statement->getItem('10205')->getValue($selectedYear, $selectedQuarter);
            $lt_investT_1 = $this->financialStatement->balance_statement->getItem('10205')->getValue($yearT_1, $quarterT_1);
            $total_assetsT = $this->financialStatement->balance_statement->getItem('2')->getValue($selectedYear, $selectedQuarter);
            $total_assetsT_1 = $this->financialStatement->balance_statement->getItem('2')->getValue($yearT_1, $quarterT_1);
            if ($selectedQuarter == 0) {
                $revenueT = $this->financialStatement->income_statement->getItem('3')->getValue($selectedYear, $selectedQuarter);
                $revenueT_1 = $this->financialStatement->income_statement->getItem('3')->getValue($yearT_1, $quarterT_1);
                $gross_profitT = $this->financialStatement->income_statement->getItem('5')->getValue($selectedYear, $selectedQuarter);
                $gross_profitT_1 =  $this->financialStatement->income_statement->getItem('5')->getValue($yearT_1, $quarterT_1);
                $deprecationT = $this->financialStatement->cash_flow_statement->getItem('10201')->getValue($selectedYear, $selectedQuarter);
                $deprecationT_1 = $this->financialStatement->cash_flow_statement->getItem('10201')->getValue($yearT_1, $quarterT_1);
                $sgaT = $this->financialStatement->income_statement->getItem('9')->getValue($selectedYear, $selectedQuarter) + $this->financialStatement->income_statement->getItem('10')->getValue($selectedYear, $selectedQuarter);
                $sgaT_1 = $this->financialStatement->income_statement->getItem('9')->getValue($yearT_1, $quarterT_1) + $this->financialStatement->income_statement->getItem('10')->getValue($yearT_1, $quarterT_1);
                // Beneish TATA uses total net income (incl. minority interest), item 19
                $net_profit = $this->financialStatement->income_statement->getItem('19')->getValue($selectedYear, $selectedQuarter);
                $cfoT = $this->financialStatement->cash_flow_statement->getItem('104')->getValue($selectedYear, $selectedQuarter);
            } else {
                // Calculate in the combination of 04 consecutive periods including the given period
                $revenueT = $this->financialStatement->income_statement->getItem('3')->getAccumulatedValueFromPastPeriod($selectedYear, $selectedQuarter, 3);
                $revenueT_1 = $this->financialStatement->income_statement->getItem('3')->getAccumulatedValueFromPastPeriod($yearT_1, $quarterT_1, 3);
                $gross_profitT = $this->financialStatement->income_statement->getItem('5')->getAccumulatedValueFromPastPeriod($selectedYear, $selectedQuarter, 3);
                $gross_profitT_1 =  $this->financialStatement->income_statement->getItem('5')->getAccumulatedValueFromPastPeriod($yearT_1, $quarterT_1, 3);
                $deprecationT = $this->financialStatement->cash_flow_statement->getItem('10201')->getAccumulatedValueFromPastPeriod($selectedYear, $selectedQuarter, 3);
                $deprecationT_1 = $this->financialStatement->cash_flow_statement->getItem('10201')->getAccumulatedValueFromPastPeriod($yearT_1, $quarterT_1, 3);
                $sgaT = $this->financialStatement->income_statement->getItem('9')->getAccumulatedValueFromPastPeriod($selectedYear, $selectedQuarter, 3) + $this->financialStatement->income_statement->getItem('10')->getAccumulatedValueFromPastPeriod($selectedYear, $selectedQuarter, 3);
                $sgaT_1 = $this->financialStatement->income_statement->getItem('9')->getAccumulatedValueFromPastPeriod($yearT_1, $quarterT_1, 3) + $this->financialStatement->income_statement->getItem('10')->getAccumulatedValueFromPastPeriod($yearT_1, $quarterT_1, 3);
                $net_profit = $this->financialStatement->income_statement->getItem('19')->getAccumulatedValueFromPastPeriod($selectedYear, $selectedQuarter, 3);
                $cfoT = $this->financialStatement->cash_flow_statement->getItem('104')->getAccumulatedValueFromPastPeriod($selectedYear, $selectedQuarter, 3);
            }
            if ($revenueT != 0 && $revenueT_1 != 0) {
                $dsriT = $receivablesT / $revenueT;
                $dsriT_1 = $receivablesT_1 / $revenueT_1;
                $gmiT = $gross_profitT / $revenueT;
                $gmiT_1 = $gross_profitT_1 / $revenueT_1;
                $sgaiT = $sgaT / $revenueT;
                $sgaiT_1 = $sgaT_1 / $revenueT_1;
                if ($dsriT_1 != 0) {
                    $this->dsri = $dsriT / $dsriT_1;
                }
                if ($gmiT != 0) {
                    $this->gmi = $gmiT_1 / $gmiT;
                }
                if ($sgaiT_1 != 0) {
                    $this->sgai = $sgaiT / $sgaiT_1;
                }
            }
            if ($total_assetsT != 0 && $total_assetsT_1 != 0) {
                $aqiT = 1 - ($current_assetsT + $ppeT + $lt_investT) / $total_assetsT;
                $aqiT_1 = 1 - ($current_assetsT_1 + $ppeT_1 + $lt_investT_1) / $total_assetsT_1;
                $lvgiT = ($this->financialStatement->balance_statement->getItem('30101')->getValue($selectedYear, $selectedQuarter) + $this->financialStatement->balance_statement->getItem('3010206')->getValue($selectedYear, $selectedQuarter)) / $total_assetsT;
                $lvgiT_1 = ($this->financialStatement->balance_statement->getItem('30101')->getValue($yearT_1, $quarterT_1) + $this->financialStatement->balance_statement->getItem('3010206')->getValue($yearT_1, $quarterT_1)) / $total_assetsT_1;
                $this->tata = ($net_profit - $cfoT) / $total_assetsT;
                if ($aqiT_1 != 0) {
                    $this->aqi = $aqiT / $aqiT_1;
                }
                if ($lvgiT_1 != 0) {
                    $this->lvgi = $lvgiT / $lvgiT_1;
                }
            }
            if ($revenueT_1 != 0) {
                $this->sgi = $revenueT / $revenueT_1;
            }
            if (($ppeT + $deprecationT) != 0 && ($ppeT_1 + $deprecationT_1) != 0) {
                $depiT = $deprecationT / ($ppeT + $deprecationT);
                $depiT_1 = $deprecationT_1 / ($ppeT_1 + $deprecationT_1);
                if ($depiT != 0) {
                    $this->depi = $depiT_1 / $depiT;
                }
            }
            if (!is_null($this->dsri) && !is_null($this->gmi) &&
                !is_null($this->aqi) && !is_null($this->sgi) &&
                !is_null($this->depi) && !is_null($this->sgai) &&
                !is_null($this->tata) && !is_null($this->lvgi)) {
                $this->m8Score = -4.84 + 0.92 * $this->dsri + 0.528 * $this->gmi + 0.404 * $this->aqi + 0.892 * $this->sgi + 0.115 * $this->depi - 0.172 * $this->sgai + 4.679 * $this->tata - 0.327 * $this->lvgi;
                $this->m5Score = -6.065 + 0.823 * $this->dsri + 0.906 * $this->gmi + 0.593 * $this->aqi + 0.717 * $this->sgi + 0.107 * $this->depi;
            }
        }
        return $this;
    }
}
