<?php

namespace Tests\Unit;

use App\Jobs\Financials\Calculators\CapexCalculator;
use App\Jobs\Financials\Calculators\CashFlowCalculator;
use App\Jobs\Financials\Calculators\CostStructureCalculator;
use App\Jobs\Financials\Calculators\FinancialLeverageCalculator;
use App\Jobs\Financials\Calculators\ProfitabilityCalculator;
use App\Jobs\Financials\Calculators\ProfitStructureCalculator;
use App\Models\BalanceStatement;
use App\Models\CashFlowStatement;
use App\Models\IncomeStatement;
use Tests\TestCase;

/**
 * Audit: các chỉ số "vô nghĩa" khi 1 thành phần đổi dấu dù vẫn tính ra số (vd VCSH âm làm
 * ROE/ROEA/Net Debt-Equity dương giả tạo, CFO âm làm FCF/CFO mất ý nghĩa...). Các test này
 * xác nhận từng calculator đã lộ (expose) đúng biến gốc dùng cho banner cảnh báo
 * (negativeEquityAlert/negativeCfoAlert/oppositeSignAlert ở kstock_helpers.php), tách biệt
 * với việc Writer có gắn 'alert' vào entry hay không (đã kiểm tra thủ công qua recompute).
 */
class InvalidityAlertTest extends TestCase
{
    private function line($id, $value)
    {
        return [
            'id' => $id, 'name' => "Item $id", 'parentID' => -1, 'expanded' => true,
            'level' => 1, 'field' => '',
            'values' => [['period' => 'Q1 2026', 'year' => 2026, 'quarter' => 1, 'value' => $value]],
        ];
    }

    public function test_roe_exposes_negative_equity_used()
    {
        $is = new IncomeStatement();
        $is->content = json_encode([$this->line('21', -20)]); // lỗ ròng
        $bs = new BalanceStatement();
        $bs->content = json_encode([$this->line('302', -50)]); // VCSH âm

        $fs = new \stdClass();
        $fs->year = 2026;
        $fs->quarter = 1;
        $fs->income_statement = $is;
        $fs->balance_statement = $bs;

        $calc = new ProfitabilityCalculator($fs);
        $calc->calculateROE(2026, 1);

        $this->assertSame(-50.0, $calc->equityUsedForROE);
        // Lỗ / VCSH âm = ROE dương "trông bình thường" -> chính là case cần banner.
        $this->assertGreaterThan(0, $calc->roe);
        $this->assertNotNull(negativeEquityAlert($calc->equityUsedForROE));
    }

    public function test_roe_exposes_positive_equity_used_no_alert()
    {
        $is = new IncomeStatement();
        $is->content = json_encode([$this->line('21', 20)]);
        $bs = new BalanceStatement();
        $bs->content = json_encode([$this->line('302', 200)]);

        $fs = new \stdClass();
        $fs->year = 2026;
        $fs->quarter = 1;
        $fs->income_statement = $is;
        $fs->balance_statement = $bs;

        $calc = new ProfitabilityCalculator($fs);
        $calc->calculateROE(2026, 1);

        $this->assertSame(200.0, $calc->equityUsedForROE);
        $this->assertNull(negativeEquityAlert($calc->equityUsedForROE));
    }

    public function test_net_debt_to_equity_exposes_negative_equity_used()
    {
        $bs = new BalanceStatement();
        $bs->content = json_encode([
            $this->line('302', -100),    // VCSH âm
            $this->line('3010101', 0),
            $this->line('3010206', 0),
            $this->line('10101', 500),   // tiền ròng dương -> Net Debt âm
            $this->line('10102', 0),
            $this->line('10205', 0),
        ]);
        $fs = new \stdClass();
        $fs->year = 2026;
        $fs->quarter = 1;
        $fs->balance_statement = $bs;

        $calc = new FinancialLeverageCalculator($fs);
        $calc->calculateNetDebtToEquityRatio(2026, 1);

        $this->assertSame(-100.0, $calc->equityUsedForNetDebtToEquity);
        // Net Debt = 0+0-500 = -500 (âm); âm/âm = dương "trông bình thường" dù VCSH âm.
        $this->assertGreaterThan(0, $calc->netDebtToEquityRatio);
        $this->assertNotNull(negativeEquityAlert($calc->equityUsedForNetDebtToEquity));
    }

    public function test_average_total_asset_to_average_equity_negative_when_equity_negative()
    {
        // Đây chính là biến đại diện DupontWriter dùng để phát hiện VCSH bq âm
        // (averageFinancialLeverage = AvgAssets/AvgEquity, AvgAssets luôn >= 0).
        $bs = new BalanceStatement();
        $bs->content = json_encode([
            [
                'id' => '2', 'name' => 'Item 2', 'parentID' => -1, 'expanded' => true,
                'level' => 1, 'field' => '',
                'values' => [
                    ['period' => 'Q1 2026', 'year' => 2026, 'quarter' => 1, 'value' => 1000],
                    ['period' => 'Q4 2025', 'year' => 2025, 'quarter' => 4, 'value' => 1000],
                ],
            ],
            [
                'id' => '302', 'name' => 'Item 302', 'parentID' => -1, 'expanded' => true,
                'level' => 1, 'field' => '',
                'values' => [
                    ['period' => 'Q1 2026', 'year' => 2026, 'quarter' => 1, 'value' => -50],
                    ['period' => 'Q4 2025', 'year' => 2025, 'quarter' => 4, 'value' => -30],
                ],
            ],
        ]);
        $fs = new \stdClass();
        $fs->year = 2026;
        $fs->quarter = 1;
        $fs->balance_statement = $bs;

        $calc = new FinancialLeverageCalculator($fs);
        $calc->calculateAverageTotalAssetToAverageEquityRatio(2026, 1);

        $this->assertLessThan(0, $calc->averageTotalAssetToAverageEquityRatio);
        $this->assertLessThan(0, $calc->averageEquityUsedForAverageTotalAssetToAverageEquity);
    }

    public function test_capex_to_net_profit_still_computes_with_net_loss_but_exposes_it_for_alert()
    {
        $is = new IncomeStatement();
        $is->content = json_encode([$this->line('19', -20)]); // lỗ ròng
        $cf = new CashFlowStatement();
        $cf->content = json_encode([$this->line('201', -50), $this->line('202', 0)]); // capex chi ra

        $fs = new \stdClass();
        $fs->year = 2026;
        $fs->quarter = 1;
        $fs->income_statement = $is;
        $fs->cash_flow_statement = $cf;

        $calc = new CapexCalculator($fs);
        $calc->calculateCapexToNetProfitRatio(2026, 1);

        $this->assertSame(-20.0, $calc->netProfitUsedForCapexToNetProfit);
        // 100*abs(-50)/-20 = -250 : vẫn tính ra số (không bị chặn), chỉ cần banner cảnh báo.
        $this->assertSame(-250.0, $calc->capexToNetProfitRatio);
    }

    public function test_capex_to_net_profit_does_not_divide_by_zero_net_profit()
    {
        $is = new IncomeStatement();
        $is->content = json_encode([$this->line('19', 0)]);
        $cf = new CashFlowStatement();
        $cf->content = json_encode([$this->line('201', -50), $this->line('202', 0)]);

        $fs = new \stdClass();
        $fs->year = 2026;
        $fs->quarter = 1;
        $fs->income_statement = $is;
        $fs->cash_flow_statement = $cf;

        $calc = new CapexCalculator($fs);
        $calc->calculateCapexToNetProfitRatio(2026, 1);

        // Trước đây thiếu guard net_profit != 0 -> có thể ra INF/chia 0; giờ phải là null.
        $this->assertNull($calc->capexToNetProfitRatio);
    }

    public function test_fcf_to_cfo_exposes_negative_cfo_used()
    {
        $cf = new CashFlowStatement();
        $cf->content = json_encode([
            $this->line('104', -100), // CFO âm (đốt tiền HĐKD)
            $this->line('201', -20),
            $this->line('202', 0),
        ]);
        $fs = new \stdClass();
        $fs->year = 2026;
        $fs->quarter = 1;
        $fs->cash_flow_statement = $cf;

        $calc = new CashFlowCalculator($fs);
        $calc->calculateFCFToCFO(2026, 1);

        $this->assertSame(-100.0, $calc->cfoUsedForFCFToCFO);
        $this->assertNotNull(negativeCfoAlert($calc->cfoUsedForFCFToCFO));
        // FCF = -100-20+0 = -120; -120/-100*100 = 120% -> trông như chuyển đổi tốt dù CFO âm.
        $this->assertSame(120.0, $calc->fCFToCFO);
    }

    public function test_liability_coverage_by_fcf_exposes_negative_cfo_used()
    {
        $cf = new CashFlowStatement();
        $cf->content = json_encode([
            $this->line('104', -50),
            $this->line('201', 0),
            $this->line('202', 300), // thanh lý TSCĐ lớn -> FCF dương dù CFO âm
        ]);
        $bs = new BalanceStatement();
        $bs->content = json_encode([$this->line('301', 1000)]);
        $fs = new \stdClass();
        $fs->year = 2026;
        $fs->quarter = 1;
        $fs->cash_flow_statement = $cf;
        $fs->balance_statement = $bs;

        $calc = new CashFlowCalculator($fs);
        $calc->calculateLiabilityCoverageRatioByFCF(2026, 1);

        $this->assertSame(-50.0, $calc->cfoUsedForFCF);
        $this->assertNotNull(negativeCfoAlert($calc->cfoUsedForFCF));
        // FCF = -50-0+300 = 250 (dương, do thanh lý TSCĐ) -> hệ số trang trải nợ trông tốt.
        $this->assertGreaterThan(0, $calc->liabilityCoverageRatioByFCF);
    }

    public function test_operating_profit_to_ebt_exposes_opposite_sign_components()
    {
        $is = new IncomeStatement();
        // HĐKD lãi (11) nhưng LNTT lỗ (15) do khoản bất thường ngoài HĐKD.
        $is->content = json_encode([$this->line('11', 80), $this->line('15', -30)]);

        $fs = new \stdClass();
        $fs->year = 2026;
        $fs->quarter = 1;
        $fs->income_statement = $is;

        $calc = new ProfitStructureCalculator($fs);
        $calc->calculateOperatingProfitToEBTRatio(2026, 1);

        $this->assertSame(80.0, $calc->operatingProfitUsedForOperatingProfitToEBT);
        $this->assertSame(-30.0, $calc->eBTUsedForOperatingProfitToEBT);
        $this->assertNotNull(oppositeSignAlert(
            $calc->operatingProfitUsedForOperatingProfitToEBT,
            $calc->eBTUsedForOperatingProfitToEBT,
            'trái dấu'
        ));
    }

    public function test_selling_and_enterprise_management_to_gross_profit_exposes_negative_gross_profit()
    {
        $is = new IncomeStatement();
        $is->content = json_encode([$this->line('9', 10), $this->line('10', 5), $this->line('5', -40)]);

        $fs = new \stdClass();
        $fs->year = 2026;
        $fs->quarter = 1;
        $fs->income_statement = $is;

        $calc = new CostStructureCalculator($fs);
        $calc->calculateSellingAndEnperpriseManagementToGrossProfitRatio(2026, 1);

        $this->assertSame(-40.0, $calc->grossProfitUsedForSellingAndEnperpriseManagementToGrossProfit);
        $this->assertLessThan(0, $calc->grossProfitUsedForSellingAndEnperpriseManagementToGrossProfit);
    }
}
