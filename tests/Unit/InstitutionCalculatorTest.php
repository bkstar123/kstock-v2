<?php

namespace Tests\Unit;

use App\Jobs\Financials\Calculators\InstitutionCalculator;
use App\Models\BalanceStatement;
use App\Models\IncomeStatement;
use Tests\TestCase;

/**
 * InstitutionCalculator (ngân hàng/chứng khoán/bảo hiểm): kiến trúc data-driven
 * (definitions($type) trả về closure 'fn'). Audit trước đó phát hiện: các chỉ số flow/stock
 * (vd BANK_ROAA/NIM, SEC_ROAA, INS_ROAA...) ĐÃ tính đúng TTM (lũy kế 4 quý) qua isTTM()/
 * sumTTM(), nhưng InstitutionWriter chưa từng gắn badge 'ttm'/tooltip 'valueNote' như các
 * Calculator thường — nên trên UI trông như "không dùng TTM". Test này xác nhận:
 * 1) closure 'fn' nhận tham số thứ 3 $ttm để trả về bản TTM (true) hoặc riêng-quý (false);
 * 2) 8 chỉ số mới (bank/securities/insurance) tính đúng theo công thức đã xác minh với dữ
 *    liệu thật (TCB/SSI/BVH).
 */
class InstitutionCalculatorTest extends TestCase
{
    /** Kỳ trước = kỳ liền trước (getPreviousPeriod) — dùng cho bsAvg()/avgSumBs(). */
    private function line($id, $current, $previous = null)
    {
        $values = [['period' => 'Q1 2026', 'year' => 2026, 'quarter' => 1, 'value' => $current]];
        if ($previous !== null) {
            $values[] = ['period' => 'Q4 2025', 'year' => 2025, 'quarter' => 4, 'value' => $previous];
        }
        return [
            'id' => $id, 'name' => "Item $id", 'parentID' => -1, 'expanded' => true,
            'level' => 1, 'field' => '', 'values' => $values,
        ];
    }

    /** Kỳ trước = cùng quý năm trước ($y-1, cùng $q) — dùng cho yoyBs(). */
    private function lineYoY($id, $current, $previousYear)
    {
        return [
            'id' => $id, 'name' => "Item $id", 'parentID' => -1, 'expanded' => true,
            'level' => 1, 'field' => '',
            'values' => [
                ['period' => 'Q1 2026', 'year' => 2026, 'quarter' => 1, 'value' => $current],
                ['period' => 'Q1 2025', 'year' => 2025, 'quarter' => 1, 'value' => $previousYear],
            ],
        ];
    }

    /** LNST ngân hàng (item '13'): 4 quý liên tiếp @100 (TTM=400) + quý cũ hơn @90. */
    private function bankIncomeStatement(): IncomeStatement
    {
        $flow = fn ($id, $v) => [
            'id' => $id, 'name' => "Item $id", 'parentID' => -1, 'expanded' => true,
            'level' => 1, 'field' => '',
            'values' => [
                ['period' => 'Q1 2026', 'year' => 2026, 'quarter' => 1, 'value' => $v],
                ['period' => 'Q4 2025', 'year' => 2025, 'quarter' => 4, 'value' => $v],
                ['period' => 'Q3 2025', 'year' => 2025, 'quarter' => 3, 'value' => $v],
                ['period' => 'Q2 2025', 'year' => 2025, 'quarter' => 2, 'value' => $v],
                ['period' => 'Q1 2025', 'year' => 2025, 'quarter' => 1, 'value' => $v - 10],
            ],
        ];
        $is = new IncomeStatement();
        $is->content = json_encode([$flow('13', 100)]);
        return $is;
    }

    public function test_bank_roaa_definition_supports_ttm_and_quarter_only_via_third_param()
    {
        $bs = new BalanceStatement();
        $bs->content = json_encode([$this->line('2', 2000, 1800)]); // Tổng tài sản
        $fs = new \stdClass();
        $fs->year = 2026;
        $fs->quarter = 1;
        $fs->income_statement = $this->bankIncomeStatement();
        $fs->balance_statement = $bs;

        $calc = new InstitutionCalculator($fs);
        $def = collect($calc->definitions(1))->firstWhere('alias', 'BANK_ROAA');
        $this->assertNotNull($def);
        $this->assertTrue($def['usesTtm']);

        $ttmValue = ($def['fn'])(2026, 1, true);
        $quarterOnlyValue = ($def['fn'])(2026, 1, false);
        // avg assets = (2000+1800)/2 = 1900; TTM LNST = 100*4 = 400 -> 400/1900*100
        $this->assertEqualsWithDelta(round(100 * 400 / 1900, 4), $ttmValue, 0.0001);
        // riêng quý: 100/1900*100
        $this->assertEqualsWithDelta(round(100 * 100 / 1900, 4), $quarterOnlyValue, 0.0001);
        $this->assertNotEquals($ttmValue, $quarterOnlyValue);
    }

    public function test_bank_roaa_annual_report_ttm_equals_quarter_only()
    {
        $flow = fn ($id, $v, $prev) => [
            'id' => $id, 'name' => "Item $id", 'parentID' => -1, 'expanded' => true,
            'level' => 1, 'field' => '',
            'values' => [
                ['period' => '2026', 'year' => 2026, 'quarter' => 0, 'value' => $v],
                ['period' => '2025', 'year' => 2025, 'quarter' => 0, 'value' => $prev],
            ],
        ];
        $is = new IncomeStatement();
        $is->content = json_encode([$flow('13', 400, 350)]);
        $bs = new BalanceStatement();
        $bs->content = json_encode([$flow('2', 2000, 1800)]);
        $fs = new \stdClass();
        $fs->year = 2026;
        $fs->quarter = 0;
        $fs->income_statement = $is;
        $fs->balance_statement = $bs;

        $calc = new InstitutionCalculator($fs);
        $def = collect($calc->definitions(1))->firstWhere('alias', 'BANK_ROAA');
        $ttmValue = ($def['fn'])(2026, 0, true);
        $quarterOnlyValue = ($def['fn'])(2026, 0, false);
        $this->assertNotNull($ttmValue);
        $this->assertSame($ttmValue, $quarterOnlyValue);
    }

    public function test_pure_balance_sheet_ratios_do_not_flag_usestm()
    {
        $fs = new \stdClass();
        $fs->year = 2026;
        $fs->quarter = 1;
        $calc = new InstitutionCalculator($fs);
        foreach (['BANK_LDR', 'BANK_ETA', 'BANK_LEV'] as $alias) {
            $def = collect($calc->definitions(1))->firstWhere('alias', $alias);
            $this->assertArrayNotHasKey('usesTtm', $def, "$alias should not be flagged usesTtm");
        }
        foreach (['SEC_CURRENT', 'SEC_ETA', 'SEC_LEV', 'SEC_MARGIN_LEVERAGE'] as $alias) {
            $def = collect($calc->definitions(2))->firstWhere('alias', $alias);
            $this->assertArrayNotHasKey('usesTtm', $def, "$alias should not be flagged usesTtm");
        }
        foreach (['INS_ETA', 'INS_LEV'] as $alias) {
            $def = collect($calc->definitions(4))->firstWhere('alias', $alias);
            $this->assertArrayNotHasKey('usesTtm', $def, "$alias should not be flagged usesTtm");
        }
    }

    // --- 8 chỉ số mới: bank (3), securities (2), insurance (3) ---

    public function test_bank_loan_and_investment_to_assets_and_charter_capital_growth()
    {
        $bs = new BalanceStatement();
        $bs->content = json_encode([
            $this->line('2', 10000),         // Tổng tài sản
            $this->line('10701', 6000),      // Cho vay khách hàng
            $this->line('108', 2000),        // Chứng khoán đầu tư
            $this->lineYoY('3080101', 1100, 1000), // Vốn điều lệ (2026 Q1 vs 2025 Q1)
        ]);
        $fs = new \stdClass();
        $fs->year = 2026;
        $fs->quarter = 1;
        $fs->balance_statement = $bs;

        $calc = new InstitutionCalculator($fs);
        $defs = collect($calc->definitions(1))->keyBy('alias');

        $this->assertEqualsWithDelta(60.0, ($defs['BANK_LOAN_TO_ASSETS']['fn'])(2026, 1), 0.0001);
        $this->assertEqualsWithDelta(20.0, ($defs['BANK_INVESTMENT_TO_ASSETS']['fn'])(2026, 1), 0.0001);
        $this->assertEqualsWithDelta(10.0, ($defs['BANK_CHARTER_CAPITAL_GROWTH']['fn'])(2026, 1), 0.0001);
    }

    public function test_securities_proprietary_and_margin_leverage()
    {
        $is = new IncomeStatement();
        $is->content = json_encode([
            $this->line('101', 50), $this->line('102', 10), $this->line('104', 20), $this->line('105', 0),
            $this->line('112', 200),
        ]);
        $bs = new BalanceStatement();
        $bs->content = json_encode([
            $this->line('1010104', 300), // Dư nợ cho vay margin
            $this->line('4', 200),       // VCSH
        ]);
        $fs = new \stdClass();
        $fs->year = 2026;
        $fs->quarter = 1;
        $fs->income_statement = $is;
        $fs->balance_statement = $bs;

        $calc = new InstitutionCalculator($fs);
        $defs = collect($calc->definitions(2))->keyBy('alias');

        // (50+10+20+0)/200*100 = 40%
        $this->assertEqualsWithDelta(40.0, ($defs['SEC_PROPRIETARY']['fn'])(2026, 1, true), 0.0001);
        // 300/200*100 = 150% (< 200% quy định UBCKNN)
        $this->assertEqualsWithDelta(150.0, ($defs['SEC_MARGIN_LEVERAGE']['fn'])(2026, 1), 0.0001);
    }

    public function test_insurance_retention_investment_yield_and_reserve_growth()
    {
        $is = new IncomeStatement();
        $is->content = json_encode([
            $this->line('1', 900), $this->line('2', 100), // phí gốc + phí nhận tái = 1000
            $this->line('7', 700),  // doanh thu thuần HĐ bảo hiểm
            $this->line('23', 60),  // doanh thu HĐ tài chính
        ]);
        $bs = new BalanceStatement();
        $bs->content = json_encode([
            $this->line('10102', 400, 600), // đầu tư TC ngắn hạn (kỳ liền trước, cho avgSumBs)
            $this->line('10205', 100, 100), // đầu tư TC dài hạn (kỳ liền trước, cho avgSumBs)
            $this->lineYoY('30103', 1100, 1000), // Dự phòng nghiệp vụ (2026 Q1 vs 2025 Q1, cho yoyBs)
        ]);
        $fs = new \stdClass();
        $fs->year = 2026;
        $fs->quarter = 1;
        $fs->income_statement = $is;
        $fs->balance_statement = $bs;

        $calc = new InstitutionCalculator($fs);
        $defs = collect($calc->definitions(4))->keyBy('alias');

        // 700/(900+100)*100 = 70%
        $this->assertEqualsWithDelta(70.0, ($defs['INS_RETENTION']['fn'])(2026, 1, true), 0.0001);
        // avg invest assets = (400+100 + 600+100)/2 = 600; 60/600*100 = 10%
        $this->assertEqualsWithDelta(10.0, ($defs['INS_INVESTMENT_YIELD']['fn'])(2026, 1, true), 0.0001);
        // (1100-1000)/1000*100 = 10%
        $this->assertEqualsWithDelta(10.0, ($defs['INS_RESERVE_GROWTH']['fn'])(2026, 1), 0.0001);
    }
}
