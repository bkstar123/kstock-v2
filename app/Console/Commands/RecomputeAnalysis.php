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
            $admin = Admin::find($fs->admin_id);
            if (!$admin) {
                $this->line("  <comment>✗</comment> #{$fs->id} {$fs->symbol} — không tìm thấy admin sở hữu");
                $skip++;
                continue;
            }
            // Chụp lại các report cũ để xóa SAU khi tạo bản mới thành công.
            $oldReportIds = AnalysisReport::where('financial_statement_id', $fs->id)->pluck('id')->all();
            try {
                AnalyzeFinancialStatement::dispatchSync($fs->id, $admin, $type);
                AnalysisReport::whereIn('id', $oldReportIds)->delete();
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
