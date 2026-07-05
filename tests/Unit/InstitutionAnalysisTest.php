<?php

namespace Tests\Unit;

use App\Models\AnalysisReport;
use Tests\TestCase;

/**
 * Kiểm thử tầng phân tích định chế tài chính: tín hiệu chỉ số theo ngưỡng chuẩn ngành,
 * nhận diện loại định chế, thẻ tóm tắt & nhận định tổng quan. Dựng AnalysisReport
 * in-memory (chỉ đọc attributes['content']) nên không cần DB.
 */
class InstitutionAnalysisTest extends TestCase
{
    /** Tạo AnalysisReport in-memory từ danh sách [alias => [name, group, unit, latestValue]]. */
    private function report(array $metrics): AnalysisReport
    {
        $content = [];
        foreach ($metrics as $alias => [$name, $group, $unit, $latest]) {
            $content[] = [
                'name' => $name, 'alias' => $alias, 'group' => $group, 'unit' => $unit,
                'description' => '',
                'values' => [
                    ['period' => 'Q1 2026', 'year' => 2026, 'quarter' => 1, 'value' => $latest],
                    ['period' => 'Q4 2025', 'year' => 2025, 'quarter' => 4, 'value' => $latest],
                ],
            ];
        }
        $ar = new AnalysisReport();
        $ar->content = json_encode($content);
        return $ar;
    }

    public function test_high_is_good_metric_signal_thresholds()
    {
        $this->assertSame('good', institutionMetricSignal('BANK_ROAA', [2.0])['tone']);
        $this->assertSame('warn', institutionMetricSignal('BANK_ROAA', [1.0])['tone']);
        $this->assertSame('bad',  institutionMetricSignal('BANK_ROAA', [0.5])['tone']);
    }

    public function test_low_is_good_metric_signal_thresholds()
    {
        // CIR: ≤35 tốt, ≤45 trung bình, còn lại cao
        $this->assertSame('good', institutionMetricSignal('BANK_CIR', [30])['tone']);
        $this->assertSame('warn', institutionMetricSignal('BANK_CIR', [40])['tone']);
        $this->assertSame('bad',  institutionMetricSignal('BANK_CIR', [55])['tone']);
    }

    public function test_margin_leverage_signal_thresholds()
    {
        // Dư nợ margin/VCSH: ≤150% tốt, ≤200% trung bình (giới hạn UBCKNN), >200% vi phạm
        $this->assertSame('good', institutionMetricSignal('SEC_MARGIN_LEVERAGE', [100])['tone']);
        $this->assertSame('warn', institutionMetricSignal('SEC_MARGIN_LEVERAGE', [180])['tone']);
        $this->assertSame('bad',  institutionMetricSignal('SEC_MARGIN_LEVERAGE', [250])['tone']);
    }

    public function test_growth_metric_signal_thresholds()
    {
        $this->assertSame('good', institutionMetricSignal('SEC_NP_GROWTH', [15])['tone']);
        $this->assertSame('warn', institutionMetricSignal('SEC_NP_GROWTH', [5])['tone']);
        $this->assertSame('bad',  institutionMetricSignal('SEC_NP_GROWTH', [-3])['tone']);
    }

    public function test_informational_and_invalid_metrics_have_no_signal()
    {
        $this->assertNull(institutionMetricSignal('SEC_BROKERAGE', [18.9])); // chỉ mang tính thông tin
        $this->assertNull(institutionMetricSignal('BANK_ROAA', []));         // không có giá trị
        $this->assertNull(institutionMetricSignal('BANK_ROAA', ['']));       // giá trị rỗng
    }

    public function test_credit_cost_is_scored_asset_quality_others_informational()
    {
        // Chi phí tín dụng: thấp = tốt (≤1% tốt, ≤2% trung bình, >2% cao).
        $this->assertSame('good', institutionMetricSignal('BANK_CREDIT_COST', [0.8])['tone']);
        $this->assertSame('warn', institutionMetricSignal('BANK_CREDIT_COST', [1.5])['tone']);
        $this->assertSame('bad',  institutionMetricSignal('BANK_CREDIT_COST', [3.0])['tone']);
        // Các chỉ số chất lượng tài sản / phân rã NIM còn lại chỉ mang tính thông tin.
        $this->assertNull(institutionMetricSignal('BANK_LLR', [1.41]));
        $this->assertNull(institutionMetricSignal('BANK_YIELD', [8.0]));
        $this->assertNull(institutionMetricSignal('BANK_COF', [4.0]));
    }

    public function test_institution_data_note()
    {
        $this->assertStringContainsString('NPL', institutionDataNote('bank'));
        $this->assertStringContainsString('nợ xấu', institutionDataNote('bank'));
        $this->assertNotNull(institutionDataNote('securities'));
        $this->assertNotNull(institutionDataNote('insurance'));
        $this->assertNull(institutionDataNote(null));
    }

    public function test_institution_type_detection()
    {
        $this->assertSame('bank', institutionType($this->report([
            'BANK_ROAA' => ['ROAA', 'Sinh lời (Ngân hàng)', '%', 2.0],
        ])));
        $this->assertSame('securities', institutionType($this->report([
            'SEC_ROAA' => ['ROAA', 'Sinh lời (Chứng khoán)', '%', 4.0],
        ])));
        $this->assertSame('insurance', institutionType($this->report([
            'INS_ROAA' => ['ROAA', 'Sinh lời (Bảo hiểm)', '%', 1.0],
        ])));
        $this->assertNull(institutionType($this->report([
            'ROE' => ['ROE', 'Sinh lời', '%', 15.0],
        ])));
        $this->assertNull(institutionType(null));
    }

    public function test_bank_tiles_built_with_signals()
    {
        $ar = $this->report([
            'BANK_ROAA' => ['ROAA', 'Sinh lời (Ngân hàng)', '%', 2.2],
            'BANK_ROEA' => ['ROEA', 'Sinh lời (Ngân hàng)', '%', 14.5], // < 15 => warn
            'BANK_NIM'  => ['NIM',  'Sinh lời (Ngân hàng)', '%', 3.9],
            'BANK_CIR'  => ['CIR',  'Hiệu quả (Ngân hàng)', '%', 30.0],
        ]);
        $tiles = institutionTiles($ar);

        $this->assertCount(4, $tiles);
        $tones = array_column($tiles, 'tone');
        $this->assertSame(['good', 'warn', 'good', 'good'], $tones);
        // Giá trị định dạng theo đơn vị %
        $this->assertStringContainsString('%', $tiles[0]['value']);
    }

    public function test_verdict_reflects_worst_core_signal()
    {
        // ROEA yếu (< ngưỡng warn) => nhận định tổng quan phải là "bad".
        $ar = $this->report([
            'BANK_ROAA' => ['ROAA', 'Sinh lời (Ngân hàng)', '%', 2.2],
            'BANK_ROEA' => ['ROEA', 'Sinh lời (Ngân hàng)', '%', 5.0],   // core, bad
            'BANK_CIR'  => ['CIR',  'Hiệu quả (Ngân hàng)', '%', 30.0],  // core, good
            'BANK_PROV' => ['Prov', 'Hiệu quả (Ngân hàng)', '%', 10.0],  // core, good
            'BANK_ETA'  => ['ETA',  'Cân đối (Ngân hàng)',  '%', 12.0],  // core, good
            'BANK_NP_GROWTH' => ['NP growth', 'Tăng trưởng (Ngân hàng)', '%', 25.0],
        ]);
        $verdict = institutionVerdict($ar);

        $this->assertSame('bad', $verdict['tone']);
        $this->assertNotEmpty($verdict['drivers']);
    }

    public function test_non_institution_report_has_no_tiles_or_verdict()
    {
        $ar = $this->report(['ROE' => ['ROE', 'Sinh lời', '%', 15.0]]);
        $this->assertSame([], institutionTiles($ar));
        $this->assertNull(institutionVerdict($ar));
    }

    public function test_overall_verdict_uses_selected_z_variant()
    {
        // Phi sản xuất: chỉ có Z2-Score (0.5 = nguy hiểm) -> verdict bắt được rủi ro phá sản.
        $ar = $this->report(['Z2-Score' => ['Z2-Score', 'Altman', 'scalar', 0.5]]);
        $this->assertSame('bad', analysisOverallVerdict($ar, 'Z2-Score')['tone']);
        // Nếu chọn nhầm Z-Score (không có trong report) -> không có driver -> verdict null.
        $this->assertNull(analysisOverallVerdict($ar, 'Z-Score'));
    }
}
