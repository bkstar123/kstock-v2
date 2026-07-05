<?php
/**
 * CostStructureWriter trait
 *
 * @author: tuanha
 * @date: 14-Aug-2022
 */
namespace App\Jobs\Financials\Writers;

use App\Jobs\Financials\Calculators\CostStructureCalculator;

trait CostStructureWriter
{
    /**
     * Write CFO/CAPEX Ratio
     *
     * @param \App\Jobs\Financials\Calculators\CostStructureCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    protected function writeCOGSToRevenueRatio(CostStructureCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateCOGSToRevenueRatio($year, $quarter)->cOGSToRevenueRatio
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Giá vốn bán hàng / Doanh thu thuần',
            'alias' => 'Cogs/Revenue',
            'group' => 'Chỉ số Cơ cấu chi phí',
            'unit' => '%',
            'description' => 'Chỉ số này cho biết giá vốn bán hàng chiếm tỉ trọng bao nhiêu trong doanh thu thuần (= 100% − biên lợi nhuận gộp). <strong style="color:#d2691e;">Ngưỡng tham khảo (theo Buffett trên biên lợi nhuận gộp): ≤ 60% (biên gộp ≥ 40%) là tốt, ≥ 80% (biên gộp &lt; 20%) là dấu hiệu cạnh tranh gay gắt.</strong>',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write Selling Expense / Revenue Ratio
     *
     * @param \App\Jobs\Financials\Calculators\CostStructureCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    public function writeSellingExpenseToRevenueRatio(CostStructureCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateSellingExpenseToRevenueRatio($year, $quarter)->sellingExpenseToRevenueRatio
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Chi phí bán hàng / Doanh thu thuần',
            'alias' => 'Selling Expense/Revenue',
            'group' => 'Chỉ số Cơ cấu chi phí',
            'unit' => '%',
            'description' => 'Chỉ số này cho biết chi phí bán hàng chiếm tỉ trọng bao nhiêu trong doanh thu thuần',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write Administration Expense / Revenue Ratio
     *
     * @param \App\Jobs\Financials\Calculators\CostStructureCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    public function writeAdministrationExpenseToRevenueRatio(CostStructureCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateAdministrationExpenseToRevenueRatio($year, $quarter)->administrationExpenseToRevenueRatio
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Chi phí quản lý doanh nghiệp / doanh thu thuần',
            'alias' => 'Administration Expense/Revenue',
            'group' => 'Chỉ số Cơ cấu chi phí',
            'unit' => '%',
            'description' => 'Chỉ số này cho biết chi phí quản lý doanh nghiệp chiếm tỉ trọng bao nhiêu trong doanh thu thuần',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write Interest Cost / Revenue Ratio
     *
     * @param \App\Jobs\Financials\Calculators\CostStructureCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    public function writeInterestCostToRevenueRatio(CostStructureCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateInterestCostToRevenueRatio($year, $quarter)->interestCostToRevenueRatio
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Chi phí lãi vay / doanh thu thuần',
            'alias' => 'Interest cost/Revenue',
            'group' => 'Chỉ số Cơ cấu chi phí',
            'unit' => '%',
            'description' => 'Chỉ số này cho biết chi phí lãi vay chiếm tỉ trọng bao nhiêu trong doanh thu thuần — gánh nặng lãi vay so với quy mô kinh doanh. <strong style="color:#d2691e;">Ngưỡng tham khảo: ≤ 3% gánh nặng lãi vay thấp, &gt; 10% gánh nặng lãi vay cao.</strong>',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write Selling & Enterprise Management Expenses To Gross Profit Ratio
     *
     * @param \App\Jobs\Financials\Calculators\CostStructureCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    public function writeSellingAndEnperpriseManagementToGrossProfitRatio(CostStructureCalculator $calculator, $year, $quarter)
    {
        $values = [];
        $alert = null;
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            $calculator->calculateSellingAndEnperpriseManagementToGrossProfitRatio($year, $quarter);
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->sellingAndEnperpriseManagementToGrossProfitRatio
            ]);
            if ($i === 1 && $calculator->grossProfitUsedForSellingAndEnperpriseManagementToGrossProfit !== null && $calculator->grossProfitUsedForSellingAndEnperpriseManagementToGrossProfit < 0) {
                $alert = 'Lợi nhuận gộp kỳ này đang <strong>âm</strong> (bán dưới giá vốn) — đây là kịch bản xấu '
                       . 'nhất mà chỉ số này muốn cảnh báo, nhưng tỷ lệ % có thể trông thấp/âm và bị đọc nhầm '
                       . 'thành "tốt", cần xem trực tiếp giá trị lợi nhuận gộp.';
            }
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Chi phí bán hàng & Chi phí QLDN / Lợi nhuận gộp',
            'alias' => 'Selling and Enterprise Management Expenses/Gross Profit',
            'group' => 'Chỉ số Cơ cấu chi phí',
            'unit' => '%',
            'description' => 'Chỉ số này cho biết chi phí bán hàng và quản lý doanh nghiệp chiếm tỷ trọng bao nhiêu trong lợi nhuận gộp. <strong style="color:#d2691e;">Theo Buffet nếu chỉ số này dưới 30% thì là tuyệt vời, một số công ty có lợi thế cạnh tranh bền vững cũng có thể có chỉ số này nằm trong khoảng 30%-80%. Nhưng nếu doanh nghiệp duy trì chi phí này ở mức gần 100% hoặc cao hơn trong nhiều năm thì đó có thể là một doanh nghiệp trong ngành có sự cạnh tranh gay gắt. Kể cả khi hệ số này thấp thì cũng cần đánh giá thêm khả năng kinh tế dài hạn của doanh nghiệp dựa trên chi phí R&D, chi phí đầu tư TSCĐ (CAPEX) và chi phí lãi vay</strong>',
            'values' => $values,
            'alert' => $alert
        ]);
        return $this;
    }
}
