<?php
/**
 * ComparisonController - so sánh các mã cổ phiếu cạnh nhau theo bộ chỉ số tài chính.
 * Nguồn: AnalysisReport (kỳ gần nhất mỗi mã) + định giá thời gian thực từ Symbols service.
 */
namespace App\Http\Controllers;

use App\Models\FinancialStatement;
use App\Services\Contracts\Symbols as SymbolsInterface;
use App\Services\SymbolCatalog;
use Illuminate\Http\Request;

class ComparisonController extends Controller
{
    /**
     * @var SymbolsInterface
     */
    protected $symbols;

    /**
     * @var SymbolCatalog
     */
    protected $catalog;

    public function __construct(SymbolsInterface $symbols, SymbolCatalog $catalog)
    {
        $this->symbols = $symbols;
        $this->catalog = $catalog;
    }

    public function index(Request $request)
    {
        // Kỳ gần nhất của mỗi symbol có phân tích -> danh sách chọn được.
        $available = FinancialStatement::with('analysis_report')
            ->get()
            ->filter(fn ($fs) => !empty($fs->analysis_report))
            ->sortByDesc(fn ($fs) => sprintf('%04d%d', $fs->year, $fs->quarter))
            ->groupBy('symbol')
            ->map(function ($statements) {
                $fs = $statements->first(); // mới nhất (đã sort desc)
                return [
                    'code'   => $fs->symbol,
                    'type'   => institutionType($fs->analysis_report),
                    'period' => $fs->quarter ? "Q{$fs->quarter} {$fs->year}" : (string) $fs->year,
                    'fs'     => $fs,
                ];
            })
            ->sortKeys()
            ->values();

        $requested = (array) $request->input('symbols', []);
        $selected = $available->filter(fn ($row) => in_array($row['code'], $requested, true))->values();

        $rows = [];
        $mixedTypes = false;
        if ($selected->count()) {
            $mixedTypes = $selected->pluck('type')->unique()->count() > 1;
            // Nạp fundamentals (định giá) + xác định mô hình Altman phù hợp theo ngành.
            $selected = $selected->map(function ($row) {
                $fund = $this->symbols->getFundamentalsData($row['code']);
                $row['fund'] = is_array($fund) ? $fund : [];
                $symbol = $this->catalog->remember($row['code']);
                $sectorClass = $symbol ? businessSectorClass($symbol->industry_code) : null;
                // Z-Score cho DN sản xuất, Z2-Score cho phi sản xuất (mặc định Z-Score nếu chưa rõ).
                $row['zAlias'] = businessSectorZAlias($sectorClass) ?? 'Z-Score';
                return $row;
            });
            $rows = $this->buildRows($selected);
        }

        return view('cms.compare.index', [
            'available'  => $available,
            'selected'   => $selected,
            'rows'       => $rows,
            'mixedTypes' => $mixedTypes,
        ]);
    }

    /**
     * Dựng các nhóm/hàng so sánh từ catalog; ẩn hàng toàn "—", ẩn nhóm rỗng.
     */
    private function buildRows($selected)
    {
        $groups = [];
        foreach (comparisonMetricCatalog() as $metric) {
            $rawValues = [];
            $cells = [];
            foreach ($selected as $row) {
                [$raw, $display, $zone] = $this->cellValue($metric, $row);
                $rawValues[] = $raw;
                $cells[] = ['raw' => $raw, 'display' => $display, 'zone' => $zone];
            }
            // Ẩn hàng nếu không mã nào có dữ liệu.
            if (!count(array_filter($rawValues, 'is_numeric'))) {
                continue;
            }
            $hl = comparisonRowHighlight($rawValues, $metric['better'] ?? null);
            foreach ($cells as $i => &$cell) {
                $cell['tone'] = ($hl['best'] === $i) ? 'good' : (($hl['worst'] === $i) ? 'bad' : null);
            }
            unset($cell);

            $groups[$metric['group']][] = [
                'label'     => $metric['label'],
                'unit'      => financialUnitLabel($metric['unit']),
                'unitCode'  => $metric['unit'],
                'chartable' => !empty($metric['chart']),
                'cells'     => $cells,
            ];
        }
        return $groups;
    }

    /**
     * Trả [raw(float|null), display(string), zone(array|null)] cho 1 ô.
     */
    private function cellValue(array $metric, array $row)
    {
        if (($metric['source'] ?? 'ratio') === 'fundamental') {
            $raw = $row['fund'][$metric['key']] ?? null;
            $raw = is_numeric($raw) ? (float) $raw : null;
            return [$raw, $this->formatFundamental($metric['key'], $raw), null];
        }

        // source = ratio
        $type = $row['type'] ?? null;
        $alias = $metric['alias'][$type ?? 'normal'] ?? null;
        // Altman Z: chọn Z-Score (sản xuất) / Z2-Score (phi sản xuất) theo ngành từng mã.
        if (!empty($metric['zscore']) && $alias !== null) {
            $alias = $row['zAlias'] ?? 'Z-Score';
        }
        if ($alias === null) {
            return [null, '—', null];
        }
        $item = optional($row['fs']->analysis_report)->getItem($alias);
        $val = ($item && !empty($item->values)) ? ($item->values[0]['value'] ?? null) : null;
        $raw = is_numeric($val) ? (float) $val : null;
        $zone = (!empty($metric['zone']) && $raw !== null) ? analysisScoreZone($alias, $raw) : null;
        return [$raw, formatFinancialValue($val, $metric['unit']), $zone];
    }

    private function formatFundamental($key, $raw)
    {
        if ($raw === null) {
            return '—';
        }
        switch ($key) {
            case 'marketCap':      return number_format($raw / 1e9, 0) . ' tỷ';
            case 'eps':            return number_format($raw, 0) . ' đ';
            case 'dividendYield':  return number_format($raw * 100, 2) . '%';
            default:               return number_format($raw, 2); // pe
        }
    }
}
