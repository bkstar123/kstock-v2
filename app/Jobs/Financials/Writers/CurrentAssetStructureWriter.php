<?php
/**
 * CurrentAssetStructureWriter trait
 *
 * @author: tuanha
 * @date: 14-Aug-2022
 */
namespace App\Jobs\Financials\Writers;

use App\Jobs\Financials\Calculators\CurrentAssetStructureCalculator;

trait CurrentAssetStructureWriter
{
    /**
     * Write Current Assets / Total Assets Ratio
     *
     * @param \App\Jobs\Financials\Calculators\CurrentAssetStructureCalculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    public function writeCurrentAssetToTotalAssetRatio(CurrentAssetStructureCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateCurrentAssetToTotalAssetRatio($year, $quarter)->currentAssetToTotalAssetRatio
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tài sản ngắn hạn / Tổng tài sản',
            'alias' => 'Current Assets/Total Assets',
            'group' => 'Chỉ số Cơ cấu tài sản ngắn hạn',
            'unit' => '%',
            'description' => 'Phản ánh tỉ trọng tài sản ngắn hạn trên tổng tài sản của doanh nghiệp',
            'values' => $values
        ]);
        return $this;
    }

    /**
    * write Cash / Current Assets Ratio
    *
    * @param \App\Jobs\Financials\Calculators\CurrentAssetStructureCalculator
    * @param  int $year
    * @param  int $quarter
    * @return $this
    */
    public function writeCashToCurrentAssetRatio(CurrentAssetStructureCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateCashToCurrentAssetRatio($year, $quarter)->cashToCurrentAssetRatio
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tiền và các khoản tương đương tiền/Tài sản ngắn hạn',
            'alias' => 'Cash/Current Assets',
            'group' => 'Chỉ số Cơ cấu tài sản ngắn hạn',
            'unit' => '%',
            'description' => 'Phản ánh tỉ trọng tiền mặt à các khoản tương đương tiền trên tài sản ngắn hạn của doanh nghiệp',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write Current Financial Investing / Current Assets Ratio
     *
     * @param \App\Jobs\Financials\Calculators\CurrentAssetStructureCalculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    public function writeCurrentFinancialInvestingToCurrentAssetRatio(CurrentAssetStructureCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateCurrentFinancialInvestingToCurrentAssetRatio($year, $quarter)->currentFinancialInvestingToCurrentAssetRatio
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Đầu tư tài chính ngắn hạn/Tài sản ngắn hạn',
            'alias' => 'Current Financial Investing/Current Assets',
            'group' => 'Chỉ số Cơ cấu tài sản ngắn hạn',
            'unit' => '%',
            'description' => 'Phản ánh tỉ trọng đầu tư tài chính ngắn hạn trên tài sản ngắn hạn của doanh nghiệp',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write Current Receivable Account / Current Assets Ratio
     *
     * @param \App\Jobs\Financials\Calculators\CurrentAssetStructureCalculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    public function writeCurrentReceivableAccountToCurrentAssetRatio(CurrentAssetStructureCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateCurrentReceivableAccountToCurrentAssetRatio($year, $quarter)->currentReceivableAccountToCurrentAssetRatio
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Phải thu ngắn hạn/Tài sản ngắn hạn',
            'alias' => 'Current Receivable Accounts/Current Assets',
            'group' => 'Chỉ số Cơ cấu tài sản ngắn hạn',
            'unit' => '%',
            'description' => 'Phản ánh tỉ trọng các khoản phải thu khách hàng ngắn hạn trên tài sản ngắn hạn của doanh nghiệp',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write Inventories / Current Assets Ratio
     *
     * @param \App\Jobs\Financials\Calculators\CurrentAssetStructureCalculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    public function writeInventoryToCurrentAssetRatio(CurrentAssetStructureCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateInventoryToCurrentAssetRatio($year, $quarter)->inventoryToCurrentAssetRatio
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Hàng tồn kho/Tài sản ngắn hạn',
            'alias' => 'Inventories/Current Assets',
            'group' => 'Chỉ số Cơ cấu tài sản ngắn hạn',
            'unit' => '%',
            'description' => 'Phản ánh tỉ trọng hàng tồn kho trên tài sản ngắn hạn của doanh nghiệp',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write other Current Assets / Current Assets Ratio
     *
     * @param \App\Jobs\Financials\Calculators\CurrentAssetStructureCalculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    public function calculateOtherCurrentAssetToCurrentAssetRatio(CurrentAssetStructureCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateOtherCurrentAssetToCurrentAssetRatio($year, $quarter)->otherCurrentAssetToCurrentAssetRatio
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tài sản ngắn hạn khác/Tài sản ngắn hạn',
            'alias' => 'Other Current Assets/Current Assets',
            'group' => 'Chỉ số Cơ cấu tài sản ngắn hạn',
            'unit' => '%',
            'description' => 'Phản ánh tỉ trọng tài sản ngắn hạn khác trên tài sản ngắn hạn của doanh nghiệp',
            'values' => $values
        ]);
        return $this;
    }
}
