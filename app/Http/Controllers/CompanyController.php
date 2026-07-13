<?php
/**
 * CompanyController - symbol directory + per-company profile hub.
 *
 * @author: kstock
 */
namespace App\Http\Controllers;

use App\Models\FinancialStatement;
use App\Models\Symbol;
use App\Models\Watchlist;
use App\Services\Contracts\Symbols as SymbolsInterface;
use App\Services\SymbolCatalog;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CompanyController extends Controller
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

    /**
     * Searchable symbol directory (local master table).
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $exchange = $request->input('exchange');

        $companies = Symbol::search($search)
            ->when($exchange, fn ($q) => $q->where('exchange', $exchange))
            ->orderBy('code')
            ->paginate(20)
            ->withQueryString();

        $exchanges = Symbol::query()
            ->whereNotNull('exchange')
            ->distinct()
            ->orderBy('exchange')
            ->pluck('exchange');

        return view('cms.companies.index', compact('companies', 'exchanges', 'search', 'exchange'));
    }

    /**
     * Resolve a ticker against the external API and add it to the directory.
     */
    public function store(Request $request)
    {
        $request->validate([
            'symbol' => ['required', 'string', 'regex:/^[A-Za-z0-9.]{1,20}$/'],
        ]);

        $symbol = $this->catalog->sync($request->input('symbol'));

        if (!$symbol) {
            flashing('No such symbol was found on the data provider')->error()->flash();
            return back();
        }

        flashing("{$symbol->code} has been added to the directory")->success()->flash();
        return redirect()->route('cms.companies.show', ['code' => $symbol->code]);
    }

    /**
     * Company profile hub.
     */
    public function show(string $code)
    {
        $symbol = $this->catalog->remember($code);

        if (!$symbol) {
            flashing('No such symbol was found')->error()->flash();
            return redirect()->route('cms.companies.index');
        }

        $profile      = $this->symbols->getProfile($symbol->code);
        $fundamentals = $this->symbols->getFundamentalsData($symbol->code);
        $latestQuote  = $this->symbols->getLatestQuote($symbol->code);

        $statements = FinancialStatement::where('symbol', $symbol->code)
            ->orderByDesc('year')->orderByDesc('quarter')
            ->get();

        $inWatchlist = Watchlist::where('admin_id', auth()->guard('admins')->user()->id)
            ->where('symbol_code', $symbol->code)
            ->exists();

        $priceToBook = $this->priceToBook($fundamentals, $statements->first());
        $valuation = $this->valuation($this->symbols->getEstimatedPrice($symbol->code), $latestQuote);
        $valuationRatios = $this->valuationRatios($this->symbols->getFinancialIndicators($symbol->code));

        return view('cms.companies.show', compact(
            'symbol', 'profile', 'fundamentals', 'latestQuote', 'statements', 'inWatchlist',
            'priceToBook', 'valuation', 'valuationRatios'
        ));
    }

    /**
     * Extract the P/E, P/S and P/B valuation multiples (company `value` vs peer
     * `industryValue`) from the financial-indicators list, keeping that order.
     * Returns null when none are present.
     *
     * @param  array|null  $indicators
     * @return array<string, array{company: float|null, industry: float|null}>|null
     */
    private function valuationRatios($indicators)
    {
        if (!is_array($indicators)) {
            return null;
        }
        $wanted = ['P/E', 'P/S', 'P/B'];
        $found = [];
        foreach ($indicators as $it) {
            $short = $it['shortName'] ?? null;
            if (in_array($short, $wanted, true) && !isset($found[$short])) {
                $found[$short] = [
                    'company'  => isset($it['value']) && is_numeric($it['value']) ? (float) $it['value'] : null,
                    'industry' => isset($it['industryValue']) && is_numeric($it['industryValue']) ? (float) $it['industryValue'] : null,
                ];
            }
        }
        $ordered = [];
        foreach ($wanted as $w) {
            if (isset($found[$w])) {
                $ordered[$w] = $found[$w];
            }
        }
        return $ordered ?: null;
    }

    /**
     * Build the valuation view-model from the external estimated-price payload
     * (weighted DCF/PE/PB/Graham blend). Returns null when no fair value is
     * available (financial institutions and unknown symbols return all-null).
     * Estimated prices are full VND; the quote's priceClose is in thousands, so the
     * current price is scaled by 1000 for a like-for-like upside/downside.
     *
     * @param  array|null  $estimated
     * @param  array|null  $latestQuote
     * @return array{fair: float, current: float|null, upsidePct: float|null, methods: array}|null
     */
    private function valuation($estimated, $latestQuote)
    {
        if (!is_array($estimated) || !isset($estimated['composedPrice']) || !is_numeric($estimated['composedPrice'])) {
            return null;
        }
        $fair = (float) $estimated['composedPrice'];

        $current = (isset($latestQuote['priceClose']) && is_numeric($latestQuote['priceClose']))
            ? (float) $latestQuote['priceClose'] * 1000
            : null;
        $upsidePct = ($current && $current != 0) ? round(($fair - $current) / $current * 100, 1) : null;

        $labels = [
            'DCF' => 'DCF', 'PE' => 'P/E', 'PB' => 'P/B',
            'Graham1' => 'Graham 1', 'Graham2' => 'Graham 2', 'Graham3' => 'Graham 3',
        ];
        $methods = [];
        foreach ($labels as $key => $label) {
            $price = $estimated["estimatedPrice{$key}"] ?? null;
            $weight = $estimated["proportion{$key}"] ?? null;
            if (is_numeric($price) && is_numeric($weight)) {
                $methods[] = ['label' => $label, 'price' => (float) $price, 'weight' => (float) $weight];
            }
        }
        usort($methods, fn ($a, $b) => $b['weight'] <=> $a['weight']);

        return ['fair' => $fair, 'current' => $current, 'upsidePct' => $upsidePct, 'methods' => $methods];
    }

    /**
     * Trailing Price-to-Book, derived (the external fundamental endpoint doesn't
     * expose P/B): current market cap / book value of equity attributable to the
     * PARENT company = total equity (item 302) minus non-controlling interests
     * (item 3020114), so the denominator matches the parent's listed shares behind
     * the market cap. Book value is taken from the latest pulled statement, so the
     * result carries the book period and a "stale" flag: today's market cap paired
     * with an old book value (e.g. a 2024 annual report) can misstate P/B.
     *
     * Market cap and statement values share the same full-VND scale. Returns null
     * when market cap or a balance statement is unavailable, or parent equity is
     * non-positive (P/B not meaningful).
     *
     * @param  array|null  $fundamentals
     * @param  \App\Models\FinancialStatement|null  $latest
     * @return array{value: float, period: string, stale: bool}|null
     */
    private function priceToBook($fundamentals, $latest)
    {
        $marketCap = $fundamentals['marketCap'] ?? null;
        if (!is_numeric($marketCap) || !$latest || !$latest->balance_statement) {
            return null;
        }
        $equityItem = $latest->balance_statement->getItem('302');
        if (!$equityItem) {
            return null;
        }
        $totalEquity = $equityItem->getValue($latest->year, $latest->quarter);
        $nciItem = $latest->balance_statement->getItem('3020114'); // Lợi ích cổ đông không kiểm soát
        $nci = $nciItem ? $nciItem->getValue($latest->year, $latest->quarter) : 0.0;
        $parentEquity = $totalEquity - $nci;
        if ($parentEquity <= 0) {
            return null;
        }
        return [
            'value'  => round($marketCap / $parentEquity, 2),
            'period' => $latest->quarter != 0 ? "Q{$latest->quarter} {$latest->year}" : "{$latest->year}",
            'stale'  => $this->bookValueIsStale($latest->year, $latest->quarter),
        ];
    }

    /**
     * A book value is "stale" for P/B once its reporting period ended more than two
     * quarters (~6 months) ago — today's market cap is then paired with a clearly
     * outdated equity figure. Pulling a newer report refreshes it.
     */
    private function bookValueIsStale($year, $quarter)
    {
        $endMonth = $quarter == 0 ? 12 : $quarter * 3;
        $periodEnd = Carbon::create((int) $year, $endMonth, 1)->endOfMonth();
        return $periodEnd->lt(Carbon::now()->subMonths(6));
    }

    /**
     * OHLCV history as JSON for the price chart.
     */
    public function priceHistory(Request $request, string $code)
    {
        $code = strtoupper($code);
        $ranges = [
            '1m' => '-1 month', '3m' => '-3 months', '6m' => '-6 months',
            '1y' => '-1 year', '3y' => '-3 years',
        ];
        $range = $request->input('range', '1y');
        $modifier = $ranges[$range] ?? $ranges['1y'];

        $end = date('Y-m-d');
        $start = date('Y-m-d', strtotime($modifier));
        $quotes = $this->symbols->getHistoricalQuotes($code, $start, $end, 2000) ?: [];

        // Normalise to ascending [timestamp, o, h, l, c] + volume series for Highcharts.
        $ohlc = [];
        $volume = [];
        foreach (array_reverse($quotes) as $q) {
            if (empty($q['date'])) {
                continue;
            }
            $ts = strtotime($q['date']) * 1000;
            $ohlc[] = [$ts, $q['priceOpen'] ?? null, $q['priceHigh'] ?? null, $q['priceLow'] ?? null, $q['priceClose'] ?? null];
            $volume[] = [$ts, $q['totalVolume'] ?? 0];
        }

        return response()->json([
            'code'   => $code,
            'range'  => $range,
            'ohlc'   => $ohlc,
            'volume' => $volume,
        ]);
    }
}
