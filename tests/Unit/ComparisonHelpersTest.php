<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Kiểm thử helper so sánh mã cổ phiếu (catalog + tô best/worst). Hàm thuần, autoload
 * qua composer "files" nên không cần boot Laravel.
 */
class ComparisonHelpersTest extends TestCase
{
    public function test_catalog_has_required_shape_and_mappings()
    {
        $catalog = comparisonMetricCatalog();
        $this->assertNotEmpty($catalog);
        foreach ($catalog as $m) {
            $this->assertArrayHasKey('group', $m);
            $this->assertArrayHasKey('label', $m);
            $this->assertArrayHasKey('unit', $m);
            $this->assertArrayHasKey('source', $m);
        }
        // ROE ánh xạ đúng alias theo loại.
        $roe = collect($catalog)->firstWhere('label', 'ROE');
        $this->assertSame('ROE', $roe['alias']['normal']);
        $this->assertSame('BANK_ROEA', $roe['alias']['bank']);
        $this->assertSame('SEC_ROEA', $roe['alias']['securities']);
        // Có đủ 3 nhóm người dùng yêu cầu.
        $groups = collect($catalog)->pluck('group')->unique()->all();
        $this->assertContains('Hiệu quả hoạt động', $groups);
        $this->assertContains('Đòn bẩy tài chính', $groups);
        $this->assertContains('Chất lượng tài sản', $groups);
    }

    public function test_highlight_high_is_better()
    {
        $hl = comparisonRowHighlight([10.0, 20.0, 30.0], 'high');
        $this->assertSame(2, $hl['best']);
        $this->assertSame(0, $hl['worst']);
    }

    public function test_highlight_low_is_better_ignores_nulls()
    {
        $hl = comparisonRowHighlight([null, 5.0, 10.0], 'low');
        $this->assertSame(1, $hl['best']);   // 5 thấp nhất
        $this->assertSame(2, $hl['worst']);  // 10 cao nhất
    }

    public function test_highlight_returns_none_when_not_applicable()
    {
        $this->assertSame(['best' => null, 'worst' => null], comparisonRowHighlight([10.0, 20.0], null));
        $this->assertSame(['best' => null, 'worst' => null], comparisonRowHighlight([7.0], 'high'));
        $this->assertSame(['best' => null, 'worst' => null], comparisonRowHighlight([5.0, 5.0, 5.0], 'high'));
    }
}
