<?php

namespace Tests\Unit;

use App\Jobs\Financials\Calculators\DupontCalculator;
use App\Models\BalanceStatement;
use App\Models\IncomeStatement;
use Tests\TestCase;

/**
 * DuPont Levels 2/3/5 must all reconstruct the SAME ROEA for a quarterly report.
 *
 * Previously Level 3/5 built ROEA from a QUARTER-ONLY net margin (ROS2 / EBIT margin
 * via getValue) while ROAA, the asset turnover and leverage were TTM — mixing a
 * single-quarter margin with an annualised turnover breaks the DuPont identity, so
 * Level 3 reported a materially different ROEA than Level 2 (e.g. 15.0% vs 16.6%).
 * The DuPont margin/burden factors are now computed on a TTM basis, so all levels agree.
 */
class DupontReconciliationTest extends TestCase
{
    private function line($id, array $values): array
    {
        return [
            'id' => $id, 'name' => "Item $id", 'parentID' => -1, 'expanded' => true,
            'level' => 1, 'field' => '', 'values' => $values,
        ];
    }

    /** 4 consecutive quarters so ttmOrAnnual() can accumulate the trailing year. */
    private function q4(int $q1_2026, int $q4_2025, int $q3_2025, int $q2_2025): array
    {
        return [
            ['period' => 'Q1 2026', 'year' => 2026, 'quarter' => 1, 'value' => $q1_2026],
            ['period' => 'Q4 2025', 'year' => 2025, 'quarter' => 4, 'value' => $q4_2025],
            ['period' => 'Q3 2025', 'year' => 2025, 'quarter' => 3, 'value' => $q3_2025],
            ['period' => 'Q2 2025', 'year' => 2025, 'quarter' => 2, 'value' => $q2_2025],
        ];
    }

    private function financialStatement()
    {
        $is = new IncomeStatement();
        $is->content = json_encode([
            $this->line('3',   $this->q4(1000, 1000, 1000, 1000)), // Doanh thu thuần -> TTM 4000
            $this->line('15',  $this->q4(350, 150, 150, 150)),     // LNTT (EBT)       -> TTM 800
            $this->line('701', $this->q4(50, 50, 50, 50)),         // Chi phí lãi vay  -> TTM 200 (EBIT 1000)
            $this->line('19',  $this->q4(160, 160, 160, 160)),     // LNST tổng        -> TTM 640
            // LNST cổ đông mẹ cố ý lệch giữa các quý: TTM=600 nhưng riêng-quý=300,
            // để phân biệt biến thể TTM (đúng) với quý-đơn lẻ (sai) của ROS2.
            $this->line('21',  $this->q4(300, 100, 100, 100)),     // -> TTM 600, quý 300
        ]);

        $bs = new BalanceStatement();
        $bs->content = json_encode([
            $this->line('2',   [ // Tổng tài sản: bq (5000+4000)/2 = 4500
                ['period' => 'Q1 2026', 'year' => 2026, 'quarter' => 1, 'value' => 5000],
                ['period' => 'Q4 2025', 'year' => 2025, 'quarter' => 4, 'value' => 4000],
            ]),
            $this->line('302', [ // VCSH: bq (3000+2600)/2 = 2800
                ['period' => 'Q1 2026', 'year' => 2026, 'quarter' => 1, 'value' => 3000],
                ['period' => 'Q4 2025', 'year' => 2025, 'quarter' => 4, 'value' => 2600],
            ]),
        ]);

        $fs = new \stdClass();
        $fs->year = 2026;
        $fs->quarter = 1;
        $fs->income_statement = $is;
        $fs->balance_statement = $bs;
        return $fs;
    }

    public function test_dupont_ros2_uses_ttm_not_quarter_only()
    {
        $calc = (new DupontCalculator($this->financialStatement()))->calculateDupontComponents(2026, 1);
        // TTM: 600/4000 = 15.0% (đúng) — KHÔNG phải quý-đơn 300/1000 = 30.0% (cũ, sai).
        $this->assertSame(15.0, $calc->ros2);
        $this->assertNotSame(30.0, $calc->ros2);
    }

    public function test_levels_2_3_5_reconstruct_the_same_roea()
    {
        $calc = (new DupontCalculator($this->financialStatement()))->calculateDupontComponents(2026, 1);

        $level2 = round($calc->averageFinancialLeverage * $calc->roaa, 1);
        $level3 = round($calc->ros2 * $calc->averageTotalAssetTurnOver * $calc->averageFinancialLeverage, 1);
        $level5 = round(
            $calc->earningAfterTaxParentCompanyToEarningBeforeTax
            * $calc->earningBeforeTaxToEBIT
            * $calc->ebitMargin
            * $calc->averageTotalAssetTurnOver
            * $calc->averageFinancialLeverage,
            1
        );

        // True ROEA = LNST mẹ TTM / VCSH bq = 600/2800 = 21.43%.
        $trueRoea = 100 * 600 / 2800;

        $this->assertEqualsWithDelta($level2, $level3, 0.11);
        $this->assertEqualsWithDelta($level3, $level5, 0.11);
        $this->assertEqualsWithDelta($trueRoea, $level2, 0.3);
    }
}
