<?php

namespace Tests\Unit;

use App\Jobs\Financials\Calculators\FinancialLeverageCalculator;
use App\Jobs\Financials\Calculators\ProfitabilityCalculator;
use App\Models\BalanceStatement;
use App\Models\IncomeStatement;
use Tests\TestCase;

/**
 * Audit fix #1 (TTM cho tỷ số flow/stock ở báo cáo quý) + #2 (Net Debt trừ tiền mặt).
 * Trước đây ROAA/ROA/ROE/ROEA/ROCE/ROTA dùng LNST của MỘT quý chia cho tài sản/VCSH bình
 * quân (một "stock") — hiểu ngầm là chỉ số hàng năm nhưng thực ra chỉ phản ánh 1/4 năm.
 * Đã kiểm chứng thực tế: HPG ROEA hiện 6.64% (sai) lẽ ra phải ~15.5% (đúng, TTM).
 */
class ProfitabilityTtmTest extends TestCase
{
    private function incomeStatement(): IncomeStatement
    {
        // LNST cổ đông mẹ (item 21): 4 quý liên tiếp, mỗi quý 100 -> TTM = 400.
        $line = function ($id) {
            return [
                'id' => $id, 'name' => "Item $id", 'parentID' => -1, 'expanded' => true,
                'level' => 1, 'field' => '',
                'values' => [
                    ['period' => 'Q1 2026', 'year' => 2026, 'quarter' => 1, 'value' => 100],
                    ['period' => 'Q4 2025', 'year' => 2025, 'quarter' => 4, 'value' => 100],
                    ['period' => 'Q3 2025', 'year' => 2025, 'quarter' => 3, 'value' => 100],
                    ['period' => 'Q2 2025', 'year' => 2025, 'quarter' => 2, 'value' => 100],
                    ['period' => 'Q1 2025', 'year' => 2025, 'quarter' => 1, 'value' => 90],
                ],
            ];
        };
        $is = new IncomeStatement();
        $is->content = json_encode([$line('21')]);
        return $is;
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
        $bs->content = json_encode([$line('302', 2000, 1800)]); // VCSH
        return $bs;
    }

    private function financialStatement($quarter = 1)
    {
        $fs = new \stdClass();
        $fs->year = 2026;
        $fs->quarter = $quarter;
        $fs->income_statement = $this->incomeStatement();
        $fs->balance_statement = $this->balanceStatement();
        return $fs;
    }

    public function test_roea_uses_ttm_net_profit_for_quarterly_report()
    {
        $calc = new ProfitabilityCalculator($this->financialStatement(1));
        $calc->calculateROEA(2026, 1);
        // avg VCSH = (2000+1800)/2 = 1900; TTM LNST = 100*4 = 400 -> 400/1900*100 = 21.05%
        $this->assertSame(round(100 * 400 / 1900, 2), $calc->roea);
        // Riêng quý (tooltip): 100/1900*100 = 5.26% — khác hẳn giá trị TTM.
        $this->assertSame(round(100 * 100 / 1900, 2), $calc->roeaQuarterOnly);
        $this->assertNotEquals($calc->roea, $calc->roeaQuarterOnly);
    }

    public function test_roea_annual_report_ttm_equals_quarter_only()
    {
        // Báo cáo năm (quarter=0): ttmOrAnnual() dùng nhánh getValue (không lũy kế) nên
        // giá trị chính phải BẰNG giá trị riêng-kỳ — không có khái niệm "TTM" cho báo cáo năm.
        $line = fn ($id, $current, $previous) => [
            'id' => $id, 'name' => "Item $id", 'parentID' => -1, 'expanded' => true,
            'level' => 1, 'field' => '',
            'values' => [
                ['period' => '2026', 'year' => 2026, 'quarter' => 0, 'value' => $current],
                ['period' => '2025', 'year' => 2025, 'quarter' => 0, 'value' => $previous],
            ],
        ];
        $is = new IncomeStatement();
        $is->content = json_encode([$line('21', 400, 350)]);
        $bs = new BalanceStatement();
        $bs->content = json_encode([$line('302', 2000, 1800)]);
        $fs = new \stdClass();
        $fs->year = 2026;
        $fs->quarter = 0;
        $fs->income_statement = $is;
        $fs->balance_statement = $bs;

        $calc = new ProfitabilityCalculator($fs);
        $calc->calculateROEA(2026, 0);
        $this->assertNotNull($calc->roea);
        $this->assertSame($calc->roea, $calc->roeaQuarterOnly);
    }

    public function test_net_debt_to_equity_subtracts_cash()
    {
        $bsLine = function ($id, $value) {
            return [
                'id' => $id, 'name' => "Item $id", 'parentID' => -1, 'expanded' => true,
                'level' => 1, 'field' => '',
                'values' => [['period' => 'Q1 2026', 'year' => 2026, 'quarter' => 1, 'value' => $value]],
            ];
        };
        $bs = new BalanceStatement();
        $bs->content = json_encode([
            $bsLine('302', 2000),     // VCSH
            $bsLine('3010101', 500),  // Nợ vay ngắn hạn
            $bsLine('3010206', 300),  // Nợ vay dài hạn
            $bsLine('10101', 400),    // Tiền và tương đương tiền
            $bsLine('10102', 50),     // Đầu tư TC ngắn hạn
            $bsLine('10205', 0),      // Đầu tư TC dài hạn
        ]);
        $fs = new \stdClass();
        $fs->year = 2026;
        $fs->quarter = 1;
        $fs->balance_statement = $bs;

        $calc = new FinancialLeverageCalculator($fs);
        $calc->calculateNetDebtToEquityRatio(2026, 1);
        // Net Debt = 500+300-400-50-0 = 350 (đã trừ tiền mặt) -> 350/2000 = 0.175
        $this->assertSame(round(350 / 2000, 4), $calc->netDebtToEquityRatio);
        // Trước đây (không trừ tiền): (500+300-50)/2000 = 0.375 — sai, KHÔNG được bằng giá trị này.
        $this->assertNotEquals(round(750 / 2000, 4), $calc->netDebtToEquityRatio);
    }
}
