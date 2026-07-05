<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Pure-function tests for the global period-arithmetic / formatting helpers
 * in app/Functions/kstock_helpers.php (autoloaded via composer "files").
 * No Laravel container needed.
 */
class KstockHelpersTest extends TestCase
{
    public function test_previous_period_of_q1_rolls_back_to_prior_year_q4()
    {
        $this->assertSame(['year' => 2021, 'quarter' => 4], getPreviousPeriod(2022, 1));
    }

    public function test_previous_period_within_year_decrements_quarter()
    {
        $this->assertSame(['year' => 2022, 'quarter' => 2], getPreviousPeriod(2022, 3));
    }

    public function test_previous_period_of_annual_period_rolls_back_a_year()
    {
        // quarter 0 denotes an annual (non-quarterly) period
        $this->assertSame(['year' => 2021, 'quarter' => 0], getPreviousPeriod(2022, 0));
    }

    public function test_previous_period_casts_string_inputs_to_int()
    {
        $this->assertSame(['year' => 2019, 'quarter' => 4], getPreviousPeriod('2020', '1'));
    }

    public function test_last_year_same_period_keeps_quarter_and_decrements_year()
    {
        $this->assertSame(['year' => 2021, 'quarter' => 3], getLastYearSamePeriod(2022, 3));
        $this->assertSame(['year' => 2021, 'quarter' => 0], getLastYearSamePeriod(2022, 0));
    }

    public function test_read_vietnamese_dong_for_human_converts_to_billions()
    {
        $this->assertSame(1.0, readVietnameseDongForHuman(1_000_000_000));
        $this->assertSame(2.5, readVietnameseDongForHuman(2_500_000_000));
    }

    public function test_read_vietnamese_dong_for_human_returns_null_when_rounds_to_zero()
    {
        $this->assertNull(readVietnameseDongForHuman(0));
        $this->assertNull(readVietnameseDongForHuman(4_000_000)); // 0.004B -> rounds to 0.00
    }

    // --- analysisMetricSignal: level-based ---

    public function test_signal_flags_negative_operating_cash_flow_as_bad()
    {
        $s = analysisMetricSignal('CFO/Revenue', [-3.2, 5.0], '%');
        $this->assertSame('bad', $s['tone']);
        $this->assertStringContainsString('Dòng tiền HĐKD âm', $s['label']);
    }

    public function test_signal_flags_weak_liquidity_as_warning()
    {
        $s = analysisMetricSignal('Current Ratio', [0.62, 0.70], 'scalar');
        $this->assertSame('warn', $s['tone']);
    }

    public function test_signal_flags_interest_coverage_below_one_as_bad()
    {
        $this->assertSame('bad', analysisMetricSignal('Interest Coverage Ratio', [0.5, 1.2], 'scalar')['tone']);
    }

    // --- analysisMetricSignal: trend-based (operating effectiveness) ---

    public function test_signal_inventory_period_decreasing_is_good()
    {
        // days, latest 30 < prior 40 => selling faster
        $s = analysisMetricSignal('Average Age of Inventory', [30, 40], 'days');
        $this->assertSame('good', $s['tone']);
        $this->assertStringContainsString('Bán hàng nhanh hơn', $s['label']);
    }

    public function test_signal_payable_duration_increasing_is_good()
    {
        // latest 50 > prior 40 => using supplier capital longer
        $this->assertSame('good', analysisMetricSignal('Average Account Payable Duration', [50, 40], 'days')['tone']);
    }

    public function test_signal_receivable_period_decreasing_is_good()
    {
        $this->assertSame('good', analysisMetricSignal('Average Collection Period', [20, 30], 'days')['tone']);
    }

    public function test_signal_asset_turnover_increasing_is_good()
    {
        $this->assertSame('good', analysisMetricSignal('Total Asset Turnover Ratio', [0.7, 0.6], 'cycles')['tone']);
    }

    public function test_signal_returns_null_for_nonnumeric()
    {
        $this->assertNull(analysisMetricSignal('Current Ratio', ['', ''], 'scalar'));
    }

    // --- extended signal: profitability / growth / leverage ---

    public function test_signal_negative_profitability_is_bad()
    {
        $this->assertSame('bad', analysisMetricSignal('ROE', [-3.0, 2.0], '%')['tone']);
    }

    public function test_signal_rising_profitability_is_good()
    {
        $this->assertSame('good', analysisMetricSignal('ROE', [12.0, 9.0], '%')['tone']);
    }

    public function test_signal_high_leverage_thresholds()
    {
        $this->assertSame('bad',  analysisMetricSignal('Debts/Equities', [2.5, 2.4], 'scalar')['tone']);
        $this->assertSame('warn', analysisMetricSignal('Debts/Equities', [1.5, 1.4], 'scalar')['tone']);
        $this->assertSame('good', analysisMetricSignal('Debts/Equities', [0.6, 0.6], 'scalar')['tone']); // ≤ 1 => đòn bẩy an toàn
    }

    public function test_signal_liabilities_over_assets_percent_thresholds()
    {
        $this->assertSame('bad',  analysisMetricSignal('Total Liabilities/Total Assets', [86.0, 80.0], '%')['tone']);
        $this->assertSame('warn', analysisMetricSignal('Total Liabilities/Total Assets', [72.0, 70.0], '%')['tone']);
    }

    public function test_signal_negative_revenue_growth_is_warning()
    {
        $this->assertSame('warn', analysisMetricSignal('Revenue Growth YoY', [-8.0, 10.0], '%')['tone']);
    }

    // --- extended signal: liquidity / cash flow / capex / Buffett thresholds ---

    public function test_signal_liquidity_thresholds()
    {
        $this->assertSame('good', analysisMetricSignal('Current Ratio', [1.2], 'scalar')['tone']);
        $this->assertSame('warn', analysisMetricSignal('Current Ratio', [0.6], 'scalar')['tone']);
        $this->assertSame('good', analysisMetricSignal('Quick Ratio 1', [0.6], 'scalar')['tone']);
        $this->assertSame('warn', analysisMetricSignal('Quick Ratio 1', [0.4], 'scalar')['tone']);
        $this->assertSame('good', analysisMetricSignal('Overall Solvency Ratio', [1.5], 'scalar')['tone']);
        $this->assertSame('bad',  analysisMetricSignal('Overall Solvency Ratio', [0.8], 'scalar')['tone']);
        // Cash Ratio / Quick Ratio 2: đã bổ sung ngưỡng (audit "review lại các nhóm chỉ số còn thiếu badge").
        $this->assertSame('warn', analysisMetricSignal('Cash Ratio', [0.2], 'scalar')['tone']);
        $this->assertSame('good', analysisMetricSignal('Cash Ratio', [0.6], 'scalar')['tone']);
        $this->assertSame('warn', analysisMetricSignal('Quick Ratio 2', [0.2], 'scalar')['tone']);
        $this->assertSame('good', analysisMetricSignal('Quick Ratio 2', [0.3], 'scalar')['tone']);
    }

    public function test_signal_cashflow_coverage_thresholds()
    {
        $this->assertSame('good', analysisMetricSignal('Liability Coverage Ratio By CFO', [1.2], 'scalar')['tone']);
        $this->assertSame('warn', analysisMetricSignal('Current Liability Coverage Ratio By FCF', [0.5], 'scalar')['tone']);
        $this->assertSame('bad',  analysisMetricSignal('Long-term Liability Coverage Ratio By CFO', [-0.1], 'scalar')['tone']);
        $this->assertSame('good', analysisMetricSignal('Cash Generating Power Ratio', [20.0], '%')['tone']);
        $this->assertSame('good', analysisMetricSignal('External Financing Ratio', [-0.1], 'scalar')['tone']);
        $this->assertSame('warn', analysisMetricSignal('External Financing Ratio', [0.5], 'scalar')['tone']);
    }

    public function test_signal_capex_thresholds()
    {
        $this->assertSame('good', analysisMetricSignal('CFO/CAPEX', [1.5], 'scalar')['tone']);
        $this->assertSame('warn', analysisMetricSignal('CFO/CAPEX', [0.5], 'scalar')['tone']);
        $this->assertSame('good', analysisMetricSignal('CAPEX/NetProfit', [20.0], '%')['tone']);
        $this->assertSame('warn', analysisMetricSignal('CAPEX/NetProfit', [60.0], '%')['tone']);
    }

    public function test_signal_buffett_cost_structure_thresholds()
    {
        $this->assertSame('good', analysisMetricSignal('Gross profit margin', [45.0], '%')['tone']);
        $this->assertSame('warn', analysisMetricSignal('Gross profit margin', [15.0], '%')['tone']);
        $this->assertSame('good', analysisMetricSignal('Selling and Enterprise Management Expenses/Gross Profit', [25.0], '%')['tone']);
        $this->assertSame('good', analysisMetricSignal('Selling and Enterprise Management Expenses/Gross Profit', [50.0], '%')['tone']); // 30–80% chấp nhận được
        $this->assertSame('warn', analysisMetricSignal('Selling and Enterprise Management Expenses/Gross Profit', [90.0], '%')['tone']);
        $this->assertSame('bad',  analysisMetricSignal('Selling and Enterprise Management Expenses/Gross Profit', [110.0], '%')['tone']);
        // Cogs/Revenue = 100% − biên gộp -> theo ngưỡng Buffett
        $this->assertSame('good', analysisMetricSignal('Cogs/Revenue', [55.0], '%')['tone']);
        $this->assertSame('warn', analysisMetricSignal('Cogs/Revenue', [85.0], '%')['tone']);
        $this->assertNull(analysisMetricSignal('Cogs/Revenue', [70.0], '%')); // vùng giữa: không kết luận
    }

    public function test_signal_absolute_profitability_thresholds()
    {
        // Mức tuyệt đối mạnh -> good bất kể xu hướng (1 kỳ, không có delta).
        $this->assertSame('good', analysisMetricSignal('ROE', [18.0], '%')['tone']);
        $this->assertSame('good', analysisMetricSignal('ROA', [9.0], '%')['tone']);
        $this->assertSame('good', analysisMetricSignal('ROS', [12.0], '%')['tone']);
        $this->assertSame('good', analysisMetricSignal('ROCE', [16.0], '%')['tone']);
        $this->assertSame('bad',  analysisMetricSignal('ROA', [-2.0], '%')['tone']);
        // Vùng giữa không có xu hướng -> không kết luận.
        $this->assertNull(analysisMetricSignal('ROE', [9.0], '%'));
    }

    public function test_signal_extra_leverage_and_cycle_thresholds()
    {
        $this->assertSame('good', analysisMetricSignal('Net Debts/Equities', [-0.5], 'scalar')['tone']); // net cash
        $this->assertSame('warn', analysisMetricSignal('Net Debts/Equities', [1.5], 'scalar')['tone']);
        $this->assertSame('bad',  analysisMetricSignal('Net Debts/Equities', [2.5], 'scalar')['tone']);
        $this->assertSame('good', analysisMetricSignal('Cash Conversion Cycle', [-10.0], 'days')['tone']);
        $this->assertSame('good', analysisMetricSignal('Interest cost/Revenue', [2.0], '%')['tone']);
        $this->assertSame('warn', analysisMetricSignal('Interest cost/Revenue', [12.0], '%')['tone']);
    }

    public function test_signal_new_leverage_and_quality_of_earnings_thresholds()
    {
        // Total Debts/Total Assets (nợ vay chịu lãi / tổng tài sản, %)
        $this->assertSame('good', analysisMetricSignal('Total Debts/Total Assets', [20.0], '%')['tone']);
        $this->assertSame('warn', analysisMetricSignal('Total Debts/Total Assets', [40.0], '%')['tone']);
        $this->assertSame('bad',  analysisMetricSignal('Total Debts/Total Assets', [60.0], '%')['tone']);
        // Long Term Debts/Equities
        $this->assertSame('good', analysisMetricSignal('Long Term Debts/Equities', [0.3], 'scalar')['tone']);
        $this->assertSame('warn', analysisMetricSignal('Long Term Debts/Equities', [0.7], 'scalar')['tone']);
        $this->assertSame('bad',  analysisMetricSignal('Long Term Debts/Equities', [1.5], 'scalar')['tone']);
        // Total Assets/Equities & Average Total Assets/Average Equities (equity multiplier)
        $this->assertSame('good', analysisMetricSignal('Total Assets/Equities', [1.8], 'scalar')['tone']);
        $this->assertSame('warn', analysisMetricSignal('Total Assets/Equities', [2.5], 'scalar')['tone']);
        $this->assertSame('bad',  analysisMetricSignal('Total Assets/Equities', [4.0], 'scalar')['tone']);
        $this->assertSame('good', analysisMetricSignal('Average Total Assets/Average Equities', [1.8], 'scalar')['tone']);
        // Operating Profit/EBT (chất lượng lợi nhuận, %)
        $this->assertSame('bad',  analysisMetricSignal('Operating Profit/EBT', [-20.0], '%')['tone']);
        $this->assertSame('warn', analysisMetricSignal('Operating Profit/EBT', [30.0], '%')['tone']);
        $this->assertSame('good', analysisMetricSignal('Operating Profit/EBT', [90.0], '%')['tone']);
    }

    // --- business sector: manufacturing vs non-manufacturing (Altman Z selection) ---

    public function test_business_sector_class_from_icb_industry_code()
    {
        // Sản xuất: vật liệu cơ bản (1), hàng tiêu dùng (3)
        $this->assertSame('manufacturing', businessSectorClass('1750')); // thép (HPG)
        $this->assertSame('manufacturing', businessSectorClass('3570')); // thực phẩm (VNM)
        // Phi sản xuất: dịch vụ tiêu dùng (5), công nghệ phần mềm (9)
        $this->assertSame('non_manufacturing', businessSectorClass('5750')); // hàng không (HVN)
        $this->assertSame('non_manufacturing', businessSectorClass('9530')); // phần mềm (FPT)
        // Override ngành hỗn hợp
        $this->assertSame('manufacturing', businessSectorClass('9570'));     // phần cứng CNTT
        $this->assertSame('manufacturing', businessSectorClass('4570'));     // dược phẩm
        $this->assertSame('non_manufacturing', businessSectorClass('2350')); // xây dựng
        $this->assertSame('non_manufacturing', businessSectorClass('2770')); // vận tải công nghiệp
        // Tài chính
        $this->assertSame('financial', businessSectorClass('8350')); // ngân hàng (TCB)
        // Bất động sản (ICB 86xx) thuộc nhóm 8 nhưng là DN vận hành -> phi sản xuất
        $this->assertSame('non_manufacturing', businessSectorClass('8630')); // BĐS (VIC/VHM/VRE)
        // Thiếu dữ liệu
        $this->assertNull(businessSectorClass(''));
        $this->assertNull(businessSectorClass(null));
    }

    public function test_business_sector_z_alias_and_label()
    {
        $this->assertSame('Z-Score', businessSectorZAlias('manufacturing'));
        $this->assertSame('Z2-Score', businessSectorZAlias('non_manufacturing'));
        $this->assertNull(businessSectorZAlias('financial'));
        $this->assertSame('Doanh nghiệp sản xuất', businessSectorLabel('manufacturing'));
        $this->assertSame('Doanh nghiệp phi sản xuất', businessSectorLabel('non_manufacturing'));
        $this->assertNull(businessSectorLabel(null));
    }

    public function test_score_caption()
    {
        $this->assertStringContainsString('2.99', analysisScoreCaption('Z-Score'));
        $this->assertStringContainsString('-1.78', analysisScoreCaption('M8-Score'));
        $this->assertNull(analysisScoreCaption('ROE'));
    }

    // --- quarterOnlyNote(): ghi chú tooltip cho giá trị riêng-quý (audit fix #1) ---

    public function test_quarter_only_note_returns_null_for_annual_report()
    {
        $this->assertNull(quarterOnlyNote(6.64, '%', 0));
    }

    public function test_quarter_only_note_returns_null_when_value_missing()
    {
        $this->assertNull(quarterOnlyNote(null, '%', 1));
    }

    public function test_quarter_only_note_formats_value_for_quarterly_report()
    {
        $note = quarterOnlyNote(6.64, '%', 1);
        $this->assertStringContainsString('6.64%', $note);
        $this->assertStringContainsString('Riêng quý này', $note);
    }

    // --- negativeEquityAlert() / negativeCfoAlert() / oppositeSignAlert(): banner cảnh báo
    // các tỷ số mất ý nghĩa khi 1 thành phần đổi dấu (audit "invalidity") ---

    public function test_negative_equity_alert_fires_only_when_equity_negative()
    {
        $this->assertNull(negativeEquityAlert(100));
        $this->assertNull(negativeEquityAlert(0));
        $this->assertNull(negativeEquityAlert(null));
        $alert = negativeEquityAlert(-50);
        $this->assertNotNull($alert);
        $this->assertStringContainsString('VCSH', $alert);
        $this->assertStringContainsString('âm', $alert);
    }

    public function test_negative_cfo_alert_fires_only_when_cfo_negative()
    {
        $this->assertNull(negativeCfoAlert(100));
        $this->assertNull(negativeCfoAlert(0));
        $this->assertNull(negativeCfoAlert(null));
        $alert = negativeCfoAlert(-30);
        $this->assertNotNull($alert);
        $this->assertStringContainsString('CFO', $alert);
        $this->assertStringContainsString('âm', $alert);
    }

    public function test_opposite_sign_alert_fires_only_when_numerator_and_denominator_disagree()
    {
        $msg = 'trái dấu';
        $this->assertNull(oppositeSignAlert(10, 20, $msg));
        $this->assertNull(oppositeSignAlert(-10, -20, $msg));
        $this->assertNull(oppositeSignAlert(null, 20, $msg));
        $this->assertSame($msg, oppositeSignAlert(10, -20, $msg));
        $this->assertSame($msg, oppositeSignAlert(-10, 20, $msg));
    }
}
