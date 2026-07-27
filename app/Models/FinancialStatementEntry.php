<?php
/**
 * FinancialStatementEntry - which admins "have" a shared FinancialStatement.
 *
 * Mirrors DirectoryEntry / Watchlist, with one deliberate difference: those store a
 * bare ticker string with no FK, because `symbols` is an on-demand cache that may
 * legitimately vanish under them. Here the parent IS the reference-counted resource,
 * so the FK is real and cascades — purging a statement drops its entries for free.
 */
namespace App\Models;

use Bkstar123\BksCMS\AdminPanel\Admin;
use Illuminate\Database\Eloquent\Model;

class FinancialStatementEntry extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'admin_id', 'financial_statement_id',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function financialStatement()
    {
        return $this->belongsTo(FinancialStatement::class, 'financial_statement_id');
    }
}
