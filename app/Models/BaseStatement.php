<?php
/**
 * BalanceStatement
 *
 * @author: tuanha
 * @date: 11-Aug-2022
 */
namespace App\Models;

use App\Models\FinancialStatement;
use Illuminate\Database\Eloquent\Model;
use App\Models\Behaviors\StatementRepository;

class BaseStatement extends Model
{
    use StatementRepository;

    /**
     * A balance statement belongs to a financial statement
     *
     * @return @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function financialStatement()
    {
        return $this->belongsTo(FinancialStatement::class);
    }
}
