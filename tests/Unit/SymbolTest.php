<?php

namespace Tests\Unit;

use App\Models\Symbol;
use Tests\TestCase;

class SymbolTest extends TestCase
{
    public function test_code_is_normalized_to_uppercase()
    {
        $symbol = new Symbol(['code' => 'fpt']);

        $this->assertSame('FPT', $symbol->code);
    }

    public function test_symbol_code_mutator_trims_and_uppercases()
    {
        $symbol = new Symbol();
        $symbol->code = '  vnm ';

        $this->assertSame('VNM', $symbol->code);
    }
}
