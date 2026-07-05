<?php

namespace Tests\Feature;

use App\Models\AnalysisReport;
use App\Models\FinancialStatement;
use App\Services\Contracts\Symbols as SymbolsInterface;
use Bkstar123\BksCMS\AdminPanel\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeSymbols;
use Tests\TestCase;

/**
 * Tính năng so sánh mã cổ phiếu: picker, bảng hợp nhất theo loại, cảnh báo khác loại.
 */
class StockComparisonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(SymbolsInterface::class, new FakeSymbols());
    }

    private function admin(): Admin
    {
        return Admin::create([
            'name' => 'Cmp', 'username' => 'cmp', 'email' => 'cmp@example.com',
            'password' => bcrypt('secret123'),
        ])->refresh();
    }

    private function item(string $alias, string $group, string $unit, $value): array
    {
        return [
            'name' => $alias, 'alias' => $alias, 'group' => $group, 'unit' => $unit, 'description' => '',
            'values' => [['period' => 'Q1 2026', 'year' => 2026, 'quarter' => 1, 'value' => $value]],
        ];
    }

    private function makeStatement(int $adminId, string $symbol, array $items): void
    {
        $fs = FinancialStatement::create(['symbol' => $symbol, 'admin_id' => $adminId, 'year' => 2026, 'quarter' => 1]);
        AnalysisReport::create(['financial_statement_id' => $fs->id, 'content' => json_encode($items)]);
    }

    private function seedThree(int $adminId): void
    {
        // Hai DN thường + một ngân hàng.
        $this->makeStatement($adminId, 'FPT', [
            $this->item('ROE', 'Khả năng sinh lời', '%', 25.0),
            $this->item('ROA', 'Khả năng sinh lời', '%', 15.0),
        ]);
        $this->makeStatement($adminId, 'VNM', [
            $this->item('ROE', 'Khả năng sinh lời', '%', 30.0),
            $this->item('ROA', 'Khả năng sinh lời', '%', 20.0),
        ]);
        $this->makeStatement($adminId, 'TCB', [
            $this->item('BANK_ROAA', 'Sinh lời (Ngân hàng)', '%', 2.2),
            $this->item('BANK_ROEA', 'Sinh lời (Ngân hàng)', '%', 14.5),
        ]);
    }

    public function test_guest_is_redirected()
    {
        $this->get('/cms/compare')->assertRedirect();
    }

    public function test_picker_lists_symbols_with_reports()
    {
        $admin = $this->admin();
        $this->seedThree($admin->id);

        $this->actingAs($admin, 'admins')->get('/cms/compare')
            ->assertStatus(200)->assertSee('FPT')->assertSee('VNM')->assertSee('TCB');
    }

    public function test_same_type_comparison_has_no_mixed_warning()
    {
        $admin = $this->admin();
        $this->seedThree($admin->id);

        $this->actingAs($admin, 'admins')->get('/cms/compare?symbols[]=FPT&symbols[]=VNM')
            ->assertStatus(200)
            ->assertSee('ROE')
            ->assertDontSee('không cùng loại');
    }

    public function test_mixed_type_comparison_shows_warning()
    {
        $admin = $this->admin();
        $this->seedThree($admin->id);

        $this->actingAs($admin, 'admins')->get('/cms/compare?symbols[]=FPT&symbols[]=TCB')
            ->assertStatus(200)
            ->assertSee('không cùng loại');
    }

    public function test_unknown_symbol_is_ignored()
    {
        $admin = $this->admin();
        $this->seedThree($admin->id);

        // ZZZ không có báo cáo -> bị loại, chỉ FPT được so sánh, không lỗi.
        $this->actingAs($admin, 'admins')->get('/cms/compare?symbols[]=FPT&symbols[]=ZZZ')
            ->assertStatus(200)->assertSee('FPT');
    }
}
