<?php
/**
 * CashFlowStatement
 *
 * @author: tuanha
 * @date: 11-Aug-2022
 */
namespace App\Models;

use App\Models\BaseStatement;

class CashFlowStatement extends BaseStatement
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'content', 'financial_statement_id'
    ];
}
