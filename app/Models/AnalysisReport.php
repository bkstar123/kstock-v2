<?php
/**
 * AnalysisReport
 *
 * @author: tuanha
 * @date: 11-Aug-2022
 */
namespace App\Models;

use App\Models\FinancialStatement;
use Illuminate\Database\Eloquent\Model;
use App\Models\Behaviors\AnalysisReportRepository;

class AnalysisReport extends Model
{
    use AnalysisReportRepository;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'content', 'financial_statement_id'
    ];

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
