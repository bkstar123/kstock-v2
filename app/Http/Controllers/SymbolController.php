<?php
/**
 * SymbolController
 *
 * @author: tuanha
 * @date: 28-July-2022
 */
namespace App\Http\Controllers;

use Exception;
use Throwable;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Models\FinancialStatement;
use App\Jobs\PullFinancialStatement;
use App\Services\StatementLibrary;
use App\Services\SymbolCatalog;
use Bkstar123\BksCMS\AdminPanel\Role;
use App\Http\Components\RequestByUserThrottling;

class SymbolController extends Controller
{
    use RequestByUserThrottling;

    /**
     * Display a list of financial statements
     *
     * @return \Illuminate\Http\Response
     */
    public function listFinancialStatements()
    {
        // Tìm kiếm theo mã bằng LIKE (portable cho cả SQLite lẫn MySQL) — tránh fulltext
        // MATCH...AGAINST của MySqlSearch vốn lỗi trên SQLite.
        $searchText = trim((string) request()->input('search'));
        $query = FinancialStatement::query();
        if ($searchText !== '') {
            $query->where('symbol', 'like', strtoupper($searchText) . '%');
        }
        // Ownership is the pivot now. Superadmin-sees-all is deliberate here and
        // matches the AuthServiceProvider gates — the OPPOSITE of the Symbol
        // Directory, which is strictly personal even for superadmins.
        $query->visibleTo(auth()->guard('admins')->user());
        $financial_statements = $query->orderByDesc('id')
            ->simplePaginate(config('bkstar123_bkscms_adminpanel.pageSize'))
            ->appends(['search' => $searchText]);
        return view('cms.symbols.statements.index', compact('financial_statements'));
    }

    /**
     * Pull financial statement with the data given in the request
     *
     * @param Illuminate\Http\Request
     * @return Illuminate\Http\Response
     */
    public function pullFinancialStatement(Request $request, StatementLibrary $library)
    {
        if (!$this->isThrottled()) {
            $this->setRequestThrottling();
            $validated = $request->validate([
                'symbol' => ['required', 'string', 'regex:/^[A-Za-z0-9.]{1,20}$/'],
                'year' => 'required|integer|between:1900,2100',
                'quarter' => 'required|integer|between:0,4'
            ]);
            $symbol  = strtoupper(trim($validated['symbol']));
            $year    = (int) $validated['year'];
            $quarter = (int) $validated['quarter'];
            $adminId = $request->user()->id;
            try {
                // One shared row per (symbol, year, quarter); ownership is the pivot.
                // A pull of a period KSTOCK already has attaches the caller to that
                // row and refreshes it in place rather than making a second ~330 KB copy.
                [$financialStatement, $existed] = $this->resolveStatement($symbol, $year, $quarter, $adminId);
                $library->attach($financialStatement, $adminId);
                PullFinancialStatement::dispatch(
                    ['symbol' => $symbol, 'year' => $year, 'quarter' => $quarter],
                    $financialStatement->id,
                    $request->user()
                );
                $period = $quarter ? "Q{$quarter} {$year}" : (string) $year;
                flashing($existed
                    ? "{$symbol} {$period} is already in KSTOCK — it has been added to your library and is being refreshed from the data provider"
                    : 'Your request is being processed'
                )->flash();
            } catch (Throwable $e) {
                report($e);
                flashing('Failed to proceed the requested action')
                ->error()
                ->flash();
            }
        } else {
            flashing('KSTOCK is busy processing your first request, please wait for 10 seconds before sending another one')->warning()->flash();
        }
        return back();
    }

    /**
     * Resolve the ONE shared row for this period, creating it if it does not exist.
     *
     * @return array{0: FinancialStatement, 1: bool}  [statement, alreadyExisted]
     */
    private function resolveStatement(string $symbol, int $year, int $quarter, int $adminId): array
    {
        // Plain equality on `symbol`: every write path upper-cases it via the model
        // mutator, and $symbol is explicitly upper-cased by the caller (query-builder
        // wheres do not run mutators).
        $key = ['symbol' => $symbol, 'year' => $year, 'quarter' => $quarter];

        if ($existing = FinancialStatement::where($key)->first()) {
            $existing->last_pulled_by_admin_id = $adminId;
            $existing->save();
            return [$existing, true];
        }

        try {
            return [FinancialStatement::create($key + ['last_pulled_by_admin_id' => $adminId]), false];
        } catch (QueryException $e) {
            // Two admins pulled the same period in the same instant. The
            // RequestByUserThrottling trait is keyed per user + path, so it gives
            // ZERO cross-user protection — unique(symbol, year, quarter) is the only
            // real arbiter, and it can only speak by throwing. Retry as an attach.
            // updateOrCreate() would not help: it is the same SELECT-then-INSERT and
            // does not catch the violation either.
            $winner = FinancialStatement::where($key)->first();
            if (!$winner) {
                throw $e;   // a different constraint failed; do not swallow it
            }
            $winner->last_pulled_by_admin_id = $adminId;
            $winner->save();
            return [$winner, true];
        }
    }

    /**
     * Destroy the selected financial statement
     *
     * @param \App\FinancialStatement $financial_statement
     * @return \Illuminate\Http\Response
     */
    public function destroyFinancialStatement(FinancialStatement $financial_statement, StatementLibrary $library)
    {
        if (!$this->isThrottled()) {
            $this->setRequestThrottling();
            $id = $financial_statement->id;
            $me = auth()->guard('admins')->user();
            try {
                // Statements are shared, so removing one is "drop MY hold", then
                // purge the data only if nobody else still holds it. The detach()
                // return value is the guard — same load-bearing pattern as
                // CompanyController::destroy — so an admin can never trigger a purge
                // for a statement they never held.
                if ($library->detach($id, $me->id)) {
                    $purged = $library->release($id);
                    flashing($purged
                        ? 'The selected financial statement has been removed from your library and purged'
                        : 'The selected financial statement has been removed from your library (another admin still has it, so the data was kept)'
                    )->success()->flash();
                } elseif ($me->hasRole(Role::SUPERADMINS)) {
                    // Administrative removal of a statement the superadmin never
                    // pulled — reachable only via the superadmin branch of the gate.
                    $library->purge($id);
                    flashing('The selected financial statement has been purged for all admins')
                    ->success()
                    ->flash();
                } else {
                    // Unreachable: the gate already required a hold. Fail closed.
                    flashing('That financial statement is not in your library')
                    ->error()
                    ->flash();
                }
            } catch (Exception $e) {
                report($e);
                flashing("The submitted action failed to be executed due to some unknown error")
                ->error()
                ->flash();
            }
        } else {
            flashing('KSTOCK is busy processing your first request, please wait for 10 seconds before sending another one')->warning()->flash();
        }
        return back();
    }

    /**
     * Destroy multiple selected financial statements
     *
     * @return \Illuminate\Http\Response
     */
    public function massiveDestroyFinancialStatements(StatementLibrary $library)
    {
        if (!$this->isThrottled()) {
            $this->setRequestThrottling();
            $Ids = collect(explode(',', (string) request()->input('Ids')))
                ->map(fn ($id) => (int) trim($id))
                ->filter()
                ->unique();
            try {
                // A hard purge, not a detach: this button is superadmin-only (see the
                // gate) and sits on a listing that shows every admin's statements, so
                // a detach-only "Remove all" would silently no-op on every row the
                // superadmin never pulled. purge() also avoids hydrating the models —
                // FinancialStatement::$with eager-loads four blobs (~330 KB a row).
                foreach ($Ids as $id) {
                    $library->purge($id);
                }
                flashing('All selected financial statements have been removed')
                ->success()
                ->flash();
            } catch (Exception $e) {
                flashing("The submitted action failed to be executed due to some unknown error")
                ->error()
                ->flash();
            }
        } else {
            flashing('KSTOCK is busy processing your first request, please wait for 10 seconds before sending another one')->warning()->flash();
        }
        return back();
    }

    /**
     * Display a financial statement
     *
     * @param \App\FinancialStatement $financial_statement
     * @return \Illuminate\Http\Response
     */
    public function showFinancialStatement(FinancialStatement $financial_statement, SymbolCatalog $catalog)
    {
        // Nhận diện DN sản xuất / phi sản xuất từ mã ngành ICB (để chọn đúng Altman Z/Z2).
        $symbol = $catalog->remember($financial_statement->symbol);
        $sectorClass = $symbol ? businessSectorClass($symbol->industry_code) : null;
        return view('cms.symbols.statements.show', compact('financial_statement', 'sectorClass'));
    }
}
