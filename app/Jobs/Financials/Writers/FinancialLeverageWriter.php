<?php
/**
 * FinancialLeverageWriter trait
 *
 * @author: tuanha
 * @date: 14-Aug-2022
 */
namespace App\Jobs\Financials\Writers;

use App\Jobs\Financials\Calculators\FinancialLeverageCalculator;

trait FinancialLeverageWriter
{
    /**
     * Write short-term on total liabilities ratio - Tỷ số nợ ngắn hạn trên tổng nợ phải trả
     *
     * @param \App\Jobs\Financials\Calculators\FinancialLeverageCalculator $this
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    public function writeShortTermToTotalLiabilitiesRatio(FinancialLeverageCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateShortTermToTotalLiabilitiesRatio($year, $quarter)->shortTermToTotalLiabilitiesRatio
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Nợ ngắn hạn/Tổng nợ phải trả',
            'alias' => 'Short-term liabilities/Total liabilities',
            'group' => 'Chỉ số đòn bẩy tài chính',
            'unit' => '%',
            'description' => 'Chỉ số này cho biết cấu trúc của Nợ ngắn hạn trong Tổng nợ phải trả. Một tỷ lệ nợ ngắn hạn cao thường là chỉ dấu cho thấy áp lực trả nợ lớn',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write total debt to total asset ratio - Tỷ số Nợ vay trên Tổng tài sản
     *
     * @param \App\Jobs\Financials\Calculators\FinancialLeverageCalculator $this
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    public function writeTotalDebtToTotalAssetRatio(FinancialLeverageCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateTotalDebtToTotalAssetRatio($year, $quarter)->totalDebtToTotalAssetRatio
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tổng nợ vay / Tổng tài sản',
            'alias' => 'Total Debts/Total Assets',
            'group' => 'Chỉ số đòn bẩy tài chính',
            'unit' => '%',
            'description' => 'Chỉ số này phản ánh bao nhiêu % tài sản của doanh nghiệp được tài trợ bởi nợ vay. <strong style="color:#d2691e;">Ngưỡng tham khảo: ≤ 30% đòn bẩy an toàn, 30–50% trung bình, &gt; 50% đòn bẩy cao.</strong> <strong style="color:#FF00FF;">Tổng nợ vay = Vay và nợ thuê tài chính ngắn hạn + Vay và nợ thuê tài chính dài hạn</strong>',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write total liability to total asset ratio - Chỉ số Tổng nợ / Tổng tài sản
     *
     * @param \App\Jobs\Financials\Calculators\FinancialLeverageCalculator $this
     * @param  int $year
     * @param  int $quarter
     * @return \App\Jobs\Financials\Calculators\FinancialLeverageCalculator $this
     */
    public function writeTotalLiabilityToTotalAssetRatio(FinancialLeverageCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateTotalLiabilityToTotalAssetRatio($year, $quarter)->totalLiabilityToTotalAssetRatio
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tổng nợ phải trả / Tổng tài sản',
            'alias' => 'Total Liabilities/Total Assets',
            'group' => 'Chỉ số đòn bẩy tài chính',
            'unit' => '%',
            'description' => 'Cho biết cấu trúc hình thành nguồn vốn của doanh nghiệp, cho biết phần trăm tổng tài sản được tài trợ bởi các chủ nợ thay vì các nhà đầu tư. <strong style="color:#d2691e;">Ngưỡng tham khảo: &lt; 70% đòn bẩy an toàn, 70–85% đòn bẩy cao, ≥ 85% đòn bẩy rất cao.</strong>',
            'values' => $values
        ]);
        return $this;
    }

    /**
    * Write total asset to equity ratio - Chỉ số Tổng tài sản / Vốn chủ sở hữu
    *
    * @param \App\Jobs\Financials\Calculators\FinancialLeverageCalculator $this
    * @param  int $year
    * @param  int $quarter
    * @return \App\Jobs\Financials\Calculators\FinancialLeverageCalculator $this
    */
    public function writeTotalAssetToEquityRatio(FinancialLeverageCalculator $calculator, $year, $quarter)
    {
        $values = [];
        $alert = null;
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            $calculator->calculateTotalAssetToEquityRatio($year, $quarter);
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->totalAssetToEquityRatio
            ]);
            if ($i === 1) {
                $alert = negativeEquityAlert($calculator->equityUsedForTotalAssetToEquity);
            }
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => ' Tổng tài sản / Vốn chủ sở hữu (Hệ số đòn bẩy tài chính)',
            'alias' => 'Total Assets/Equities',
            'group' => 'Chỉ số đòn bẩy tài chính',
            'unit' => 'scalar',
            'description' => 'Hệ số đòn bẩy tài chính cho biết tài sản của công ty được tài trợ chính bởi vốn chủ sở hữu của các cổ đông hay là nguồn nợ bên ngoài. <strong style="color:#d2691e;">Ngưỡng tham khảo: ≤ 2 lần đòn bẩy vừa phải, 2–3 lần trung bình, &gt; 3 lần đòn bẩy cao.</strong> <strong style="color:#FF00FF;">Hệ số đòn bẩy tài chính = 1 + (Tổng nợ phải trả/VCSH)</strong>',
            'values' => $values,
            'alert' => $alert
        ]);
        return $this;
    }

    /**
    * Write average total asset to average equity ratio - Chỉ số Tổng tài sản bình quân / Vốn chủ sở hữu bình quân
    *
    * @param \App\Jobs\Financials\Calculators\FinancialLeverageCalculator $this
    * @param  int $year
    * @param  int $quarter
    * @return \App\Jobs\Financials\Calculators\FinancialLeverageCalculator $this
    */
    public function writeAverageTotalAssetToAverageEquityRatio(FinancialLeverageCalculator $calculator, $year, $quarter)
    {
        $values = [];
        $alert = null;
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            $calculator->calculateAverageTotalAssetToAverageEquityRatio($year, $quarter);
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->averageTotalAssetToAverageEquityRatio
            ]);
            if ($i === 1) {
                $alert = negativeEquityAlert($calculator->averageEquityUsedForAverageTotalAssetToAverageEquity);
            }
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => ' Tổng tài sản bình quân / Vốn chủ sở hữu bình quân (Hệ số đòn bẩy tài chính trung bình - phiên bản chặt chẽ hơn của đòn bẩy tài chính)',
            'alias' => 'Average Total Assets/Average Equities',
            'group' => 'Chỉ số đòn bẩy tài chính',
            'unit' => 'scalar',
            'description' => '<strong style="color:#d2691e;">Ngưỡng tham khảo (như Hệ số đòn bẩy tài chính): ≤ 2 lần đòn bẩy vừa phải, 2–3 lần trung bình, &gt; 3 lần đòn bẩy cao.</strong> <strong style="color:#FF00FF;">Hệ số đòn bẩy tài chính trung bình = 1 + (Tổng nợ phải trả bình quân / VCSH bình quân)</strong>',
            'values' => $values,
            'alert' => $alert
        ]);
        return $this;
    }

    /**
     * Write total debts to total liabilities - Chỉ số tổng nợ vay / tổng nợ
     *
     * @param \App\Jobs\Financials\Calculators\FinancialLeverageCalculator $this
     * @param  int $year
     * @param  int $quarter
     * @return \App\Jobs\Financials\Calculators\FinancialLeverageCalculator $this
     */
    public function writeTotalDebtToTotalLiabilityRatio(FinancialLeverageCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateTotalDebtToTotalLiabilityRatio($year, $quarter)->totalDebtToTotalLiabilityRatio
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tổng nợ vay / tổng nợ phải trả',
            'alias' => 'Total Debts/Total Liabilities',
            'group' => 'Chỉ số đòn bẩy tài chính',
            'unit' => '%',
            'description' => 'Chỉ số này cho biết tỉ lệ nợ vay trong tổng nợ của doanh nghiệp, <strong style="color:#FF00FF;">Tổng nợ vay = Vay và nợ thuê tài chính ngắn hạn + Vay và nợ thuê tài chính dài hạn</strong>',
            'values' => $values
        ]);
        return $this;
    }

    /**
    * Write current debts to total debts - Chỉ số nợ vay ngắn hạn / tổng nợ vay
    *
    * @param \App\Jobs\Financials\Calculators\FinancialLeverageCalculator $this
    * @param  int $year
    * @param  int $quarter
    * @return \App\Jobs\Financials\Calculators\FinancialLeverageCalculator $this
    */
    public function writeCurrentDebtToTotalDebtRatio(FinancialLeverageCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateCurrentDebtToTotalDebtRatio($year, $quarter)->currentDebtToTotalDebtRatio
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Nợ vay ngắn hạn / Tổng nợ vay',
            'alias' => 'Currrent Debts/Total Debts',
            'group' => 'Chỉ số đòn bẩy tài chính',
            'unit' => '%',
            'description' => 'Chỉ số này cho biết tỉ lệ nợ vay ngắn hạn trong tổng nợ vay của doanh nghiệp, <strong style="color:#FF00FF;">Công thức tính = 100% * Vay và nợ thuê tài chính ngắn hạn / (Vay và nợ thuê tài chính ngắn hạn + Vay và nợ thuê tài chính dài hạn)</strong>',
            'values' => $values
        ]);
        return $this;
    }

    /**
    * Write Debts to Equities Ratio - Chỉ số nợ vay / VCSH
    *
    * @param \App\Jobs\Financials\Calculators\FinancialLeverageCalculator $this
    * @param  int $year
    * @param  int $quarter
    * @return \App\Jobs\Financials\Calculators\FinancialLeverageCalculator $this
    */
    public function writeDebtToEquityRatio(FinancialLeverageCalculator $calculator, $year, $quarter)
    {
        $values = [];
        $alert = null;
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            $calculator->calculateDebtToEquityRatio($year, $quarter);
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->debtToEquityRatio
            ]);
            if ($i === 1) {
                $alert = negativeEquityAlert($calculator->equityUsedForDebtToEquity);
            }
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tổng nợ vay / Vốn chủ sở hữu (Hệ số nợ vay)',
            'alias' => 'Debts/Equities',
            'group' => 'Chỉ số đòn bẩy tài chính',
            'unit' => 'scalar',
            'description' => 'Không phải mọi khoản nợ đều rủi ro như nhau, hệ số này tập trung đánh giá mức độ đòn bẩy tài chính dựa vào nợ vay (nợ phải trả chi phí lãi vay). Hệ số này càng lớn thì rủi ro càng cao. <strong style="color:#d2691e;">Ngưỡng tham khảo: ≤ 1 lần đòn bẩy an toàn, 1–2 lần đòn bẩy cao, &gt; 2 lần đòn bẩy rất cao; VCSH âm là bất thường (mất vốn).</strong> <strong style="color:#FF00FF;">Công thức tính = (Vay và nợ thuê tài chính ngắn hạn + Vay và nợ thuê tài chính dài hạn) / Vốn chủ sở hữu</strong>',
            'values' => $values,
            'alert' => $alert
        ]);
        return $this;
    }

    /**
    * Write Net Debts to Equities Ratio - Chỉ số nợ vay rong / VCSH
    *
    * @param \App\Jobs\Financials\Calculators\FinancialLeverageCalculator $this
    * @param  int $year
    * @param  int $quarter
    * @return \App\Jobs\Financials\Calculators\FinancialLeverageCalculator $this
    */
    public function writeNetDebtToEquityRatio(FinancialLeverageCalculator $calculator, $year, $quarter)
    {
        $values = [];
        $alert = null;
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            $calculator->calculateNetDebtToEquityRatio($year, $quarter);
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->netDebtToEquityRatio
            ]);
            if ($i === 1) {
                $alert = negativeEquityAlert($calculator->equityUsedForNetDebtToEquity);
            }
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Nợ vay ròng / Vốn chủ sở hữu (Hệ số nợ vay ròng)',
            'alias' => 'Net Debts/Equities',
            'group' => 'Chỉ số đòn bẩy tài chính',
            'unit' => 'scalar',
            'description' => 'Tập trung đánh giá mức độ đòn bẩy tài chính dựa vào nợ vay ròng. Hệ số này càng lớn thì rủi ro càng cao. <strong style="color:#d2691e;">Ngưỡng tham khảo: âm là tiền ròng dương (net cash, tốt), 1–2 lần đòn bẩy cao, &gt; 2 lần đòn bẩy rất cao.</strong> <strong style="color:#FF00FF;">Công thức tính = (Vay và nợ thuê tài chính ngắn hạn + Vay và nợ thuê tài chính dài hạn - Các khoản đầu tư tài chính ngắn hạn - Các khoản đầu tư tài chính dài hạn) / Vốn chủ sở hữu</strong>',
            'values' => $values,
            'alert' => $alert
        ]);
        return $this;
    }

    /**
    * Write Long Term Debts to Equities Ratio - Chỉ số nợ vay dài hạn / VCSH
    *
    * @param \App\Jobs\Financials\Calculators\FinancialLeverageCalculator $this
    * @param  int $year
    * @param  int $quarter
    * @return \App\Jobs\Financials\Calculators\FinancialLeverageCalculator $this
    */
    public function writeLongTermDebtToEquityRatio(FinancialLeverageCalculator $calculator, $year, $quarter)
    {
        $values = [];
        $alert = null;
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            $calculator->calculateLongTermDebtToEquityRatio($year, $quarter);
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->longTermDebtToEquityRatio
            ]);
            if ($i === 1) {
                $alert = negativeEquityAlert($calculator->equityUsedForLongTermDebtToEquity);
            }
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Nợ vay dài hạn / Vốn chủ sở hữu (Hệ số nợ vay dài hạn)',
            'alias' => 'Long Term Debts/Equities',
            'group' => 'Chỉ số đòn bẩy tài chính',
            'unit' => 'scalar',
            'description' => 'Đánh giá mức độ đòn bẩy tài chính của doanh nghiệp theo nguồn nợ vay dài hạn (nợ dài hạn phải trả chi phí lãi vay). Nợ vay dài hạn chứa đựng nhiều rủi ro hơn nợ vay ngắn hạn do nhạy cảm với sự thay đổi của lãi suất và những biến động kinh tế vĩ mô cũng như triển vọng kinh doanh dài hạn của doan nghiệp. <strong style="color:#d2691e;">Ngưỡng tham khảo: ≤ 0.5 lần thận trọng, 0.5–1 lần trung bình, &gt; 1 lần là mức vay dài hạn cao so với vốn chủ sở hữu.</strong> <strong style="color:#FF00FF;">Công thức tính = Vay và nợ thuê tài chính dài hạn / Vốn chủ sở hữu</strong>',
            'values' => $values,
            'alert' => $alert
        ]);
        return $this;
    }

    /**
    * Write Long Term Debts to Long Term Liabilities Ratio - Chỉ số nợ vay dài hạn / nợ dài hạn
    *
    * @param \App\Jobs\Financials\Calculators\FinancialLeverageCalculator $this
    * @param  int $year
    * @param  int $quarter
    * @return \App\Jobs\Financials\Calculators\FinancialLeverageCalculator $this
    */
    public function writeLongTermDebtToLongTermLiabilityRatio(FinancialLeverageCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateLongTermDebtToLongTermLiabilityRatio($year, $quarter)->longTermDebtToLongTermLiabilityRatio
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Nợ vay dài hạn / Nợ dài hạn',
            'alias' => 'Long Term Debts/Long Term Liabilities',
            'group' => 'Chỉ số đòn bẩy tài chính',
            'unit' => '%',
            'description' => 'Đo lường tỉ trọng nợ vay dài hạn trong tổng nợ phải trả dài hạn của doanh nghiệp. <strong style="color:#FF00FF;">Công thức tính = Vay và nợ thuê tài chính dài hạn / Nợ dài hạn</strong>',
            'values' => $values
        ]);
        return $this;
    }

    /**
    * Write Current Debts to Current Liabilities Ratio - Chỉ số nợ vay ngắn hạn / nợ ngắn hạn
    *
    * @param \App\Jobs\Financials\Calculators\FinancialLeverageCalculator $this
    * @param  int $year
    * @param  int $quarter
    * @return \App\Jobs\Financials\Calculators\FinancialLeverageCalculator $this
    */
    public function writeCurrentDebtToCurrentLiabilityRatio(FinancialLeverageCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateCurrentDebtToCurrentLiabilityRatio($year, $quarter)->currentDebtToCurrentLiabilityRatio
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Nợ vay ngắn hạn / Nợ ngắn hạn',
            'alias' => 'Current Debts/Current Liabilities',
            'group' => 'Chỉ số đòn bẩy tài chính',
            'unit' => '%',
            'description' => 'Đo lường tỉ trọng nợ vay ngắn hạn trong tổng nợ phải trả ngắn hạn của doanh nghiệp. <strong style="color:#FF00FF;">Công thức tính = Vay và nợ thuê tài chính ngắn hạn / Nợ ngắn hạn</strong>',
            'values' => $values
        ]);
        return $this;
    }

    /**
    * Write Interest Expense to Average Debt Ratio - Chỉ số chi phí lãi vay / Nợ vay bình quân
    *
    * @param \App\Jobs\Financials\Calculators\FinancialLeverageCalculator $this
    * @param  int $year
    * @param  int $quarter
    * @return \App\Jobs\Financials\Calculators\FinancialLeverageCalculator $this
    */
    public function writeInterestExpenseToAverageDebtRatio(FinancialLeverageCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            $calculator->calculateInterestExpenseToAverageDebtRatio($year, $quarter);
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->interestExpenseToAverageDebtRatio,
                'valueNote' => quarterOnlyNote($calculator->interestExpenseToAverageDebtRatioQuarterOnly, '%', $quarter),
                'ttm' => $quarter != 0
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Chi phí lãi vay / Nợ vay bình quân',
            'alias' => 'Interest Expenses/Average Debts',
            'group' => 'Chỉ số đòn bẩy tài chính',
            'unit' => '%',
            'description' => 'Đo lường xem doanh nghiệp phải trả bao nhiêu đồng chi phí lãi vay cho một đồng vay nợ, hệ số này phản ánh mức độ tương đối lãi suất đi vay của doanh nghiệp. Với báo cáo quý, chi phí lãi vay được quy đổi năm (TTM); xem tooltip để biết số liệu riêng quý. <strong style="color:#FF00FF;">Công thức tính = Chi phí lãi vay (TTM) / (Vay và nợ thuê tài chính ngắn hạn bình quân + Vay và nợ thuê tài chính dài hạn bình quân)</strong>',
            'values' => $values
        ]);
        return $this;
    }
}
