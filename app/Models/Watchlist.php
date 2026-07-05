<?php

namespace App\Models;

use Bkstar123\BksCMS\AdminPanel\Admin;
use Illuminate\Database\Eloquent\Model;

class Watchlist extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'admin_id', 'symbol_code',
    ];

    public function setSymbolCodeAttribute($value): void
    {
        $this->attributes['symbol_code'] = strtoupper(trim((string) $value));
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    /**
     * The master Symbol row, if it has been synced locally.
     */
    public function symbol()
    {
        return $this->belongsTo(Symbol::class, 'symbol_code', 'code');
    }
}
