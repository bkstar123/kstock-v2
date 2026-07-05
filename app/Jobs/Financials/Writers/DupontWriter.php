<?php
/**
 * DupontWriter trait
 *
 * @author: tuanha
 * @date: 14-Aug-2022
 */
namespace App\Jobs\Financials\Writers;

use App\Jobs\Financials\Calculators\DupontCalculator;

trait DupontWriter
{
    /**
     * Write Dupont Level 2 Components
     *
     * @param \App\Jobs\Financials\Calculators\DupontCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    protected function writeDupontLevel2Components(DupontCalculator $calculator, $year, $quarter)
    {
        $values1 = [];
        $values2 = [];
        $values3 = [];
        $alert = null;
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values1, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateDupontComponents($year, $quarter)->roaa
            ]);
            array_push($values2, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->averageFinancialLeverage
            ]);
            array_push($values3, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => round($calculator->averageFinancialLeverage * $calculator->roaa, 1)
            ]);
            if ($i === 1 && $calculator->averageFinancialLeverage !== null && $calculator->averageFinancialLeverage < 0) {
                $alert = 'Hệ số đòn bẩy tài chính trung bình (Tổng tài sản bq / VCSH bq) đang <strong>âm</strong> '
                       . '— tức VCSH bình quân âm (lỗ lũy kế vượt vốn góp). Khi đó, tích của 2 thành phần phía '
                       . 'trên có thể cho ra ROEA dương trông bình thường dù thực chất doanh nghiệp đang mất vốn, '
                       . 'cần xem trực tiếp giá trị VCSH thay vì chỉ nhìn dòng ROEA tổng hợp.';
            }
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'ROAA',
            'alias' => 'Dupont2-ROAA',
            'group' => "Phân tích Dupont Level 2",
            'unit' => '%',
            'description' => 'Đánh giá hiệu quả sử dụng tài sản của doanh nghiệp',
            'values' => $values1
        ]);
        array_push($this->content, [
            'name' => 'Hệ số đòn bẩy tài chính trung bình',
            'alias' => 'Dupont2-FinancialLeverage',
            'group' => "Phân tích Dupont Level 2",
            'unit' => 'scalar',
            'description' => 'Phản ánh cơ cấu nguồn vốn của doanh nghiệp',
            'values' => $values2
        ]);
        array_push($this->content, [
            'name' => '==> ROEA',
            'alias' => 'Dupont2-ROEA',
            'group' => "Phân tích Dupont Level 2",
            'unit' => '%',
            'description' => 'Tỷ suất lợi nhuận trên VCSH bình quân',
            'values' => $values3,
            'alert' => $alert
        ]);
        return $this;
    }

    /**
     * Write Dupont Level 3 Components
     *
     * @param \App\Jobs\Financials\Calculators\DupontCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    protected function writeDupontLevel3Components(DupontCalculator $calculator, $year, $quarter)
    {
        $values1 = [];
        $values2 = [];
        $values3 = [];
        $values4 = [];
        $alert = null;
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values1, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateDupontComponents($year, $quarter)->ros2
            ]);
            array_push($values2, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->averageTotalAssetTurnOver
            ]);
            array_push($values3, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->averageFinancialLeverage
            ]);
            array_push($values4, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => round($calculator->ros2 * $calculator->averageTotalAssetTurnOver * $calculator->averageFinancialLeverage, 1)
            ]);
            if ($i === 1 && $calculator->averageFinancialLeverage !== null && $calculator->averageFinancialLeverage < 0) {
                $alert = 'Hệ số đòn bẩy tài chính trung bình (Tổng tài sản bq / VCSH bq) đang <strong>âm</strong> '
                       . '— tức VCSH bình quân âm (lỗ lũy kế vượt vốn góp). Khi đó, tích các thành phần phía '
                       . 'trên có thể cho ra ROEA dương trông bình thường dù thực chất doanh nghiệp đang mất vốn, '
                       . 'cần xem trực tiếp giá trị VCSH thay vì chỉ nhìn dòng ROEA tổng hợp.';
            }
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tỷ suất lợi nhuận ròng của cổ đông công ty mẹ (phiên bản chặt chẽ hơn của ROS)',
            'alias' => 'Dupont3-ROS2',
            'group' => "Phân tích Dupont Level 3",
            'unit' => '%',
            'description' => 'Hiệu quả quản lý và hoạt động của doanh nghiệp',
            'values' => $values1
        ]);
        array_push($this->content, [
            'name' => 'Vòng quay tổng tài sản bình quân',
            'alias' => 'Dupont3-Average Total Asset Turnover',
            'group' => "Phân tích Dupont Level 3",
            'unit' => 'cycles',
            'description' => 'Đánh giá hiệu quả sử dụng tài sản của doanh nghiệp',
            'values' => $values2
        ]);
        array_push($this->content, [
            'name' => 'Hệ số đòn bẩy tài chính trung bình',
            'alias' => 'Dupont3-FinancialLeverage',
            'group' => "Phân tích Dupont Level 3",
            'unit' => 'scalar',
            'description' => 'Phản ánh cơ cấu nguồn vốn của doanh nghiệp',
            'values' => $values3
        ]);
        array_push($this->content, [
            'name' => '==> ROEA',
            'alias' => 'Dupont3-ROEA',
            'group' => "Phân tích Dupont Level 3",
            'unit' => '%',
            'description' => 'Tỷ suất lợi nhuận trên VCSH bình quân',
            'values' => $values4,
            'alert' => $alert
        ]);
        return $this;
    }

    /**
     * Write Dupont Level 5 Components
     *
     * @param \App\Jobs\Financials\Calculators\DupontCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    protected function writeDupontLevel5Components(DupontCalculator $calculator, $year, $quarter)
    {
        $values1 = [];
        $values2 = [];
        $values3 = [];
        $values4 = [];
        $values5 = [];
        $values6 = [];
        $values7 = [];
        $alert = null;
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values1, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateDupontComponents($year, $quarter)->earningAfterTaxParentCompanyToEarningBeforeTax
            ]);
            array_push($values2, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->earningAfterTaxToEarningBeforeTax
            ]);
            array_push($values3, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->earningBeforeTaxToEBIT
            ]);
            array_push($values4, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->ebitMargin
            ]);
            array_push($values5, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->averageTotalAssetTurnOver
            ]);
            array_push($values6, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->averageFinancialLeverage
            ]);
            array_push($values7, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => round($calculator->earningAfterTaxParentCompanyToEarningBeforeTax * $calculator->earningBeforeTaxToEBIT * $calculator->ebitMargin * $calculator->averageTotalAssetTurnOver * $calculator->averageFinancialLeverage, 1)
            ]);
            if ($i === 1 && $calculator->averageFinancialLeverage !== null && $calculator->averageFinancialLeverage < 0) {
                $alert = 'Hệ số đòn bẩy tài chính trung bình (Tổng tài sản bq / VCSH bq) đang <strong>âm</strong> '
                       . '— tức VCSH bình quân âm (lỗ lũy kế vượt vốn góp). Khi đó, tích 5 thành phần phía '
                       . 'trên có thể cho ra ROEA dương trông bình thường dù thực chất doanh nghiệp đang mất vốn, '
                       . 'cần xem trực tiếp giá trị VCSH thay vì chỉ nhìn dòng ROEA tổng hợp.';
            }
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'LNST của cổ đông công ty mẹ / LNTT',
            'alias' => 'Dupont5-Earning After Tax of Parent Company To Earning Before Tax',
            'group' => "Phân tích Dupont Level 5",
            'unit' => 'scalar',
            'description' => 'Đánh giá sự ảnh hưởng của thuế TNDN và lợi ích của cổ đông không kiểm soát lên lợi nhuận ròng của cổ đông công ty mẹ',
            'values' => $values1
        ]);
        array_push($this->content, [
            'name' => '-----Trong đó, LNST/LNTT',
            'alias' => 'Dupont5-Earning After Tax To Earning Before Tax',
            'group' => "Phân tích Dupont Level 5",
            'unit' => 'scalar',
            'description' => 'Còn được gọi là hệ số gánh nặng thuế (Tax Burden) phản ánh mức thuế mà doanh nghiệp phải chịu, và chính sách của doanh nghiệp sẽ là cố gắng tối thiểu hóa gánh nặng thuế',
            'values' => $values2
        ]);
        array_push($this->content, [
            'name' => 'LNTT/EBIT',
            'alias' => 'Dupont5-Earning Before Tax To EBIT',
            'group' => "Phân tích Dupont Level 5",
            'unit' => 'scalar',
            'description' => 'Hệ số Gánh nặng lãi vay (Interest Burden – IB) đánh giá sự ảnh hưởng của chi phí lãi vay lên lợi nhuận ròng của doanh nghiệp. LNTT/EBIT của doanh nghiệp lớn nhất khi không có các khoản thanh toán lãi vay cho chủ nợ (không vay nợ). Khi đó, giá trị cao nhất và tốt nhất mà hệ số này có thể có được là 1. Đòn bẩy tài chính càng thấp, hệ số IB sẽ càng cao, và rủi ro tài chính cho các cổ đông sẽ nhỏ.',
            'values' => $values3
        ]);
        array_push($this->content, [
            'name' => 'EBIT/Doanh thu thuần',
            'alias' => 'Dupont5-EBIT Margin',
            'group' => "Phân tích Dupont Level 5",
            'unit' => '%',
            'description' => 'Chỉ tiêu này phản ánh khả năng kiểm soát các loại chi phí của doanh nghiệp tốt đến đâu như giá vốn hàng bán, chi phí bán hàng, chi phí QLDN',
            'values' => $values4
        ]);
        array_push($this->content, [
            'name' => 'Vòng quay tổng tài sản bình quân',
            'alias' => 'Dupont5-Average Total Asset Turnover',
            'group' => "Phân tích Dupont Level 5",
            'unit' => 'cycles',
            'description' => 'Đánh giá hiệu quả sử dụng tài sản của doanh nghiệp',
            'values' => $values5
        ]);
        array_push($this->content, [
            'name' => 'Hệ số đòn bẩy tài chính trung bình',
            'alias' => 'Dupont5-FinancialLeverage',
            'group' => "Phân tích Dupont Level 5",
            'unit' => 'scalar',
            'description' => 'Thể hiện đòn bẩy tài chính doanh nghiệp, Với cùng 1 lượng tài sản, doanh nghiệp có đòn bẩy tài chính phù hợp trong cơ cấu tài sản có thể tạo ra 1 tỷ suất sinh lời ROE cao hơn 1 doanh nghiệp không dùng đòn bẩy. Tuy nhiên, đòn bẩy tài chính sẽ làm tăng rủi ro. Nợ vay làm cho doanh nghiệp dễ nhạy cảm hơn với chu kỳ kinh tế, nhất là trong giai đoạn khó khăn, doanh thu giảm',
            'values' => $values6
        ]);
        array_push($this->content, [
            'name' => '==> ROEA',
            'alias' => 'Dupont5-ROEA',
            'group' => "Phân tích Dupont Level 5",
            'unit' => '%',
            'description' => 'Tỷ suất lợi nhuận trên VCSH bình quân',
            'values' => $values7,
            'alert' => $alert
        ]);
        return $this;
    }
}
