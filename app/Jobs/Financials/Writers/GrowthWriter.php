<?php
/**
 * GrowthWriter trait
 *
 * @author: tuanha
 * @date: 24-Aug-2022
 */
namespace App\Jobs\Financials\Writers;

use App\Jobs\Financials\Calculators\GrowthCalculator;

trait GrowthWriter
{
    /**
     * Write Revenue Growth
     *
     * @param \App\Jobs\Financials\Calculators\GrowthCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    protected function writeRevenueGrowth(GrowthCalculator $calculator, $year, $quarter)
    {
        $values1 = [];
        $values2 = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values1, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateRevenueGrowth($year, $quarter)->revenueGrowthQoQ
            ]);
            array_push($values2, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->revenueGrowthYoY
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tăng trưởng doanh thu thuần QoQ',
            'alias' => 'Revenue Growth QoQ',
            'group' => 'Chỉ số tăng trưởng',
            'unit' => '%',
            'description' => 'Tăng trưởng doanh thu thuần so với quý trước trong cùng năm tài chính. <strong style="color:#d2691e;">Tăng trưởng âm là dấu hiệu cảnh báo (doanh thu suy giảm).</strong>',
            'values' => $values1
        ]);
        array_push($this->content, [
            'name' => 'Tăng trưởng doanh thu thuần YoY',
            'alias' => 'Revenue Growth YoY',
            'group' => 'Chỉ số tăng trưởng',
            'unit' => '%',
            'description' => 'Tăng trưởng doanh thu thuần so với cùng kỳ năm tài chính trước. <strong style="color:#d2691e;">Tăng trưởng âm là dấu hiệu cảnh báo (doanh thu suy giảm).</strong>',
            'values' => $values2
        ]);
        return $this;
    }

    /**
     * Write Gross Profit Growth
     *
     * @param \App\Jobs\Financials\Calculators\GrowthCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    protected function writeGrossProfitGrowth(GrowthCalculator $calculator, $year, $quarter)
    {
        $values1 = [];
        $values2 = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values1, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateGrossProfitGrowth($year, $quarter)->grossProfitGrowthQoQ
            ]);
            array_push($values2, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->grossProfitGrowthYoY
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tăng trưởng lợi nhuận gộp QoQ',
            'alias' => 'Gross Profit Growth QoQ',
            'group' => 'Chỉ số tăng trưởng',
            'unit' => '%',
            'description' => 'Tăng trưởng lợi nhuận gộp so với quý trước trong cùng năm tài chính',
            'values' => $values1
        ]);
        array_push($this->content, [
            'name' => 'Tăng trưởng lợi nhuận gộp YoY',
            'alias' => 'Gross Profit Growth YoY',
            'group' => 'Chỉ số tăng trưởng',
            'unit' => '%',
            'description' => 'Tăng trưởng lợi nhuận gộp so với cùng kỳ năm tài chính trước. <strong style="color:#d2691e;">Tăng trưởng âm là dấu hiệu cảnh báo (biên lợi nhuận gộp hoặc doanh thu đang suy giảm).</strong>',
            'values' => $values2
        ]);
        return $this;
    }

    /**
    * Write Earning Before Tax (EBT) Growth
    *
    * @param \App\Jobs\Financials\Calculators\GrowthCalculator $calculator
    * @param  int $year
    * @param  int $quarter
    * @return $this
    */
    protected function writeEBTGrowth(GrowthCalculator $calculator, $year, $quarter)
    {
        $values1 = [];
        $values2 = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values1, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateEBTGrowth($year, $quarter)->eBTGrowthQoQ
            ]);
            array_push($values2, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->eBTGrowthYoY
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tăng trưởng lợi nhuận trước thuế QoQ',
            'alias' => 'Earnings Before Tax Growth QoQ',
            'group' => 'Chỉ số tăng trưởng',
            'unit' => '%',
            'description' => 'Tăng trưởng lợi nhuận trước thuế so với quý trước trong cùng năm tài chính',
            'values' => $values1
        ]);
        array_push($this->content, [
            'name' => 'Tăng trưởng lợi nhuận trước thuế YoY',
            'alias' => 'Earnings Before Tax Growth YoY',
            'group' => 'Chỉ số tăng trưởng',
            'unit' => '%',
            'description' => 'Tăng trưởng lợi nhuận trước thuế so với cùng kỳ năm tài chính trước. <strong style="color:#d2691e;">Tăng trưởng âm là dấu hiệu cảnh báo (lợi nhuận trước thuế đang suy giảm).</strong>',
            'values' => $values2
        ]);
        return $this;
    }

    /**
     * Write Net Profit Of Parent Shareholders Growth
     *
     * @param \App\Jobs\Financials\Calculators\GrowthCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    public function writeNetProfitOfParentShareHolderGrowth(GrowthCalculator $calculator, $year, $quarter)
    {
        $values1 = [];
        $values2 = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values1, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateNetProfitOfParentShareHolderGrowth($year, $quarter)->netProfitOfParentShareHolderGrowthQoQ
            ]);
            array_push($values2, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->netProfitOfParentShareHolderGrowthYoY
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tăng trưởng lợi nhuận sau thuế của cổ đông công ty mẹ QoQ',
            'alias' => 'Net Profit Of Parent ShareHolder Growth QoQ',
            'group' => 'Chỉ số tăng trưởng',
            'unit' => '%',
            'description' => 'Tăng trưởng lợi nhuận sau thuế của cổ đông công ty mẹ so với quý trước trong cùng năm tài chính',
            'values' => $values1
        ]);
        array_push($this->content, [
            'name' => 'Tăng trưởng lợi nhuận sau thuế của cổ đông công ty mẹ YoY',
            'alias' => 'Net Profit Of Parent ShareHolder Growth YoY',
            'group' => 'Chỉ số tăng trưởng',
            'unit' => '%',
            'description' => 'Tăng trưởng lợi nhuận sau thuế của cổ đông công ty mẹ so với cùng kỳ năm tài chính trước. <strong style="color:#d2691e;">Tăng trưởng âm là dấu hiệu cảnh báo (lợi nhuận đang suy giảm).</strong>',
            'values' => $values2
        ]);
        return $this;
    }

    /**
     * Write Total Asset Growth
     *
     * @param \App\Jobs\Financials\Calculators\GrowthCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    public function writeTotalAssetGrowth(GrowthCalculator $calculator, $year, $quarter)
    {
        $values1 = [];
        $values2 = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values1, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateTotalAssetGrowth($year, $quarter)->totalAssetGrowthQoQ
            ]);
            array_push($values2, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->totalAssetGrowthYoY
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tăng trưởng tổng tài sản QoQ',
            'alias' => 'Total Asset Growth QoQ',
            'group' => 'Chỉ số tăng trưởng',
            'unit' => '%',
            'description' => 'Tăng trưởng tổng tài sản so với quý trước trong cùng năm tài chính',
            'values' => $values1
        ]);
        array_push($this->content, [
            'name' => 'Tăng trưởng tổng tài sản YoY',
            'alias' => 'Total Asset Growth YoY',
            'group' => 'Chỉ số tăng trưởng',
            'unit' => '%',
            'description' => 'Tăng trưởng tổng tài sản so với cùng kỳ năm tài chính trước',
            'values' => $values2
        ]);
        return $this;
    }

    /**
     * Write Long Term Liability Growth
     *
     * @param \App\Jobs\Financials\Calculators\GrowthCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    public function writeLongTermLiabilityGrowth(GrowthCalculator $calculator, $year, $quarter)
    {
        $values1 = [];
        $values2 = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values1, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateLongTermLiabilityGrowth($year, $quarter)->longTermLiabilityGrowthQoQ
            ]);
            array_push($values2, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->longTermLiabilityGrowthYoY
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tăng trưởng nợ dài hạn QoQ',
            'alias' => 'Long Term Liability Growth QoQ',
            'group' => 'Chỉ số tăng trưởng',
            'unit' => '%',
            'description' => 'Tăng trưởng nợ dài hạn so với quý trước trong cùng năm tài chính',
            'values' => $values1
        ]);
        array_push($this->content, [
            'name' => 'Tăng trưởng nợ dài hạn YoY',
            'alias' => 'Long Term Liability Growth YoY',
            'group' => 'Chỉ số tăng trưởng',
            'unit' => '%',
            'description' => 'Tăng trưởng nợ dài hạn so với cùng kỳ năm tài chính trước',
            'values' => $values2
        ]);
        return $this;
    }

    /**
     * Write Liability Growth
     *
     * @param \App\Jobs\Financials\Calculators\GrowthCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    public function writeLiabilityGrowth(GrowthCalculator $calculator, $year, $quarter)
    {
        $values1 = [];
        $values2 = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values1, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateLiabilityGrowth($year, $quarter)->liabilityGrowthQoQ
            ]);
            array_push($values2, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->liabilityGrowthYoY
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tăng trưởng nợ phải trả QoQ',
            'alias' => 'Liability Growth QoQ',
            'group' => 'Chỉ số tăng trưởng',
            'unit' => '%',
            'description' => 'Tăng trưởng nợ phải trả so với quý trước trong cùng năm tài chính',
            'values' => $values1
        ]);
        array_push($this->content, [
            'name' => 'Tăng trưởng nợ phải trả YoY',
            'alias' => 'Liability Growth YoY',
            'group' => 'Chỉ số tăng trưởng',
            'unit' => '%',
            'description' => 'Tăng trưởng nợ phải trả so với cùng kỳ năm tài chính trước',
            'values' => $values2
        ]);
        return $this;
    }

    /**
     * Write Debt Growth
     *
     * @param \App\Jobs\Financials\Calculators\GrowthCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    public function writeDebtGrowth(GrowthCalculator $calculator, $year, $quarter)
    {
        $values1 = [];
        $values2 = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values1, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateDebtGrowth($year, $quarter)->debtGrowthQoQ
            ]);
            array_push($values2, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->debtGrowthYoY
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tăng trưởng nợ vay QoQ',
            'alias' => 'Debt Growth QoQ',
            'group' => 'Chỉ số tăng trưởng',
            'unit' => '%',
            'description' => 'Tăng trưởng nợ vay so với quý trước trong cùng năm tài chính',
            'values' => $values1
        ]);
        array_push($this->content, [
            'name' => 'Tăng trưởng nợ vay YoY',
            'alias' => 'Debt Growth YoY',
            'group' => 'Chỉ số tăng trưởng',
            'unit' => '%',
            'description' => 'Tăng trưởng nợ vay so với cùng kỳ năm tài chính trước',
            'values' => $values2
        ]);
        return $this;
    }

    /**
     * Write Equity Growth
     *
     * @param \App\Jobs\Financials\Calculators\GrowthCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    public function writeEquityGrowth(GrowthCalculator $calculator, $year, $quarter)
    {
        $values1 = [];
        $values2 = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values1, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateEquityGrowth($year, $quarter)->equityGrowthQoQ
            ]);
            array_push($values2, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->equityGrowthYoY
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tăng trưởng VCSH QoQ',
            'alias' => 'Equity Growth QoQ',
            'group' => 'Chỉ số tăng trưởng',
            'unit' => '%',
            'description' => 'Tăng trưởng VCSH so với quý trước trong cùng năm tài chính',
            'values' => $values1
        ]);
        array_push($this->content, [
            'name' => 'Tăng trưởng VCSH YoY',
            'alias' => 'Equity Growth YoY',
            'group' => 'Chỉ số tăng trưởng',
            'unit' => '%',
            'description' => 'Tăng trưởng VCSH so với cùng kỳ năm tài chính trước',
            'values' => $values2
        ]);
        return $this;
    }

    /**
     * Write Charter Capital Growth
     *
     * @param \App\Jobs\Financials\Calculators\GrowthCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    public function writeCharterCapitalGrowth(GrowthCalculator $calculator, $year, $quarter)
    {
        $values1 = [];
        $values2 = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values1, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateCharterCapitalGrowth($year, $quarter)->charterCapitalGrowthQoQ
            ]);
            array_push($values2, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->charterCapitalGrowthYoY
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tăng trưởng vốn điều lệ QoQ',
            'alias' => 'Charter Capital Growth QoQ',
            'group' => 'Chỉ số tăng trưởng',
            'unit' => '%',
            'description' => 'Tăng trưởng vốn điều lệ so với quý trước trong cùng năm tài chính',
            'values' => $values1
        ]);
        array_push($this->content, [
            'name' => 'Tăng trưởng vốn điều lệ YoY',
            'alias' => 'Charter Capital Growth YoY',
            'group' => 'Chỉ số tăng trưởng',
            'unit' => '%',
            'description' => 'Tăng trưởng vốn điều lệ so với cùng kỳ năm tài chính trước',
            'values' => $values2
        ]);
        return $this;
    }

    /**
     * Write Inventory Growth
     *
     * @param \App\Jobs\Financials\Calculators\GrowthCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    public function writeInventoryGrowth(GrowthCalculator $calculator, $year, $quarter)
    {
        $values1 = [];
        $values2 = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values1, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateInventoryGrowth($year, $quarter)->inventoryGrowthQoQ
            ]);
            array_push($values2, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->inventoryGrowthYoY
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tăng trưởng hàng tồn kho QoQ',
            'alias' => 'Inventory Growth QoQ',
            'group' => 'Chỉ số tăng trưởng',
            'unit' => '%',
            'description' => 'Tăng trưởng hàng tồn kho so với quý trước trong cùng năm tài chính. <strong style="color:#d2691e;">Theo Buffet thì ở các doanh nghiệp có lợi thế cạnh tranh bền vững tăng trưởng hàng tồn kho phải nhất quán với tăng trưởng doanh thu</strong>',
            'values' => $values1
        ]);
        array_push($this->content, [
            'name' => 'Tăng trưởng hàng tồn kho YoY',
            'alias' => 'Inventory Growth YoY',
            'group' => 'Chỉ số tăng trưởng',
            'unit' => '%',
            'description' => 'Tăng trưởng hàng tồn kho so với cùng kỳ năm tài chính trước. <strong style="color:#d2691e;">Theo Buffet thì ở các doanh nghiệp có lợi thế cạnh tranh bền vững tăng trưởng hàng tồn kho phải nhất quán với tăng trưởng doanh thu</strong>',
            'values' => $values2
        ]);
        return $this;
    }

    /**
     * Write FCF Growth
     *
     * @param \App\Jobs\Financials\Calculators\GrowthCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    public function writeFcfGrowth(GrowthCalculator $calculator, $year, $quarter)
    {
        $values1 = [];
        $values2 = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values1, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateFcfGrowth($year, $quarter)->fcfGrowthQoQ
            ]);
            array_push($values2, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->fcfGrowthYoY
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tăng trưởng dòng tiền tự do (FCF) QoQ',
            'alias' => 'FCF Growth QoQ',
            'group' => 'Chỉ số tăng trưởng',
            'unit' => '%',
            'description' => 'Tăng trưởng dòng tiền tự do so với quý trước trong cùng năm tài chính',
            'values' => $values1
        ]);
        array_push($this->content, [
            'name' => 'Tăng trưởng dòng tiền tự do (FCF) YoY',
            'alias' => 'FCF Growth YoY',
            'group' => 'Chỉ số tăng trưởng',
            'unit' => '%',
            'description' => 'Tăng trưởng dòng tiền tự do so với cùng kỳ năm tài chính trước',
            'values' => $values2
        ]);
        return $this;
    }

    /**
     * Write COGS Growth
     *
     * @param \App\Jobs\Financials\Calculators\GrowthCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    public function writeCogsGrowth(GrowthCalculator $calculator, $year, $quarter)
    {
        $values1 = [];
        $values2 = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values1, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateCogsGrowth($year, $quarter)->cogsGrowthQoQ
            ]);
            array_push($values2, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->cogsGrowthYoY
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tăng trưởng giá vốn bán hàng QoQ',
            'alias' => 'COGS Growth QoQ',
            'group' => 'Chỉ số tăng trưởng',
            'unit' => '%',
            'description' => 'Tăng trưởng giá vốn bán hàng so với quý trước trong cùng năm tài chính',
            'values' => $values1
        ]);
        array_push($this->content, [
            'name' => 'Tăng trưởng giá vốn bán hàng YoY',
            'alias' => 'COGS Growth YoY',
            'group' => 'Chỉ số tăng trưởng',
            'unit' => '%',
            'description' => 'Tăng trưởng giá vốn bán hàng so với cùng kỳ năm tài chính trước',
            'values' => $values2
        ]);
        return $this;
    }

    /**
     * Write Operation Expense Growth
     *
     * @param \App\Jobs\Financials\Calculators\GrowthCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    public function writeOperationExpenseGrowth(GrowthCalculator $calculator, $year, $quarter)
    {
        $values1 = [];
        $values2 = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values1, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateOperationExpenseGrowth($year, $quarter)->operationExpenseGrowthQoQ
            ]);
            array_push($values2, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->operationExpenseGrowthYoY
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tăng trưởng chi phí hoạt động QoQ',
            'alias' => 'Operation Expense Growth QoQ',
            'group' => 'Chỉ số tăng trưởng',
            'unit' => '%',
            'description' => 'Tăng trưởng chi phí hoạt động so với quý trước trong cùng năm tài chính',
            'values' => $values1
        ]);
        array_push($this->content, [
            'name' => 'Tăng trưởng chi phí hoạt động YoY',
            'alias' => 'Operation Expense Growth YoY',
            'group' => 'Chỉ số tăng trưởng',
            'unit' => '%',
            'description' => 'Tăng trưởng chi phí hoạt động so với cùng kỳ năm tài chính trước',
            'values' => $values2
        ]);
        return $this;
    }

    /**
     * Write Interest Expense Growth
     *
     * @param \App\Jobs\Financials\Calculators\GrowthCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    public function writeInterestExpenseGrowth(GrowthCalculator $calculator, $year, $quarter)
    {
        $values1 = [];
        $values2 = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values1, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateInterestExpenseGrowth($year, $quarter)->interestExpenseGrowthQoQ
            ]);
            array_push($values2, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->interestExpenseGrowthYoY
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tăng trưởng chi phí lãi vay QoQ',
            'alias' => 'Interest Expense Growth QoQ',
            'group' => 'Chỉ số tăng trưởng',
            'unit' => '%',
            'description' => 'Tăng trưởng chi phí lãi vay so với quý trước trong cùng năm tài chính',
            'values' => $values1
        ]);
        array_push($this->content, [
            'name' => 'Tăng trưởng chi phí lãi vay YoY',
            'alias' => 'Interest Expense Growth YoY',
            'group' => 'Chỉ số tăng trưởng',
            'unit' => '%',
            'description' => 'Tăng trưởng chi phí lãi vay so với cùng kỳ năm tài chính trước',
            'values' => $values2
        ]);
        return $this;
    }
}
