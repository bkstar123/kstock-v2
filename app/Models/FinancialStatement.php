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
use Bkstar123\BksCMS\AdminPanel\Role;
use Illuminate\Database\Eloquent\Builder;
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
    protected $with = ['lastPulledBy', 'balance_statement', 'cash_flow_statement', 'income_statement', 'analysis_report'];

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
        'symbol', 'year', 'quarter', 'last_pulled_by_admin_id'
    ];

    /**
     * The admin who most recently pulled or refreshed this shared row.
     *
     * NOT ownership — that is financial_statement_entries. This is an audit column
     * with ON DELETE SET NULL, so it is nullable and every read site must be
     * null-safe. Read it together with updated_at ("last refreshed at"): the content
     * is a rolling window snapshot, so a re-pull by any holder legitimately changes
     * what every other holder sees.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function lastPulledBy()
    {
        return $this->belongsTo(Admin::class, 'last_pulled_by_admin_id');
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
     * Restrict to the statements in the given admin's library.
     * Mirrors Symbol::scopeInDirectoryOf — the pivot-subquery idiom.
     */
    public function scopeHeldBy(Builder $query, $adminId): Builder
    {
        return $query->whereIn(
            'id',
            FinancialStatementEntry::where('admin_id', (int) $adminId)->select('financial_statement_id')
        );
    }

    /**
     * What this user may see.
     *
     * Superadmin-sees-all is DELIBERATE and matches the AuthServiceProvider gates.
     * It is the OPPOSITE of the Symbol Directory, which stays strictly personal even
     * for superadmins (see CompanyController::index). Do not harmonise them.
     */
    public function scopeVisibleTo(Builder $query, $user): Builder
    {
        return $user && $user->hasRole(Role::SUPERADMINS) ? $query : $query->heldBy($user->id);
    }

    /**
     * The per-admin holds on this shared statement.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function entries()
    {
        return $this->hasMany(FinancialStatementEntry::class, 'financial_statement_id');
    }

    /**
     * The admins who have this statement in their library.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function holders()
    {
        return $this->belongsToMany(
            Admin::class,
            'financial_statement_entries',
            'financial_statement_id',
            'admin_id'
        )->withTimestamps();
    }

    /**
     * Does this admin have this statement in their library?
     *
     * The single definition of "held", shared by the authorization gates and the
     * views — ownership lives in the pivot, never on this row.
     */
    public function isHeldBy($adminId): bool
    {
        return $this->exists
            && FinancialStatementEntry::where('financial_statement_id', $this->getKey())
                ->where('admin_id', (int) $adminId)
                ->exists();
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
