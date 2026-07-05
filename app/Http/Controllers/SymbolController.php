<?php
/**
 * SymbolController
 *
 * @author: tuanha
 * @date: 28-July-2022
 */
namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Models\FinancialStatement;
use App\Jobs\PullFinancialStatement;
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
        if (!auth()->user()->hasRole(Role::SUPERADMINS)) {
            $query->where('admin_id', auth()->user()->id);
        }
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
    public function pullFinancialStatement(Request $request)
    {
        if (!$this->isThrottled()) {
            $this->setRequestThrottling();
            $request->validate([
                'symbol' => ['required', 'string', 'regex:/^[A-Za-z0-9.]{1,20}$/'],
                'year' => 'required|integer|between:1900,2100',
                'quarter' => 'required|integer|between:0,4'
            ]);
            $data = $request->except('_token');
            $data['admin_id'] = $request->user()->id;
            try {
                $financialStatement = FinancialStatement::create($data);
                PullFinancialStatement::dispatch($request->except('_token'), $financialStatement->id, $request->user());
                flashing('Your request is being processed')
                ->flash();
            } catch (Exception $e) {
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
     * Destroy the selected financial statement
     *
     * @param \App\FinancialStatement $financial_statement
     * @return \Illuminate\Http\Response
     */
    public function destroyFinancialStatement(FinancialStatement $financial_statement)
    {
        if (!$this->isThrottled()) {
            $this->setRequestThrottling();
            try {
                $financial_statement->delete();
                flashing("The selected financial statement has been successfully removed")
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
     * Destroy multiple selected financial statements
     *
     * @return \Illuminate\Http\Response
     */
    public function massiveDestroyFinancialStatements()
    {
        if (!$this->isThrottled()) {
            $this->setRequestThrottling();
            $Ids = explode(',', request()->input('Ids'));
            try {
                FinancialStatement::destroy($Ids);
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
