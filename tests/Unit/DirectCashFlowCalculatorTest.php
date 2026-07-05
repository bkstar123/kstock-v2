<?php

namespace Tests\Unit;

use App\Jobs\Financials\Calculators\DirectCapexCalculator;
use App\Jobs\Financials\Calculators\DirectCashFlowCalculator;
use App\Models\BalanceStatement;
use App\Models\CashFlowStatement;
use App\Models\IncomeStatement;
use Tests\TestCase;

/**
 * DirectCashFlowCalculator/DirectCapexCalculator tính CFO/FCF cho LCTT theo phương pháp
 * TRỰC TIẾP — item ID hoàn toàn khác phương pháp gián tiếp (ca thực tế: NTL #66, id '104'
 * là "Tiền chi trả lãi vay" chứ không phải CFO). Calculator tra theo TÊN dòng trong đúng
 * mục cha (I/II/III, luôn id '1'/'2'/'3' theo mẫu B03-DN) thay vì literal id, để không phụ
 * thuộc số lượng dòng chi tiết khác nhau giữa các công ty.
 */
class DirectCashFlowCalculatorTest extends TestCase
{
    /** Mô phỏng thu gọn cấu trúc LCTT trực tiếp thật của NTL. */
    private function directCashFlowStatement(): CashFlowStatement
    {
        $line = function ($id, $parent, $level, $name, $current, $previous) {
            return [
                'id' => $id, 'name' => $name, 'parentID' => $parent, 'expanded' => true,
                'level' => $level, 'field' => '',
                'values' => [
                    ['period' => 'Q1 2026', 'year' => 2026, 'quarter' => 1, 'value' => $current],
                    ['period' => 'Q4 2025', 'year' => 2025, 'quarter' => 4, 'value' => $previous],
                ],
            ];
        };
        $content = [
            $line('1', -1, 1, 'I. Lưu chuyển tiền từ hoạt động kinh doanh', 0, 0),
            $line('101', '1', 2, '1. Tiền thu từ bán hàng, cung cấp dịch vụ và doanh thu khác', 1000, 900),
            $line('102', '1', 2, '2. Tiền chi trả cho người cung cấp hàng hóa và dịch vụ', -400, -350),
            $line('104', '1', 2, '4. Tiền chi trả lãi vay', -50, -40),
            $line('105', '1', 2, '5. Tiền chi nộp thuế thu nhập doanh nghiệp', -30, -20),
            $line('109', '1', 2, 'Lưu chuyển tiền thuần từ hoạt động kinh doanh', 520, 490),
            $line('2', -1, 1, 'II. Lưu chuyển tiền từ hoạt động đầu tư', 0, 0),
            $line('201', '2', 2, '1. Tiền chi để mua sắm, xây dựng TSCĐ và các tài sản dài hạn khác', -200, -100),
            $line('202', '2', 2, '2. Tiền thu từ thanh lý, nhượng bán TSCĐ và các tài sản dài hạn khác', 10, 5),
            $line('208', '2', 2, 'Lưu chuyển tiền thuần từ hoạt động đầu tư', -190, -95),
            $line('3', -1, 1, 'III. Lưu chuyển tiền từ hoạt động tài chính', 0, 0),
            $line('303', '3', 2, '3. Tiền vay ngắn hạn, dài hạn nhận được', 100, 80),
            $line('309', '3', 2, 'Lưu chuyển tiền thuần từ hoạt động tài chính', 100, 80),
        ];
        $cf = new CashFlowStatement();
        $cf->content = json_encode($content);
        return $cf;
    }

    private function balanceStatement(): BalanceStatement
    {
        $line = function ($id, $current, $previous) {
            return [
                'id' => $id, 'name' => "Item $id", 'parentID' => -1, 'expanded' => true,
                'level' => 1, 'field' => '',
                'values' => [
                    ['period' => 'Q1 2026', 'year' => 2026, 'quarter' => 1, 'value' => $current],
                    ['period' => 'Q4 2025', 'year' => 2025, 'quarter' => 4, 'value' => $previous],
                ],
            ];
        };
        $bs = new BalanceStatement();
        $bs->content = json_encode([
            $line('301', 4000, 3800), // Nợ phải trả
            $line('30101', 2500, 2300), // Nợ ngắn hạn
            $line('30102', 1500, 1500), // Nợ dài hạn
            $line('2', 10000, 9500), // Tổng tài sản
        ]);
        return $bs;
    }

    private function incomeStatement(): IncomeStatement
    {
        $line = function ($id, $value) {
            return [
                'id' => $id, 'name' => "Item $id", 'parentID' => -1, 'expanded' => true,
                'level' => 1, 'field' => '',
                'values' => [['period' => 'Q1 2026', 'year' => 2026, 'quarter' => 1, 'value' => $value]],
            ];
        };
        $is = new IncomeStatement();
        $is->content = json_encode([
            $line('3', 5000), // Doanh thu thuần
            $line('19', 300), // LNST
        ]);
        return $is;
    }

    /** FinancialStatement giả lập (không lưu DB) mang đủ 3 statement + year/quarter. */
    private function financialStatement()
    {
        $fs = new \stdClass();
        $fs->year = 2026;
        $fs->quarter = 1;
        $fs->cash_flow_statement = $this->directCashFlowStatement();
        $fs->balance_statement = $this->balanceStatement();
        $fs->income_statement = $this->incomeStatement();
        return $fs;
    }

    public function test_cash_flow_method_detection()
    {
        $this->assertSame('direct', cashFlowMethodOf($this->directCashFlowStatement()));
        $this->assertNull(cashFlowMethodOf(null));
    }

    public function test_cfo_to_revenue_uses_net_operating_total_not_literal_104()
    {
        $calc = new DirectCashFlowCalculator($this->financialStatement());
        $calc->calculateCFOToRevenue(2026, 1);
        // CFO (id 109) = 520; doanh thu = 5000 -> 520/5000*100 = 10.4
        $this->assertSame(10.4, $calc->cFOToRevenue);
    }

    public function test_fcf_to_revenue_and_fcf_to_cfo()
    {
        $calc = new DirectCashFlowCalculator($this->financialStatement());
        // FCF = CFO(520) - |capex_out(-200)| + capex_in(10) = 330
        $calc->calculateFCFToRevenue(2026, 1);
        $this->assertSame(6.6, $calc->fCFToRevenue); // 330/5000*100
        $calc->calculateFCFToCFO(2026, 1);
        $this->assertSame(round(100 * 330 / 520, 2), $calc->fCFToCFO);
    }

    public function test_liability_coverage_ratios_by_cfo()
    {
        $calc = new DirectCashFlowCalculator($this->financialStatement());
        // avg nợ phải trả = (4000+3800)/2 = 3900.
        // Giá trị chính đã quy đổi năm (TTM): fixture chỉ có 2 kỳ (Q1 2026=520, Q4 2025=490)
        // nên TTM = tổng các kỳ có sẵn = 520+490 = 1010 (không phải chỉ riêng quý này).
        $calc->calculateLiabilityCoverageRatioByCFO(2026, 1);
        $this->assertSame(round(1010 / 3900, 4), $calc->liabilityCoverageRatioByCFO);
        // Giá trị riêng-quý (tooltip) vẫn giữ đúng công thức cũ: chỉ CFO của quý đang xét.
        $this->assertSame(round(520 / 3900, 4), $calc->liabilityCoverageRatioByCFOQuarterOnly);
    }

    public function test_external_financing_ratio()
    {
        $calc = new DirectCashFlowCalculator($this->financialStatement());
        // CFF (id 309) = 100; CFO (id 109) = 520 -> 100/520
        $calc->calculateExternalFinancingRatio(2026, 1);
        $this->assertSame(round(100 / 520, 2), $calc->externalFinancingRatio);
    }

    public function test_cash_generating_power_ratio_excludes_net_total_lines()
    {
        $calc = new DirectCashFlowCalculator($this->financialStatement());
        $calc->calculateCashGeneratingPowerRatio(2026, 1);
        // investingInflows (chỉ dòng dương, loại trừ dòng tổng 208) = capex_in(10); financingInflows = 303(100)
        // cfo=520 -> 520/(520+10+100)*100
        $this->assertSame(round(100 * 520 / (520 + 10 + 100), 2), $calc->cashGeneratingPowerRatio);
    }

    public function test_capex_calculator_uses_named_lookup()
    {
        $calc = new DirectCapexCalculator($this->financialStatement());
        // capex = -200 + 10 = -190 (âm => có capex)
        $calc->calculateCfoToCapexRatio(2026, 1);
        $this->assertSame(round(520 / 190, 4), $calc->cfoToCapexRatio);
        $calc->calculateCapexToNetProfitRatio(2026, 1);
        $this->assertSame(round(100 * 190 / 300, 2), $calc->capexToNetProfitRatio);
    }

    public function test_ratios_are_null_when_cash_flow_statement_missing()
    {
        $fs = $this->financialStatement();
        $fs->cash_flow_statement = null;
        $calc = new DirectCashFlowCalculator($fs);
        $calc->calculateCFOToRevenue(2026, 1);
        $this->assertNull($calc->cFOToRevenue);
    }
}
