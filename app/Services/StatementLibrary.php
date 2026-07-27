<?php
/**
 * StatementLibrary - per-admin ownership of the SHARED `financial_statements` rows.
 *
 * `financial_statements` is keyed on (symbol, year, quarter) and is global: one row
 * per period, no owner column. Which admins "have" a statement lives in
 * `financial_statement_entries`. A statement — and its four child blobs, together
 * ~330 KB of JSON — may only be deleted once nobody holds it any more.
 *
 * This is the same reference-counted pattern as SymbolCatalog::release(), and it is a
 * separate class for the same reason that one is: SymbolCatalog owns the `symbols`
 * master table and wraps the external API client, while this owns statement ownership
 * and touches no API at all.
 */
namespace App\Services;

use App\Models\AnalysisReport;
use App\Models\BalanceStatement;
use App\Models\CashFlowStatement;
use App\Models\FinancialStatement;
use App\Models\FinancialStatementEntry;
use App\Models\IncomeStatement;
use Illuminate\Support\Facades\DB;

class StatementLibrary
{
    /**
     * Give an admin a hold on a statement. Idempotent — safe on every pull,
     * including a re-pull of a period they already have.
     */
    public function attach(FinancialStatement $statement, $adminId): FinancialStatementEntry
    {
        return FinancialStatementEntry::firstOrCreate([
            'admin_id'               => (int) $adminId,
            'financial_statement_id' => $statement->getKey(),
        ]);
    }

    /**
     * Remove ONE admin's hold. Returns true only if a row was actually removed, so
     * callers can use it as a guard before running the purge — the same
     * load-bearing pattern as CompanyController::destroy, where it is what stops an
     * admin triggering a purge for something they never held.
     */
    public function detach($statementId, $adminId): bool
    {
        return FinancialStatementEntry::where('financial_statement_id', (int) $statementId)
            ->where('admin_id', (int) $adminId)
            ->delete() > 0;
    }

    /**
     * Does any admin still hold this statement?
     */
    public function isHeld($statementId): bool
    {
        return FinancialStatementEntry::where('financial_statement_id', (int) $statementId)->exists();
    }

    /**
     * Purge the statement and its children, but only if nobody holds it any more.
     * Returns true only when a statement was actually deleted.
     */
    public function release($statementId): bool
    {
        $statementId = (int) $statementId;

        return DB::transaction(function () use ($statementId) {
            if ($this->isHeld($statementId)) {
                return false;
            }

            return $this->purge($statementId);
        });
    }

    /**
     * Release several statements. Returns how many were actually purged.
     *
     * @param  iterable<int>  $statementIds
     */
    public function releaseMany(iterable $statementIds): int
    {
        return DB::transaction(function () use ($statementIds) {
            $purged = 0;
            foreach ($statementIds as $statementId) {
                if ($this->release($statementId)) {
                    $purged++;
                }
            }

            return $purged;
        });
    }

    /**
     * The latest ANALYSED period per symbol in this user's library — the picker
     * behind both /cms/compare and the dashboard. One definition, two callers; it
     * used to be duplicated as inline PHP in cms/dashboard.blade.php, and neither
     * copy scoped by admin.
     *
     * @param  \Bkstar123\BksCMS\AdminPanel\Admin|null  $user
     * @return \Illuminate\Support\Collection
     */
    public function comparable($user)
    {
        return FinancialStatement::query()
            ->visibleTo($user)
            // Was ->get()->filter(fn ($fs) => !empty($fs->analysis_report)), which
            // loaded every statement and let $with drag all four blobs (~330 KB a
            // row) into memory just to discard most of them.
            ->whereHas('analysis_report')
            ->withOnly('analysis_report')
            // Was sorted in PHP on sprintf('%04d%d', year, quarter) with NO tiebreak.
            // PHP sorts are stable, so ties kept insertion order and the OLDEST row
            // won — the wrong one once two admins hold the same period.
            ->orderByDesc('year')->orderByDesc('quarter')->orderByDesc('id')
            ->get()
            ->groupBy('symbol')
            ->map(function ($statements) {
                $fs = $statements->first();
                return [
                    'code'   => $fs->symbol,
                    'type'   => institutionType($fs->analysis_report),
                    'period' => $fs->quarter ? "Q{$fs->quarter} {$fs->year}" : (string) $fs->year,
                    'fs'     => $fs,
                ];
            })
            ->sortKeys()
            ->values();
    }

    /**
     * Unconditional removal, ignoring holders — the administrative escape hatch
     * (superadmin massive-destroy) and the cleanup path for a pull that produced no
     * data at all.
     *
     * The child deletes are EXPLICIT even though all five foreign keys cascade:
     * SQLite only enforces them while `PRAGMA foreign_keys` is on, and that is a
     * config toggle (config/database.php `foreign_key_constraints`). Leaving ~330 KB
     * of orphaned blobs per statement behind because of a mis-set flag is not an
     * acceptable failure mode.
     */
    public function purge($statementId): bool
    {
        $statementId = (int) $statementId;

        return DB::transaction(function () use ($statementId) {
            FinancialStatementEntry::where('financial_statement_id', $statementId)->delete();
            AnalysisReport::where('financial_statement_id', $statementId)->delete();
            BalanceStatement::where('financial_statement_id', $statementId)->delete();
            IncomeStatement::where('financial_statement_id', $statementId)->delete();
            CashFlowStatement::where('financial_statement_id', $statementId)->delete();

            return FinancialStatement::whereKey($statementId)->delete() > 0;
        });
    }
}
