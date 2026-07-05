<?php
/**
 * FinancialStatement - A placeholder for IncomeStatement, CashFlowStatement, BalanceStatement
 *
 * @author: tuanha
 * @date: 11-Aug-2022
 */
namespace App\Models;

use App\Models\AnalysisReport;
use App\Models\IncomeStatement;
use App\Models\BalanceStatement;
use App\Models\CashFlowStatement;
use Bkstar123\BksCMS\AdminPanel\Admin;
use Illuminate\Database\Eloquent\Model;
use Bkstar123\MySqlSearch\Traits\MySqlSearch;

class FinancialStatement extends Model
{
    use MySqlSearch;

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['admin', 'balance_statement', 'cash_flow_statement', 'income_statement', 'analysis_report'];

    /**
     * List of columns for search enabling
     *
     * @var array
     */
    public static $mysqlSearchable = ['symbol'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'symbol', 'admin_id', 'year', 'quarter'
    ];

    /**
     * A financial statement belongs to an admin
     *
     * @return @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Standardize the symbol attribute to upper case
     *
     * @param string
     */
    public function setSymbolAttribute($value)
    {
        $this->attributes['symbol'] = strtoupper($value);
    }

    /**
     * A financial statement has one balance statement
     *
     * @return @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function balance_statement()
    {
        return $this->hasOne(BalanceStatement::class);
    }

    /**
     * A financial statement has one cash flow statement
     *
     * @return @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function cash_flow_statement()
    {
        return $this->hasOne(CashFlowStatement::class);
    }

    /**
     * A financial statement has one income statement
     *
     * @return @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function income_statement()
    {
        return $this->hasOne(IncomeStatement::class);
    }

    /**
     * A financial statement has one analysis report
     *
     * @return @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function analysis_report()
    {
        return $this->hasOne(AnalysisReport::class);
    }
}
