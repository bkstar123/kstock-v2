<?php

namespace Tests\Unit;

use App\ContentObjects\StatementItem;
use Tests\TestCase;

/**
 * Tests the time-series accessors on a single financial-statement line item.
 * Extends the framework TestCase because the class relies on the Arr facade
 * and config() during value lookups.
 */
class StatementItemTest extends TestCase
{
    private function makeItem(): StatementItem
    {
        // Three consecutive periods with known values.
        $values = [
            ['period' => '2021-Q3', 'year' => 2021, 'quarter' => 3, 'value' => 100],
            ['period' => '2021-Q4', 'year' => 2021, 'quarter' => 4, 'value' => 200],
            ['period' => '2022-Q1', 'year' => 2022, 'quarter' => 1, 'value' => 300],
        ];

        return new StatementItem('2', 'Total assets', null, false, 0, 'value', $values);
    }

    public function test_get_value_returns_matching_period_as_float()
    {
        $this->assertSame(300.0, $this->makeItem()->getValue(2022, 1));
        $this->assertSame(100.0, $this->makeItem()->getValue(2021, 3));
    }

    public function test_get_value_returns_zero_for_missing_period()
    {
        $this->assertSame(0.0, $this->makeItem()->getValue(2099, 1));
    }

    public function test_get_average_value_between_current_and_prior_period()
    {
        // avg of 2022-Q1 (300) and its previous period 2021-Q4 (200) = 250
        $this->assertSame(250.0, $this->makeItem()->getAverageValue(2022, 1, 1));
    }

    public function test_get_average_value_falls_back_to_current_when_past_missing()
    {
        // previous period of 2021-Q3 is 2021-Q2 which has no data -> returns current
        $this->assertSame(100.0, $this->makeItem()->getAverageValue(2021, 3, 1));
    }

    public function test_get_differential_value_from_past_period()
    {
        // 2022-Q1 (300) - 2021-Q4 (200) = 100
        $this->assertSame(100.0, $this->makeItem()->getDifferentialValueFromPastPeriod(2022, 1, 1));
    }

    public function test_get_accumulated_value_sums_over_the_window()
    {
        // 300 + 200 + 100 = 600 walking back two periods from 2022-Q1
        $this->assertSame(600.0, $this->makeItem()->getAccumulatedValueFromPastPeriod(2022, 1, 2));
    }
}
