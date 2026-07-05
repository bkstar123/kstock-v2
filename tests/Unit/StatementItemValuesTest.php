<?php

namespace Tests\Unit;

use App\Models\AnalysisReport;
use App\Models\CashFlowStatement;
use Tests\TestCase;

/**
 * Regression: `statementItemValues()` (app/Functions/kstock_helpers.php) phải luôn trả về
 * một mảng an toàn — kể cả khi statement/item không tồn tại — thay vì để lời gọi
 * `->getItem($id)->getValues()` vỡ vì gọi method trên null. Ca thực tế gây lỗi 500: một
 * công ty có lưu chuyển tiền tệ theo phương pháp TRỰC TIẾP (Direct) thiếu hẳn các item ID
 * của phương pháp GIÁN TIẾP (Indirect) mà trang báo cáo mặc định giả định (vd item '212').
 */
class StatementItemValuesTest extends TestCase
{
    private function cashFlowStatement(array $itemIds): CashFlowStatement
    {
        $content = array_map(function ($id) {
            return [
                'id' => $id, 'name' => "Item $id", 'parentID' => -1, 'expanded' => true,
                'level' => 1, 'field' => '',
                'values' => [['period' => 'Q1 2026', 'year' => 2026, 'quarter' => 1, 'value' => 10]],
            ];
        }, $itemIds);
        $cf = new CashFlowStatement();
        $cf->content = json_encode($content);
        return $cf;
    }

    public function test_returns_empty_array_when_statement_is_null()
    {
        $this->assertSame([], statementItemValues(null, '212'));
    }

    public function test_returns_empty_array_when_item_missing_on_direct_method_cashflow()
    {
        // Mô phỏng LCTT trực tiếp: chỉ có id '104' (Tiền chi trả lãi vay), thiếu id '212'
        // (Lưu chuyển tiền thuần từ HĐ đầu tư của phương pháp gián tiếp).
        $cf = $this->cashFlowStatement(['101', '102', '103', '104']);
        $this->assertSame([], statementItemValues($cf, '212'));
    }

    public function test_returns_values_when_item_exists()
    {
        $cf = $this->cashFlowStatement(['104']);
        $this->assertNotEmpty(statementItemValues($cf, '104'));
    }

    public function test_returns_empty_array_when_analysis_report_item_missing()
    {
        // Tỷ số dòng tiền/CAPEX bị writer bỏ qua (vd công ty LCTT trực tiếp) -> report
        // không có alias này; trang biểu đồ không được vỡ vì đó.
        $ar = new AnalysisReport();
        $ar->content = json_encode([]);
        $this->assertSame([], statementItemValues($ar, 'CFO/Revenue'));
    }
}
