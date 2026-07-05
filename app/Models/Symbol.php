<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Symbol extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'code', 'name', 'exchange', 'company_type', 'industry_code', 'icb_code', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Ticker codes are always stored upper-cased (mirrors FinancialStatement).
     */
    public function setCodeAttribute($value): void
    {
        $this->attributes['code'] = strtoupper(trim((string) $value));
    }

    /**
     * Financial statements pulled for this symbol (cross-linked by ticker).
     */
    public function financialStatements()
    {
        return $this->hasMany(FinancialStatement::class, 'symbol', 'code');
    }

    /**
     * Portable search (code or name) that works on both SQLite and MySQL.
     * Intentionally does NOT use the MySqlSearch fulltext trait.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('code', 'like', $term . '%')
              ->orWhere('name', 'like', '%' . $term . '%');
        });
    }
}
