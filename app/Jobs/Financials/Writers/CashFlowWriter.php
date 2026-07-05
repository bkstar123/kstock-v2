<?php
/**
 * CashFlowWriter trait
 *
 * @author: tuanha
 * @date: 14-Aug-2022
 */
namespace App\Jobs\Financials\Writers;

use App\Jobs\Financials\Calculators\CashFlowCalculator;

trait CashFlowWriter
{
    /**
     * Write Liability Coverage Ratio By CFO - He so kha nang thanh toan no cua dong tien kinh doanh
     *
     * @param \App\Jobs\Financials\Calculators\CashFlowCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    protected function writeLiabilityCoverageRatioByCFO(CashFlowCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            $calculator->calculateLiabilityCoverageRatioByCFO($year, $quarter);
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->liabilityCoverageRatioByCFO,
                'valueNote' => quarterOnlyNote($calculator->liabilityCoverageRatioByCFOQuarterOnly, 'scalar', $quarter),
                'ttm' => $quarter != 0
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Hệ số thanh toán nợ bằng dòng tiền hoạt động kinh doanh',
            'alias' => 'Liability Coverage Ratio By CFO',
            'group' => 'Chỉ số dòng tiền',
            'unit' => 'scalar',
            'description' => 'Hệ số này cho biết với dòng tiền thuần từ  HĐKD trong kỳ, DN có đảm bảo khả năng đáp ứng được nợ phải trả hay không. Nói cách khác, một đồng nợ phải trả bình quân trong kỳ được đảm bảo bởi mấy đồng tiền và tương đương tiền lưu chuyển thuần từ HĐKD. Khi trị số của chỉ tiêu này lớn hơn hoặc bằng một(>= 1), dòng tiền lưu chuyển thuần từ HĐKD trong kỳ bảo đảm đáp ứng đủ và thừa khả năng thanh toán nợ phải trả trong kỳ và ngược lại. <strong style="color:#d2691e;">Ngưỡng tham khảo: CFO âm (&lt; 0) là xấu, 0 đến &lt; 1 là chưa đủ trang trải nợ, ≥ 1 là đủ khả năng thanh toán.</strong> Với báo cáo quý, CFO được quy đổi năm (TTM); xem tooltip để biết số liệu riêng quý. <strong style="color:#FF00FF;">Công thức tính = Lưu chuyển tiền thuần từ HĐKD (CFO, TTM) / Tổng nợ bình quân </strong>',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write Current Liabilities Coverage Ratio By CFO - He so kha nang thanh toan no ngan han cua dong tien kinh doanh
     *
     * @param \App\Jobs\Financials\Calculators\CashFlowCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    protected function writeCurrentLiabilityCoverageRatioByCFO(CashFlowCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            $calculator->calculateCurrentLiabilityCoverageRatioByCFO($year, $quarter);
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->currentLiabilityCoverageRatioByCFO,
                'valueNote' => quarterOnlyNote($calculator->currentLiabilityCoverageRatioByCFOQuarterOnly, 'scalar', $quarter),
                'ttm' => $quarter != 0
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Hệ số thanh toán nợ ngắn hạn bằng dòng tiền hoạt động kinh doanh',
            'alias' => 'Current Liability Coverage Ratio By CFO',
            'group' => 'Chỉ số dòng tiền',
            'unit' => 'scalar',
            'description' => 'Hệ số này cho biết dòng tiền lưu chuyển thuần từ hoạt động kinh doanh (HĐKD) trong kỳ có đảm bảo trang trải được các khoản nợ ngắn hạn (Kể cả nợ dài hạn đến hạn trả) hay không. Do dòng tiền lưu chuyển thuần tạo ra từ HĐKD là dòng tiền an ninh tài chính của doanh nghiệp nên khi chỉ số này lớn hơn hoặc bằng một (>=1), Doanh nghiệp có đủ khả năng thanh toán nợ ngắn hạn bằng dòng tiền hoạt động kinh doanh và ngược lại, dòng tiền HĐKD tạo ra không đủ để trả nợ ngắn hạn. <strong style="color:#d2691e;">Ngưỡng tham khảo: CFO âm (&lt; 0) là xấu, 0 đến &lt; 1 là chưa đủ trang trải nợ, ≥ 1 là đủ khả năng thanh toán.</strong> Với báo cáo quý, CFO được quy đổi năm (TTM); xem tooltip để biết số liệu riêng quý. <strong style="color:#FF00FF;">Công thức tính = Lưu chuyển tiền thuần từ HĐKD (CFO, TTM) / Tổng nợ ngắn hạn bình quân </strong>',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write Long-term Liability Coverage Ratio By CFO - He so kha nang thanh toan no dai han cua dong tien kinh doanh
     *
     * @param \App\Jobs\Financials\Calculators\CashFlowCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    protected function writeLongTermLiabilityCoverageRatioByCFO(CashFlowCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            $calculator->calculateLongTermLiabilityCoverageRatioByCFO($year, $quarter);
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->longTermLiabilityCoverageRatioByCFO,
                'valueNote' => quarterOnlyNote($calculator->longTermLiabilityCoverageRatioByCFOQuarterOnly, 'scalar', $quarter),
                'ttm' => $quarter != 0
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Hệ số thanh toán nợ dài hạn bằng dòng tiền hoạt động kinh doanh',
            'alias' => 'Long-term Liability Coverage Ratio By CFO',
            'group' => 'Chỉ số dòng tiền',
            'unit' => 'scalar',
            'description' => 'Hệ số khả năng thanh toán nợ dài hạn của dòng tiền kinh doanh cho biết mức độ bảo đảm nợ dài hạn bằng dòng tiền lưu chuyển thuần từ HĐKD. Hay nói cách khác, cứ một đồng nợ dài hạn bình quân trong kỳ phải trả của DN được đảm bảo bởi mấy đồng tiền lưu chuyển thuần từ HĐKD. Khi trị số của chỉ tiêu lớn hơn hoặc bằng 1 (>=1), DN bảo đảm đủ và thừa khả năng thanh toán nợ dài hạn bằng dòng tiền HĐKD và ngược lại. <strong style="color:#d2691e;">Ngưỡng tham khảo: CFO âm (&lt; 0) là xấu, 0 đến &lt; 1 là chưa đủ trang trải nợ, ≥ 1 là đủ khả năng thanh toán.</strong> Với báo cáo quý, CFO được quy đổi năm (TTM); xem tooltip để biết số liệu riêng quý. <strong style="color:#FF00FF;">Công thức tính = Lưu chuyển tiền thuần từ HĐKD (CFO, TTM) / Tổng nợ dài hạn bình quân </strong>',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write CFO/Revenue
     *
     * @param \App\Jobs\Financials\Calculators\CashFlowCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    protected function writeCFOToRevenue(CashFlowCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateCFOToRevenue($year, $quarter)->cFOToRevenue
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'CFO/Doanh thu thuần',
            'alias' => 'CFO/Revenue',
            'group' => 'Chỉ số dòng tiền',
            'unit' => '%',
            'description' => 'Chỉ số này cho biết tỉ lệ chuyển đổi từ một đồng doanh thu thuần sang dòng tiền hoạt động kinh doanh (CFO). <strong style="color:#d2691e;">Ngưỡng tham khảo: âm (&lt; 0) là dòng tiền HĐKD âm — dấu hiệu xấu dù có lãi kế toán; dương là dòng tiền HĐKD lành mạnh.</strong>',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write FCF/Revenue
     *
     * @param \App\Jobs\Financials\Calculators\CashFlowCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    protected function writeFCFToRevenue(CashFlowCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateFCFToRevenue($year, $quarter)->fCFToRevenue
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'FCF/Doanh thu thuần',
            'alias' => 'FCF/Revenue',
            'group' => 'Chỉ số dòng tiền',
            'unit' => '%',
            'description' => 'Chỉ số này cho biết tỉ lệ chuyển đổi từ một đồng doanh thu thuần sang dòng tiền tự do (FCF). Bất kỳ một doanh nghiệp nào có thể duy trì tỷ lệ FCF/Doanh thu thuần trên 10% trong nhiều năm đều là 1 cỗ máy tạo ra tiền. <strong style="color:#d2691e;">Ngưỡng tham khảo: âm (&lt; 0) là dòng tiền tự do âm (cảnh báo), ≥ 10% ổn định nhiều năm là cỗ máy tạo tiền.</strong> <strong style="color:#FF00FF;">Dòng tiền tự do (FCF) = CFO - CAPEX </strong>',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write FCF/CFO
     *
     * @param \App\Jobs\Financials\Calculators\CashFlowCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    protected function writeFCFToCFO(CashFlowCalculator $calculator, $year, $quarter)
    {
        $values = [];
        $alert = null;
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            $calculator->calculateFCFToCFO($year, $quarter);
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->fCFToCFO
            ]);
            if ($i === 1) {
                $alert = negativeCfoAlert($calculator->cfoUsedForFCFToCFO);
            }
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'FCF/CFO',
            'alias' => 'FCF/CFO',
            'group' => 'Chỉ số dòng tiền',
            'unit' => '%',
            'description' => 'Chỉ số này cho biết tỉ lệ chuyển đổi dòng tiền thuần từ hoạt động kinh doanh sang dòng tiền tự do, từ đó phản ánh chất lượng dòng tiền của doanh nghiệp. <strong style="color:#d2691e;">Ngưỡng tham khảo: âm (&lt; 0) là dòng tiền tự do âm (cảnh báo).</strong>',
            'values' => $values,
            'alert' => $alert
        ]);
        return $this;
    }

    /**
     * Write Liability Coverage Ratio By FCF - He so kha nang thanh toan no cua dong tien tu do
     *
     * @param \App\Jobs\Financials\Calculators\CashFlowCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    protected function writeLiabilityCoverageRatioByFCF(CashFlowCalculator $calculator, $year, $quarter)
    {
        $values = [];
        $alert = null;
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            $calculator->calculateLiabilityCoverageRatioByFCF($year, $quarter);
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->liabilityCoverageRatioByFCF,
                'valueNote' => quarterOnlyNote($calculator->liabilityCoverageRatioByFCFQuarterOnly, 'scalar', $quarter),
                'ttm' => $quarter != 0
            ]);
            if ($i === 1) {
                $alert = negativeCfoAlert($calculator->cfoUsedForFCF);
            }
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Hệ số thanh toán nợ bằng dòng tiền tự do',
            'alias' => 'Liability Coverage Ratio By FCF',
            'group' => 'Chỉ số dòng tiền',
            'unit' => 'scalar',
            'description' => 'Hệ số khả năng thanh toán nợ của dòng tiền tự do cho biết với dòng tiền tự do trong kỳ, DN có đảm bảo khả năng đáp ứng được nợ phải trả hay không. Nói cách khác, một đồng nợ phải trả bình quân trong kỳ được đảm bảo bởi mấy đồng tiền tự do. Khi trị số của chỉ tiêu này lớn hơn hoặc bằng một(>= 1), dòng tiền tự do trong kỳ bảo đảm đáp ứng đủ và thừa khả năng thanh toán nợ phải trả trong kỳ và ngược lại. <strong style="color:#d2691e;">Ngưỡng tham khảo: FCF âm (&lt; 0) là xấu, 0 đến &lt; 1 là chưa đủ trang trải nợ, ≥ 1 là đủ khả năng thanh toán.</strong> Với báo cáo quý, FCF được quy đổi năm (TTM); xem tooltip để biết số liệu riêng quý. <strong style="color:#FF00FF;">Công thức tính = Dòng tiền tự do (FCF, TTM) / Tổng nợ bình quân </strong>',
            'values' => $values,
            'alert' => $alert
        ]);
        return $this;
    }

    /**
     * Write Current Liabilities Coverage Ratio By FCF - He so kha nang thanh toan no ngan han cua dong tien tu do
     *
     * @param \App\Jobs\Financials\Calculators\CashFlowCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    protected function writeCurrentLiabilityCoverageRatioByFCF(CashFlowCalculator $calculator, $year, $quarter)
    {
        $values = [];
        $alert = null;
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            $calculator->calculateCurrentLiabilityCoverageRatioByFCF($year, $quarter);
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->currentLiabilityCoverageRatioByFCF,
                'valueNote' => quarterOnlyNote($calculator->currentLiabilityCoverageRatioByFCFQuarterOnly, 'scalar', $quarter),
                'ttm' => $quarter != 0
            ]);
            if ($i === 1) {
                $alert = negativeCfoAlert($calculator->cfoUsedForFCF);
            }
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Hệ số thanh toán nợ ngắn hạn bằng dòng tiền tự do',
            'alias' => 'Current Liability Coverage Ratio By FCF',
            'group' => 'Chỉ số dòng tiền',
            'unit' => 'scalar',
            'description' => 'Hệ số khả năng thanh toán nợ ngắn hạn của dòng tiền tự do cho biết dòng tiền tự do trong kỳ có đảm bảo trang trải được các khoản nợ ngắn hạn (Kể cả nợ dài hạn đến hạn trả) hay không, khi chỉ số này lớn hơn hoặc bằng một (>=1), Doanh nghiệp có đủ dòng tiền tự do để thanh toán nợ ngắn hạn và ngược lại, dòng tiền tư do tạo ra không đủ để trả nợ ngắn hạn. <strong style="color:#d2691e;">Ngưỡng tham khảo: FCF âm (&lt; 0) là xấu, 0 đến &lt; 1 là chưa đủ trang trải nợ, ≥ 1 là đủ khả năng thanh toán.</strong> Với báo cáo quý, FCF được quy đổi năm (TTM); xem tooltip để biết số liệu riêng quý. <strong style="color:#FF00FF;">Công thức tính = Dòng tiền tự do (FCF, TTM) /Tổng nợ ngắn hạn bình quân </strong>',
            'values' => $values,
            'alert' => $alert
        ]);
        return $this;
    }

    /**
     * Write Long-term Liability Coverage Ratio By FCF - He so kha nang thanh toan no dai han cua dong tien tu do
     *
     * @param \App\Jobs\Financials\Calculators\CashFlowCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    protected function writeLongTermLiabilityCoverageRatioByFCF(CashFlowCalculator $calculator, $year, $quarter)
    {
        $values = [];
        $alert = null;
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            $calculator->calculateLongTermLiabilityCoverageRatioByFCF($year, $quarter);
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->longTermLiabilityCoverageRatioByFCF,
                'valueNote' => quarterOnlyNote($calculator->longTermLiabilityCoverageRatioByFCFQuarterOnly, 'scalar', $quarter),
                'ttm' => $quarter != 0
            ]);
            if ($i === 1) {
                $alert = negativeCfoAlert($calculator->cfoUsedForFCF);
            }
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Hệ số thanh toán nợ dài hạn bằng dòng tiền tự do',
            'alias' => 'Long-term Liability Coverage Ratio By FCF',
            'group' => 'Chỉ số dòng tiền',
            'unit' => 'scalar',
            'description' => 'Hệ số khả năng thanh toán nợ dài hạn của dòng tiền tự do cho biết mức độ bảo đảm nợ dài hạn bằng dòng tiền tự do tạo ra. Hay nói cách khác, cứ một đồng nợ dài hạn bình quân trong kỳ phải trả của DN được đảm bảo bởi mấy đồng dòng tiền tự do. Khi trị số của chỉ tiêu lớn hơn hoặc bằng 1 (>=1), DN bảo đảm đủ và thừa khả năng thanh toán nợ dài hạn bằng dòng tiền tự do và ngược lại. <strong style="color:#d2691e;">Ngưỡng tham khảo: FCF âm (&lt; 0) là xấu, 0 đến &lt; 1 là chưa đủ trang trải nợ, ≥ 1 là đủ khả năng thanh toán.</strong> Với báo cáo quý, FCF được quy đổi năm (TTM); xem tooltip để biết số liệu riêng quý. <strong style="color:#FF00FF;">Công thức tính = Dòng tiền tự do (FCF, TTM) / Tổng nợ dài hạn bình quân </strong>',
            'values' => $values,
            'alert' => $alert
        ]);
        return $this;
    }

    /**
     * Write Interest Coverage Ratio By FCF - He so kha nang thanh toan lai vay cua dong tien tu do
     *
     * @param \App\Jobs\Financials\Calculators\CashFlowCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    protected function writeInterestCoverageRatioByFCF(CashFlowCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateInterestCoverageRatioByFCF($year, $quarter)->interestCoverageRatioByFCF
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Hệ số thanh toán lãi vay bằng dòng tiền tự do',
            'alias' => 'Interest Coverage Ratio By FCF',
            'group' => 'Chỉ số dòng tiền',
            'unit' => 'scalar',
            'description' => 'Hệ số khả năng thanh toán lãi vay của dòng tiền tự do đánh giá khả năng doanh nghiệp có thể hoàn trả lãi vay của các khoản vay nợ từ dòng tiền FCF trong kỳ của mình hay không. Doanh nghiệp sử dụng đòn bẩy càng cao thì tỷ lệ này càng thấp, doanh nghiệp có 1 bảng cân đối lành mạnh sẽ có tỷ lệ này rất cao. Đối với những doanh nghiệp sử dụng quá nhiều nợ vay (đòn bẩy cao), tỷ lệ này nhỏ hơn 1, khi đó doanh nghiệp sẽ có nhiều khả năng vỡ nợ. <strong style="color:#FF00FF;">Công thức tính = (Dòng tiền tự do FCF + Lãi vay đã trả + Thuế thu nhập đã trả) / Lãi vay đã trả </strong> ',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write Asset Efficency For FCF Ratio - He so hieu qua tao dong tien tu do cua tai san
     *
     * @param \App\Jobs\Financials\Calculators\CashFlowCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    protected function writeAssetEfficencyForFCFRatio(CashFlowCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            $calculator->calculateAssetEfficencyForFCFRatio($year, $quarter);
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->assetEfficencyForFCFRatio,
                'valueNote' => quarterOnlyNote($calculator->assetEfficencyForFCFRatioQuarterOnly, '%', $quarter),
                'ttm' => $quarter != 0
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Hệ số hiệu quả chuyển đổi tài sản thành dòng tiền tự do',
            'alias' => 'Asset Efficency For FCF Ratio',
            'group' => 'Chỉ số dòng tiền',
            'unit' => '%',
            'description' => 'Hệ số hiệu quả tạo dòng tiền tự do từ tài sản đánh giá hiệu quả chuyển đổi từ tài sản thành dòng tiền tự do cho doanh nghiệp. <strong style="color:#d2691e;">Ngưỡng tham khảo: âm (&lt; 0) là dòng tiền tự do âm (cảnh báo).</strong> Với báo cáo quý, FCF được quy đổi năm (TTM); xem tooltip để biết số liệu riêng quý. <strong style="color:#FF00FF;">Công thức tính = Dòng tiền tự do (FCF, TTM) / Tổng tài sản bình quân </strong>',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write Cash Generating Power Ratio - He so suc manh tao tien
     *
     * @param \App\Jobs\Financials\Calculators\CashFlowCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    protected function writeCashGeneratingPowerRatio(CashFlowCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateCashGeneratingPowerRatio($year, $quarter)->cashGeneratingPowerRatio
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Hệ số hiệu quả tạo tiền từ hoạt động kinh doanh',
            'alias' => 'Cash Generating Power Ratio',
            'group' => 'Chỉ số dòng tiền',
            'unit' => '%',
            'description' => 'Hệ số đánh giá khả năng tạo ra tiền mặt của doanh nghiệp (<strong>Cash Generating Power Ratio</strong>) hoàn toàn dựa trên hoạt động kinh doanh, so sánh trên tổng dòng tiền vào của doanh nghiệp. Nếu 1 doanh nghiệp có tỷ lệ này được duy trì > 0 và ổn định trên 15% trong nhiều năm liền, khi đó có thể coi doanh nghiệp đó là một “cỗ máy tạo ra tiền”. <strong style="color:#d2691e;">Ngưỡng tham khảo: CFO âm (&lt; 0) là xấu, ≥ 15% ổn định nhiều năm là cỗ máy tạo tiền.</strong>/ <strong style="color:#FF00FF;">Công thức tính = Lưu chuyển tiền thuần từ HĐKD (CFO) / (CFO + Dòng tiền vào từ hoạt động đầu tư + Dòng tiền vào của hoạt động tài chính) </strong>',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write External Financing Ratio - He so phu thuoc tai chinh ngoai
     *
     * @param \App\Jobs\Financials\Calculators\CashFlowCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    protected function writeExternalFinancingRatio(CashFlowCalculator $calculator, $year, $quarter)
    {
        $values = [];
        $alert = null;
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            $calculator->calculateExternalFinancingRatio($year, $quarter);
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->externalFinancingRatio
            ]);
            if ($i === 1) {
                $alert = negativeCfoAlert($calculator->cfoUsedForExternalFinancing);
            }
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Hệ số phụ thuộc tài chính bên ngoài',
            'alias' => 'External Financing Ratio',
            'group' => 'Chỉ số dòng tiền',
            'unit' => 'scalar',
            'description' => 'Hệ số phụ thuộc tài chính bên ngoài (<strong>External Financing Ratio</strong>) so sánh giữa dòng tiền thuần từ hoạt động tài chính với dòng tiền thuần từ hoạt động kinh doanh để đánh giá sự phụ thuộc của doanh nghiệp vào hoạt động tài chính. Hệ số này càng cao chứng tỏ doanh nghiệp phụ thuộc nhiều vào dòng tiền, dòng vốn đến từ bên ngoài (nợ vay hoặc phát hành thêm cổ phiếu). Thông thường, những doanh nghiệp có tài chính ổn định và hoạt động kinh doanh tốt thường có tỷ lệ External Finacing Ratio âm (nhỏ hơn 0) & CFO > 0. <strong style="color:#FF00FF;">Công thức tính = Lưu chuyển tiền thuần từ hoạt động tài chính (CFF) / Lưu chuyển tiền thuần từ HĐKD (CFO) </strong>',
            'values' => $values,
            'alert' => $alert
        ]);
        return $this;
    }
}
