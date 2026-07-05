<?php

namespace Tests\Unit;

use App\Models\FinancialStatement;
use Tests\TestCase;

class FinancialStatementModelTest extends TestCase
{
    public function test_symbol_is_normalized_to_uppercase()
    {
        $statement = new FinancialStatement(['symbol' => 'fpt']);

        $this->assertSame('FPT', $statement->symbol);
    }

    public function test_symbol_mutator_applies_on_direct_assignment()
    {
        $statement = new FinancialStatement();
        $statement->symbol = 'hvn';

        $this->assertSame('HVN', $statement->symbol);
    }
}
