<?php
/**
 * analysis:recompute - chạy lại phân tích cho các báo cáo tài chính đã lưu,
 * ghi đè AnalysisReport bằng công thức hiện tại (Z-Score/M-Score đã cập nhật).
 * Dùng lại các khoản mục BCTC đã lưu; chỉ gọi API để lấy company type.
 */
namespace App\Console\Commands;

use App\Jobs\AnalyzeFinancialStatement;
use App\Models\AnalysisReport;
use App\Models\FinancialStatement;
use App\Models\FinancialStatementEntry;
use Bkstar123\BksCMS\AdminPanel\Admin;
use Exception;
use Illuminate\Console\Command;

class RecomputeAnalysis extends Command
{
    protected $signature = 'analysis:recompute
        {ids?* : ID báo cáo tài chính (bỏ trống = tất cả)}
        {--type=indirect : loại BCLCTT (direct|indirect)}';

    protected $description = 'Tính lại phân tích (ghi đè AnalysisReport) cho các báo cáo tài chính đã lưu';

    public function handle(): int
    {
        $type = $this->option('type');
        $ids  = $this->argument('ids');

        // Chạy CLI: tắt broadcast (Pusher) để các event completed/failing không lỗi.
        config(['broadcasting.default' => 'null']);

        $statements = FinancialStatement::query()
            ->when(!empty($ids), fn ($q) => $q->whereIn('id', $ids))
            ->orderBy('id')
            ->get();

        if ($statements->isEmpty()) {
            $this->warn('Không có báo cáo tài chính nào để tính lại.');
            return self::SUCCESS;
        }

        $ok = 0;
        $skip = 0;
        foreach ($statements as $fs) {
            // Báo cáo là hàm thuần của (dữ liệu BCTC, companyType, settings.limits) —
            // $admin chỉ dùng để broadcast, mà command đã tắt broadcast ở trên. Nên
            // bất kỳ holder nào cũng được; fallback để statement có người kéo đã bị
            // xoá vẫn tính lại được.
            $admin = $fs->lastPulledBy
                ?: Admin::whereIn(
                        'id',
                        FinancialStatementEntry::where('financial_statement_id', $fs->id)->select('admin_id')
                    )->first()
                ?: Admin::query()->orderBy('id')->first();
            if (!$admin) {
                $this->line("  <comment>✗</comment> #{$fs->id} {$fs->symbol} — không có admin nào để quy thuộc lần chạy");
                $skip++;
                continue;
            }
            try {
                // Không còn phải dọn report cũ: unique(financial_statement_id) đảm bảo
                // tối đa 1 report mỗi statement, và AnalyzeFinancialStatement ghi bằng
                // updateOrCreate nên nó ghi đè đúng chỗ thay vì chồng thêm row.
                AnalyzeFinancialStatement::dispatchSync($fs->id, $admin, $type);
                $this->line("  <info>✓</info> {$fs->symbol} {$fs->year}Q{$fs->quarter} (#{$fs->id})");
                $ok++;
            } catch (\Throwable $e) {
                // Phân tích thất bại (vd: ngân hàng/CK) — giữ nguyên report cũ.
                $this->line("  <comment>✗</comment> {$fs->symbol} {$fs->year}Q{$fs->quarter} (#{$fs->id}) — " . $e->getMessage());
                $skip++;
            }
        }

        $this->info("Đã tính lại {$ok}/{$statements->count()} báo cáo (bỏ qua {$skip}).");
        return self::SUCCESS;
    }
}
