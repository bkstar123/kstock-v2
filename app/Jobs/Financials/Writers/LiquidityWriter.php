<?php
/**
 * LiquidityWriter trait
 *
 * @author: tuanha
 * @date: 14-Aug-2022
 */
namespace App\Jobs\Financials\Writers;

use App\Jobs\Financials\Calculators\LiquidityCalculator;

trait LiquidityWriter
{
    /**
     * Write AssetsToLiabilities ratio - He so kha nang thanh toan tong quat
     *
     * @param  \App\Jobs\Financials\Calculators\LiquidityCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    protected function writeOverallSolvencyRatio(LiquidityCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateOverallSolvencyRatio($year, $quarter)->overallSolvencyRatio
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Hệ số khả năng thanh toán tổng quát',
            'alias' => 'Overall Solvency Ratio',
            'group' => 'Chỉ số thanh toán',
            'unit' => 'scalar',
            'description' => 'Hệ số khả năng thanh toán tổng quát phản ánh tổng quát nhất năng lực thanh toán của doanh nghiệp trong ngắn và dài hạn. Nếu tỉ lệ > 2 phản ánh khả năng thanh toán của doanh nghiệp rất tốt, tuy nhiên hiệu quả sử dụng vốn có thể không cao và đòn bẩy tài chính thấp. Doanh nghiệp sẽ khó có bước tăng trưởng vượt bậc. Nếu 1 < tỉ lệ < 2 phản ánh về cơ bản, với lượng tổng tài sản hiện có, doanh nghiệp hoàn toàn đáp ứng được các khoản nợ tới hạn. Tỉ lệ < 1 thể hiện khả năng thanh toán của doanh nghiệp thấp, khi chỉ số càng tiến dần về 0, doanh nghiệp sẽ mất dần khả năng thanh toán, việc phá sản có thể xảy ra nếu doanh nghiệp không có giải pháp thực sự phù hợp. <strong style="color:#FF00FF;">Công thức tính = Tổng tài sản/Nợ phải trả</strong> ',
            'values' => $values
        ]);
        return $this;
    }
    
    /**
    * Write Current ratio - He so kha nang thanh toan hien hanh (ngan han)
    *
    * @param  \App\Jobs\Financials\Calculators\LiquidityCalculator $calculator
    * @param  int $year
    * @param  int $quarter
    * @return $this
    */
    protected function writeCurrentRatio(LiquidityCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateCurrentRatio($year, $quarter)->currentRatio
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Hệ số thanh toán hiện hành (ngắn hạn)',
            'alias' => 'Current Ratio',
            'group' => 'Chỉ số thanh toán',
            'unit' => 'scalar',
            'description' => 'Hệ số khả năng thanh toán hiện hành thể hiện khả năng doanh nghiệp thanh toán các khoản nợ ngắn hạn bằng nguồn tài sản ngắn hạn. Nếu hệ số này < 1 phản ánh khả năng trả nợ của doanh nghiệp yếu, là dấu hiệu báo trước những khó khăn tiềm ẩn về tài chính mà doanh nghiệp có thể gặp phải trong việc trả các khoản nợ ngắn hạn. Khi hệ số càng dần về 0, doanh nghiệp càng mất khả năng chi trả, gia tăng nguy cơ phá sản. Nếu hệ số > 1 cho thấy doanh nghiệp có khả năng cao trong việc sẵn sàng thanh toán các khoản nợ đến hạn. Tỷ số càng cao càng đảm bảo khả năng chi trả của doanh nghiệp, tính thanh khoản ở mức cao. Tuy nhiên, trong một số trường hợp, tỷ số quá cao chưa chắc phản ánh khả năng thanh khoản của doanh nghiệp là tốt. Bởi có thể nguồn tài chính không được sử dụng hợp lý, hay hàng tồn kho quá lớn dẫn đến việc khi có biến động trên thị trường, lượng hàng tồn kho không thể bán ra để chuyển hoá thành tiền. <strong style="color:#d2691e;">Theo Buffet, hệ số thanh toán hiện hành có vai trò quan trọng trong việc đánh giá tính thanh khoản của một doanh nghiệp quy mô nhỏ, nhưng không có vai trò tích cực trong việc xác định lợi thế cạnh tranh bền vững, các doanh nghiệp có lợi thế cạnh tranh bền vững có khả năng tạo lợi nhuận lớn nên có thể không cần tấm đệm thanh khoản và có thể có hệ số này nhỏ hơn 1</strong>.  <strong style="color:#FF00FF;">Công thức tính = Tài sản ngắn hạn / Nợ ngắn hạn</strong>',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write Quick Ratio - He so kha nang thanh toan nhanh
     *
     * @param  \App\Jobs\Financials\Calculators\LiquidityCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    protected function writeQuickRatio(LiquidityCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateQuickRatio($year, $quarter)->quickRatio
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Hệ số thanh toán nhanh  (giảm trừ hàng tồn kho)',
            'alias' => 'Quick Ratio 1',
            'group' => 'Chỉ số thanh toán',
            'unit' => 'scalar',
            'description' => 'Hệ số khả năng thanh toán nhanh thể hiện khả năng thanh toán của doanh nghiệp mà không cần thực hiện thanh lý gấp hàng tồn kho, bộ phận có tính thanh khoản thấp nhất trong tài sản ngắn hạn. Hệ số này < 0.5 phản ánh doanh nghiệp đang gặp khó khăn trong việc chi trả nợ ngắn hạn, tính thanh khoản thấp, Hệ số này > 0.5 phản ánh doanh nghiệp có khả năng thanh toán tốt, tính thanh khoản cao. <strong style="color:#FF00FF;">Công thức tính = (Tài sản ngắn hạn - hàng tồn kho) / Nợ ngắn hạn </strong>',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write Quick Ratio 2 - He so kha nang thanh toan nhanh 2 (loai bo hang ton kho va phai thu ngan han)
     *
     * @param  \App\Jobs\Financials\Calculators\LiquidityCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    protected function writeQuickRatio2(LiquidityCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateQuickRatio2($year, $quarter)->quickRatio2
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Hệ số thanh toán nhanh (giảm trừ hàng tồn kho và các khoản phải thu ngắn hạn)',
            'alias' => 'Quick Ratio 2',
            'group' => 'Chỉ số thanh toán',
            'unit' => 'scalar',
            'description' => 'Tương tự như hệ số khả năng thanh toán nhanh, nhưng loại trừ thêm các khoản phải thu ngắn hạn — phép thử khắt khe hơn Quick Ratio 1 vì chỉ còn lại tiền/tương đương tiền và các khoản đầu tư ngắn hạn dễ thanh khoản. <strong style="color:#d2691e;">Ngưỡng tham khảo: &lt; 0.3 thanh khoản yếu, ≥ 0.3 được xem là an toàn.</strong> <strong style="color:#FF00FF;">Công thức tính = (Tài sản ngắn hạn - hàng tồn kho - khoản phải thu ngắn hạn) / Nợ ngắn hạn </strong>',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write CashRatio - He so kha nang thanh toan tuc thoi
     *
     * @param  \App\Jobs\Financials\Calculators\LiquidityCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    protected function writeCashRatio(LiquidityCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateCashRatio($year, $quarter)->cashRatio
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Hệ số khả năng thanh toán bằng tiền mặt',
            'alias' => 'Cash Ratio',
            'group' => 'Chỉ số thanh toán',
            'unit' => 'scalar',
            'description' => 'Hệ số khả năng thanh toán bằng tiền mặt, hay còn gọi là hệ số khả năng thanh toán tức thời, cho biết doanh nghiệp có bao nhiêu đồng vốn bằng tiền để sẵn sàng thanh toán cho một đồng nợ ngắn hạn, đây là thước đo khả năng thanh khoản của công ty. Hệ số này tính toán khả năng trả nợ ngắn hạn bằng tiền mặt hoặc tương đương tiền mặt. <strong style="color:#d2691e;">Hệ số thanh khoản bằng tiền mặt nhỏ hơn 0.5 thường được xem là rủi ro; ≥ 0.5 được xem là an toàn.</strong> Hệ số này đặc biệt hữu ích khi đánh giá tính thanh khoản của một doanh nghiệp trong giai đoạn nền kinh tế đang gặp khủng hoảng (khi mà hàng tồn kho không tiêu thụ được, các khoản phải thu khó thu hồi). Tuy nhiên, trong nền kinh tế ổn định, dùng tỷ số khả năng thanh toán tức thời đánh giá tính thanh khoản của một doanh nghiệp có thể xảy ra sai sót. Bởi lẽ, một doanh nghiệp có một lượng lớn nguồn tài chính không được sử dụng đồng nghĩa do doanh nghiệp đó sử dụng không hiệu quả nguồn vốn. <strong style="color:#FF00FF;">Công thức tính = Tiền & tương đương tiền / Nợ ngắn hạn</strong>',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write Interest Coverage Ratio - He so kha nang chi tra lai vay
     *
     * @param  \App\Jobs\Financials\Calculators\LiquidityCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    protected function writeInterestCoverageRatio(LiquidityCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateInterestCoverageRatio($year, $quarter)->interestCoverageRatio
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Hệ số chi trả lãi vay',
            'alias' => 'Interest Coverage Ratio',
            'group' => 'Chỉ số thanh toán',
            'unit' => 'scalar',
            'description' => 'Hệ số khả năng thanh toán lãi vay còn được gọi là hệ số thanh toán lãi nợ vay. Đây là chỉ số cho biết khả năng đảm bảo trả lãi nợ vay của doanh nghiệp. Ngoài ra, thông qua chỉ số tài chính này, có thấy được khả năng tài chính của doanh nghiệp tạo ra để trang trải cho chi phí vay vốn cho các hoạt động kinh doanh sản xuất. Tỷ lệ thanh toán lãi vay xác định khả năng tài chính của một công ty có thể trả lãi cho các khoản nợ tồn đọng của mình hay không. Các nhà kinh tế học chọn mức hệ số 2 là mốc để đánh giá khả năng đó, nếu hệ số thanh toán lãi vay thấp hơn càng xa hệ số 2 thì doanh nghiệp đó đang gặp vấn đề tài chính và khả năng có thể tự chi trả khoản lãi vay là rất thấp. <strong style="color:#d2691e;">Theo Buffet thì doanh nghiệp có lợi thế cạnh tranh bền vững thường có tỉ lệ này cao hơn 6.67 lần </strong>. <strong style="color:#d2691e;">Ngưỡng tham khảo trên trang này: &lt; 1 không đủ trả lãi vay (xấu), 1–2 khả năng trả lãi vay thấp, ≥ 2 thừa khả năng trả lãi vay.</strong> <strong style="color:#FF00FF;">Công thức tính = EBIT / Lãi vay</strong>',
            'values' => $values
        ]);
        return $this;
    }
}
