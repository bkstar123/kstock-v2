<?php
/**
 * ProfitabilityWriter trait
 *
 * @author: tuanha
 * @date: 14-Aug-2022
 */
namespace App\Jobs\Financials\Writers;

use App\Jobs\Financials\Calculators\ProfitabilityCalculator;

trait ProfitabilityWriter
{
    /**
     * Write ROAA - Ty suat loi nhuan tren tong tai san binh quan
     *
     * @param  \App\Jobs\Financials\Calculators\ProfitabilityCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    protected function writeROAA(ProfitabilityCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            $calculator->calculateROAA($year, $quarter);
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->roaa,
                'valueNote' => quarterOnlyNote($calculator->roaaQuarterOnly, '%', $quarter),
                'ttm' => $quarter != 0
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tỷ suất lợi nhuận trên tổng tài sản bình quân',
            'alias' => 'ROAA',
            'group' => 'Chỉ số sinh lời',
            'unit' => '%',
            'description' => '<strong>ROAA</strong> - Tỷ suất lợi nhuận trên tổng tài sản bình quân (<strong>Return on Average Assets</strong>) cho biết tài sản của một doanh nghiệp đang được sử dụng tốt như thế nào để tạo ra lợi nhuận. Với báo cáo quý, lợi nhuận được quy đổi năm (lũy kế 4 quý gần nhất - TTM) để so sánh đúng với tài sản bình quân; xem tooltip cạnh mỗi giá trị để biết số liệu riêng quý đó. <strong style="color:#d2691e;">Ngưỡng tham khảo: âm là thua lỗ, ≥ 7.5% được coi là sinh lời cao.</strong> <strong style="color:#FF00FF;">Công thức tính = 100% * Lợi nhuận sau thuế của cổ đông của công ty mẹ (TTM) / Tổng tài sản bình quân</strong>',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write ROTA - Ty suat loi nhuan truoc thue va lai vay tren tong tai san binh quan
     *
     * @param  \App\Jobs\Financials\Calculators\ProfitabilityCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    protected function writeROTA(ProfitabilityCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            $calculator->calculateROTA($year, $quarter);
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->rota,
                'valueNote' => quarterOnlyNote($calculator->rotaQuarterOnly, '%', $quarter),
                'ttm' => $quarter != 0
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tỷ suất lợi nhuận trước thuế và lãi vay trên tổng tài sản bình quân',
            'alias' => 'ROTA',
            'group' => 'Chỉ số sinh lời',
            'unit' => '%',
            'description' => '<strong>ROTA</strong> - Tỷ suất lợi nhuận trước thuế và lãi vay (EBIT) trên tổng tài sản bình quân mang ý nghĩa tương tự ROAA hay ROA nhưng loại bỏ sự ảnh hưởng của cấu trúc nguồn vốn (chi phí lãi vay) và sự ảnh hưởng của thuế suất doanh nghiệp. Chỉ số này dùng để đánh giá hiệu quả sinh lời của tài sản doanh nghiệp dựa trên mô hình kinh doanh thuần tuý. Với báo cáo quý, EBIT được quy đổi năm (TTM); xem tooltip cạnh mỗi giá trị để biết số liệu riêng quý đó. <strong style="color:#d2691e;">Ngưỡng tham khảo: âm là thua lỗ, ≥ 7.5% được coi là sinh lời cao.</strong> <strong style="color:#FF00FF;">Công thức tính = 100% * Lợi nhuận trước thuế và lãi vay (EBIT, TTM)/ Tổng tài sản bình quân</strong>',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write ROA - Ty suat loi nhuan tren tong tai san trong ky
     *
     * @param  \App\Jobs\Financials\Calculators\ProfitabilityCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    protected function writeROA(ProfitabilityCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            $calculator->calculateROA($year, $quarter);
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->roa,
                'valueNote' => quarterOnlyNote($calculator->roaQuarterOnly, '%', $quarter),
                'ttm' => $quarter != 0
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tỷ suất lợi nhuận trên tổng tài sản',
            'alias' => 'ROA',
            'group' => 'Chỉ số sinh lời',
            'unit' => '%',
            'description' => '<strong>ROA</strong> - Tỷ suất lợi nhuận trên tổng tài sản (<strong>Return on Assets</strong>). Với báo cáo quý, lợi nhuận được quy đổi năm (TTM); xem tooltip cạnh mỗi giá trị để biết số liệu riêng quý đó. <strong style="color:#d2691e;">Ngưỡng tham khảo: âm là thua lỗ, ≥ 7.5% được coi là sinh lời cao.</strong> <strong style="color:#FF00FF;">Công thức tính = 100% * Lợi nhuận sau thuế của cổ đông của công ty mẹ (TTM) / Tổng tài sản</strong>',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write ROCE - Ty suat loi nhuan tren von dai han binh quan
     *
     * @param  \App\Jobs\Financials\Calculators\ProfitabilityCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return array
     */
    protected function writeROCE(ProfitabilityCalculator $calculator, $year, $quarter)
    {
        $values = [];
        $alert = null;
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            $calculator->calculateROCE($year, $quarter);
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->roce,
                'valueNote' => quarterOnlyNote($calculator->roceQuarterOnly, '%', $quarter),
                'ttm' => $quarter != 0
            ]);
            if ($i === 1 && $calculator->capitalEmployedUsedForROCE !== null && $calculator->capitalEmployedUsedForROCE < 0) {
                $alert = 'Vốn dài hạn (Tổng tài sản bình quân − Nợ ngắn hạn bình quân) đang <strong>âm</strong> — '
                       . 'ROCE không còn phản ánh đúng hiệu quả sử dụng vốn, cần xem trực tiếp giá trị vốn dài hạn.';
            }
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tỷ suất lợi nhuận trên vốn dài hạn bình quân',
            'alias' => 'ROCE',
            'group' => 'Chỉ số sinh lời',
            'unit' => '%',
            'description' => '<strong>ROCE</strong> - Tỷ suất lợi nhuận trên vốn dài hạn bình quân (<strong>Return on Capital Employed</strong>) đo lường khả năng sinh lời và hiệu quả sử dụng vốn của doanh nghiệp. Chỉ số ROCE có thể đặc biệt hữu ích khi so sánh hiệu quả hoạt động của các công ty trong các lĩnh vực sử dụng nhiều vốn, chẳng hạn như các dịch vụ tiện ích và viễn thông, ROCE xem xét nợ và vốn chủ sở hữu . Điều này có thể giúp phân tích hiệu quả tài chính đối với các công ty có nợ đáng kể. Với báo cáo quý, EBIT được quy đổi năm (TTM); xem tooltip cạnh mỗi giá trị để biết số liệu riêng quý đó. <strong style="color:#d2691e;">Ngưỡng tham khảo: âm là thua lỗ, ≥ 15% được coi là sinh lời cao.</strong> <strong style="color:#FF00FF;">Công thức tính = EBIT (TTM) * 100% / (Tổng tài sản bình quân - nợ ngắn hạn bình quân)</strong>',
            'values' => $values,
            'alert' => $alert
        ]);
        return $this;
    }

    /**
     * Write ROEA - Ty suat loi nhuan tren VCSH binh quan
     *
     * @param  \App\Jobs\Financials\Calculators\ProfitabilityCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return array
     */
    protected function writeROEA(ProfitabilityCalculator $calculator, $year, $quarter)
    {
        $values = [];
        $alert = null;
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            $calculator->calculateROEA($year, $quarter);
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->roea,
                'valueNote' => quarterOnlyNote($calculator->roeaQuarterOnly, '%', $quarter),
                'ttm' => $quarter != 0
            ]);
            if ($i === 1) {
                $alert = negativeEquityAlert($calculator->averageEquityUsedForROEA);
            }
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tỷ suất lợi nhuận trên vốn chủ sở hữu bình quân',
            'alias' => 'ROEA',
            'group' => 'Chỉ số sinh lời',
            'unit' => '%',
            'description' => '<strong>ROEA</strong> - Tỷ suất lợi nhuận trên vốn chủ sở hữu bình quân (<strong>Return on Equity Average</strong>) đo lường mức độ hiệu quả trong việc sử dụng vốn chủ sở hữu của doanh nghiệp, ROEA được dùng kết hợp với chỉ số ROE khi phân tích một doanh nghiệp có hiện tượng biến động vốn chủ sở hữu quá lớn trong kỳ phân tích. Vốn chủ sở hữu thường chịu ảnh hưởng bởi các yếu tố: lợi nhuận giữ lại, sáp nhập; phát hành riêng lẻ để tăng vốn… Vì vậy xét trong 1 năm tài chính, nếu doanh nghiệp có sự biến động về vốn chủ sở hữu thì ROE sẽ không phản ánh chính xác khả năng sinh lời của việc sử dụng vốn của doanh nghiệp. ROEA đo lường chính xác hơn về hiệu quả sử dụng vốn của doanh nghiệp trong trường hợp  vốn chủ sở hữu đã có sự biến động trong năm tài chính nhờ việc tính bình quân vốn chủ sở hữu trong kỳ. Với báo cáo quý, lợi nhuận được quy đổi năm (lũy kế 4 quý gần nhất - TTM); xem tooltip cạnh mỗi giá trị để biết số liệu riêng quý đó. <strong style="color:#d2691e;">Ngưỡng tham khảo: âm là thua lỗ, ≥ 15% được coi là sinh lời cao.</strong> <strong style="color:#FF00FF;">Công thức tính = 100% * Lợi nhuận sau thuế của cổ đông công ty mẹ (TTM) / Vốn chủ sở hữu bình quân</strong>',
            'values' => $values,
            'alert' => $alert
        ]);
        return $this;
    }

    /**
     * Write ROE - Ty suat loi nhuan tren VCSH trong kỳ
     *
     * @param  \App\Jobs\Financials\Calculators\ProfitabilityCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return array
     */
    protected function writeROE(ProfitabilityCalculator $calculator, $year, $quarter)
    {
        $values = [];
        $alert = null;
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            $calculator->calculateROE($year, $quarter);
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->roe,
                'valueNote' => quarterOnlyNote($calculator->roeQuarterOnly, '%', $quarter),
                'ttm' => $quarter != 0
            ]);
            if ($i === 1) {
                $alert = negativeEquityAlert($calculator->equityUsedForROE);
            }
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tỷ suất lợi nhuận trên vốn chủ sở',
            'alias' => 'ROE',
            'group' => 'Chỉ số sinh lời',
            'unit' => '%',
            'description' => '<strong>ROE</strong> - Tỷ suất lợi nhuận trên vốn chủ sở hữu (<strong>Return on Equity</strong>). Với báo cáo quý, lợi nhuận được quy đổi năm (TTM); xem tooltip cạnh mỗi giá trị để biết số liệu riêng quý đó. <strong style="color:#d2691e;">Ngưỡng tham khảo: âm là thua lỗ, ≥ 15% được coi là sinh lời cao.</strong> <strong style="color:#FF00FF;">Công thức tính = 100% * Lợi nhuận sau thuế của cổ đông công ty mẹ (TTM) / Vốn chủ sở hữu</strong>',
            'values' => $values,
            'alert' => $alert
        ]);
        return $this;
    }

    /**
     * Write ROS - Ty suat loi nhuan rong (theo LNST)
     *
     * @param  \App\Jobs\Financials\Calculators\ProfitabilityCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return array
     */
    protected function writeROS(ProfitabilityCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateROS($year, $quarter)->ros
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tỉ suất lợi nhuận ròng',
            'alias' => 'ROS',
            'group' => 'Chỉ số sinh lời',
            'unit' => '%',
            'description' => '<strong>ROS</strong> - Tỉ suất lợi nhuận ròng trên doanh thu thuần (<strong>Return On Sales</strong>) thể hiện mối tương quan giữa lợi nhuận được tạo ra dựa trên mỗi đồng doanh số cho biết với một đồng doanh thu thuần từ bán hàng và cung cấp dịch vụ sẽ tạo ra bao nhiêu đồng lợi nhuận ròng, tỷ suất này càng lớn thì hiệu quả hoạt động của doanh nghiệp càng cao. <strong style="color:#d2691e;">Theo Buffet, thì tỉ suất lợi nhuận ròng duy trì đều đặn trên 20% nhiều khả năng là doanh nghiệp có lợi thế cạnh tranh bền vững, nếu thấp hơn 10% thường là dấu hiệu của doanh nghiệp hoạt động trong ngành có sự cạnh tranh gay gắt</strong>. Chỉ số trên trang này đánh dấu "sinh lời cao" khi ROS ≥ 10% (ngưỡng đủ, chưa hẳn đạt mức "bền vững" theo Buffett ở 20%); âm là thua lỗ. <strong style="color:#FF00FF;">Công thức tính = 100% * Lợi nhuận sau thuế/ Doanh thu thuần</strong>',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write ROS2 - Ty suat loi nhuan rong (theo LNST co dong cong ty me)
     *
     * @param  \App\Jobs\Financials\Calculators\ProfitabilityCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return array
     */
    protected function writeROS2(ProfitabilityCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateROS2($year, $quarter)->ros2
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tỉ suất lợi nhuận ròng của cổ đông công ty mẹ',
            'alias' => 'ROS2',
            'group' => 'Chỉ số sinh lời',
            'unit' => '%',
            'description' => '<strong>Phiên bản chặt chẽ hơn của ROS</strong> - Tỉ suất lợi nhuận ròng của cổ đông công ty mẹ. <strong style="color:#d2691e;">Ngưỡng tham khảo (như ROS): âm là thua lỗ, ≥ 10% được coi là sinh lời cao.</strong> <strong style="color:#FF00FF;">Công thức tính = 100% * Lợi nhuận sau thuế của cổ đông công ty mẹ / Doanh thu thuần</strong>',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write EBITDA Margin — gộp 2 cách tính vào 1 chỉ số hiển thị:
     * - Ưu tiên cách tính theo LCTT (Margin2, đáng tin hơn — lấy thẳng dòng khấu hao từ
     *   LCTT gián tiếp), rớt về cách tính theo CĐKT (Margin1) khi Margin2 không tính được
     *   (vd công ty LCTT trực tiếp).
     * - Cả 2 kết quả + cơ sở tính được đưa vào tooltip của từng kỳ.
     * - Nếu 2 cách tính lệch nhau lớn ở kỳ hiện tại (nghi thanh lý TSCĐ làm méo ước tính
     *   khấu hao qua CĐKT) thì gắn banner cảnh báo cấp chỉ số.
     *
     * @param  \App\Jobs\Financials\Calculators\ProfitabilityCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    protected function writeEBITDAMargin(ProfitabilityCalculator $calculator, $year, $quarter)
    {
        $values = [];
        $alert = null;
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            $m1 = $calculator->calculateEBITDAMargin1($year, $quarter)->ebitdaMargin1;
            $m2 = $calculator->calculateEBITDAMargin2($year, $quarter)->ebitdaMargin2;
            $primary = $m2 ?? $m1;

            $notes = [];
            if ($m2 !== null) {
                $notes[] = "EBITDA (LCTT gián tiếp — LNTT + lãi vay + khấu hao lấy từ dòng LCTT): {$m2}%";
            }
            if ($m1 !== null) {
                $notes[] = "EBITDA (CĐKT — LNTT + lãi vay + biến động hao mòn lũy kế TSCĐ/BĐS đầu tư): {$m1}%";
            }
            $divergent = $m1 !== null && $m2 !== null
                && abs($m1 - $m2) > max(abs($m1), abs($m2), 1) * 0.3;
            if ($divergent) {
                $notes[] = 'Lưu ý: 2 cách tính lệch nhau lớn — có thể do doanh nghiệp thanh lý/nhượng bán TSCĐ lớn trong kỳ.';
            }

            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $primary,
                'valueNote' => count($notes) ? implode(' | ', $notes) : null
            ]);

            if ($i === 1 && $divergent) {
                $alert = "<strong>Chênh lệch lớn</strong> giữa 2 cách tính EBITDA Margin ở kỳ này — "
                    . "LCTT: {$m2}% · CĐKT: {$m1}%. Có thể do doanh nghiệp thanh lý/nhượng bán TSCĐ lớn "
                    . "trong kỳ, khiến ước tính khấu hao qua biến động hao mòn lũy kế (CĐKT) bị méo.";
            }

            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Biên EBITDA (lợi nhuận trước thuế, lãi vay và khấu hao / doanh thu thuần)',
            'alias' => 'EBITDA margin 2',
            'group' => 'Chỉ số sinh lời',
            'unit' => '%',
            'description' => '<strong>EBITDA margin</strong> - Hệ số EBITDA/Doanh thu thuần cho biết tỉ lệ phần trăm thu nhập của công ty còn lại sau chi phí hoạt động, loại trừ ảnh hưởng của cấu trúc vốn (lãi vay), khấu hao và thuế thu nhập. Ưu tiên tính theo báo cáo lưu chuyển tiền tệ (lấy thẳng dòng khấu hao) — đáng tin hơn cách ước tính qua biến động hao mòn lũy kế trên bảng CĐKT vốn có thể bị méo khi công ty thanh lý TSCĐ lớn trong kỳ; xem tooltip để so sánh cả 2 cách tính. <strong style="color:#FF00FF;">Công thức tính = 100% * EBITDA / doanh thu thuần</strong>',
            'values' => $values,
            'alert' => $alert
        ]);
        return $this;
    }

    /**
     * Write EBIT Margin
     *
     * @param  \App\Jobs\Financials\Calculators\ProfitabilityCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return array
     */
    protected function writeEBITMargin(ProfitabilityCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateEBITMargin($year, $quarter)->ebitMargin
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Biên lợi nhuận trước thuế và lãi vay trên doanh thu thuần',
            'alias' => 'EBIT margin',
            'group' => 'Chỉ số sinh lời',
            'unit' => '%',
            'description' => '<strong>EBIT margin</strong> - EBIT/doanh thu thuần thể hiện hiệu quả quản lý tất cả chi phí hoạt động, bao gồm giá vốn và chi phí bán hàng, chi phí quản lý của doanh nghiệp. EBIT là một chỉ số dùng để đánh giá khả năng thu được lợi nhuận của công ty, bằng thu nhập trừ đi các chi phí hoạt động, nhưng chưa trừ tiền trả lãi vay và thuế thu nhập. Vai trò của chỉ số EBIT là loại bỏ sự khác nhau giữa cấu trúc vốn và tỷ suất thuế giữa các công ty khác nhau, đánh giá thu nhập của các doanh nghiệp khi quy đồng về mức thuế về 0, và đều không có vay nợ. . <strong style="color:#FF00FF;">Công thức tính = 100% * EBIT / doanh thu thuần</strong>',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write Gross profit margin - Bien loi nhuan gop
     *
     * @param  \App\Jobs\Financials\Calculators\ProfitabilityCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return array
     */
    protected function writeGrossProfitMargin(ProfitabilityCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateGrossProfitMargin($year, $quarter)->grossProfitMargin
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Biên lợi nhuận gộp',
            'alias' => 'Gross profit margin',
            'group' => 'Chỉ số sinh lời',
            'unit' => '%',
            'description' => '<strong>Gross Profit Margin</strong> - Biên lợi nhuận gộp hay còn gọi là tỷ suất lợi nhuận gộp đánh giá khả năng sinh lời của doanh nghiệp. <strong style="color:#d2691e;">Theo quan điểm của Buffet, thì tỉ suất lợi nhuận gộp trên 40% thường là doanh nghiệp có lợi thế cạnh tranh bền vững, nếu thấp hơn 20% thường là dấu hiệu của ngành có sự cạnh tranh gay gắt</strong>. <strong style="color:#FF00FF;">Công thức tính = 100% * Lợi nhuận gộp / Doanh thu thuần</strong>',
            'values' => $values
        ]);
        return $this;
    }
}
