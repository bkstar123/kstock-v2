<?php
/**
 * ZScoreCalculator
 *
 * @author: tuanha
 * @date: 24-Sept-2022
 */
namespace App\Jobs\Financials\Calculators;

use App\Jobs\Financials\Calculators\BaseCalculator;

class ZScoreCalculator extends BaseCalculator
{
    public $zScore;

    public $z2Score; //Z''-score

    public $x1; //Ti suat von luu dong tren tong tai san

    public $x2; //Ti suat loi nhuan giu lai tren tong tai san

    public $x3; //Ti suat LNTT va lai vay tren tong tai san

    public $x4; //VCSH/Tong no

    public $x5; //Vong quay tong tai san

    /**
     * Calculate Z-Scores for manufactoring enterprises
     *
     * @param int $year
     * @param int $quarter
     * @return \App\Jobs\Financials\Calculators\ZScoreCalculator $this
     */
    public function calculateZScores($year = null, $quarter = null)
    {
        $this->zScore = null;
        $this->z2Score = null;
        $this->x1 = null;
        $this->x2 = null;
        $this->x3 = null;
        $this->x4 = null;
        $this->x5 = null;
        if (!empty($this->financialStatement->balance_statement) &&
            !empty($this->financialStatement->income_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $total_assets = $this->financialStatement->balance_statement->getItem('2')->getValue($selectedYear, $selectedQuarter);
            $current_assets = $this->financialStatement->balance_statement->getItem('101')->getValue($selectedYear, $selectedQuarter);
            $current_liabilities = $this->financialStatement->balance_statement->getItem('30101')->getValue($selectedYear, $selectedQuarter);
            $net_working_capital = $current_assets - $current_liabilities;
            $total_liabilities = $this->financialStatement->balance_statement->getItem('301')->getValue($selectedYear, $selectedQuarter);
            $equities = $this->financialStatement->balance_statement->getItem('302')->getValue($selectedYear, $selectedQuarter);
            if ($selectedQuarter == 0) {
                // Altman X2 uses the ACCUMULATED retained-earnings balance (a
                // balance-sheet stock), not the periodic change.
                $retained_earnings = $this->financialStatement->balance_statement->getItem('3020111')->getValue($selectedYear, $selectedQuarter);
                $ebit = $this->financialStatement->income_statement->getItem('15')->getValue($selectedYear, $selectedQuarter) + $this->financialStatement->income_statement->getItem('701')->getValue($selectedYear, $selectedQuarter);
                $revenue = $this->financialStatement->income_statement->getItem('3')->getValue($selectedYear, $selectedQuarter);
            } else {
                // EBIT & revenue accumulate 04 consecutive periods (TTM); retained
                // earnings is a balance, taken at the given period.
                $retained_earnings = $this->financialStatement->balance_statement->getItem('3020111')->getValue($selectedYear, $selectedQuarter);
                $ebit = $this->financialStatement->income_statement->getItem('15')->getAccumulatedValueFromPastPeriod($selectedYear, $selectedQuarter, 3) + $this->financialStatement->income_statement->getItem('701')->getAccumulatedValueFromPastPeriod($selectedYear, $selectedQuarter, 3);
                $revenue = $this->financialStatement->income_statement->getItem('3')->getAccumulatedValueFromPastPeriod($selectedYear, $selectedQuarter, 3);
            }
            if ($total_assets != 0) {
                $this->x1 = $net_working_capital / $total_assets;
                $this->x2 = $retained_earnings / $total_assets;
                $this->x3 = $ebit / $total_assets;
                $this->x5 = $revenue / $total_assets;
            }
            if ($total_liabilities != 0) {
                $this->x4 = $equities / $total_liabilities;
            }
            if (!is_null($this->x1) && !is_null($this->x2) && !is_null($this->x3) && !is_null($this->x4)) {
                $this->z2Score = 6.56 * $this->x1 + 3.26 * $this->x2 + 6.72 * $this->x3 + 1.05 * $this->x4;
                if (!is_null($this->x5)) {
                    $this->zScore = 1.2 * $this->x1 + 1.4 *$this->x2 + 3.3 * $this->x3 + 0.6 * $this->x4 + 0.999 * $this->x5;
                }
            }
        }
        return $this;
    }
}
