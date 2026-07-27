<?php
/**
 * CompanyController - symbol directory + per-company profile hub.
 *
 * @author: kstock
 */
namespace App\Http\Controllers;

use App\Models\DirectoryEntry;
use App\Models\FinancialStatement;
use App\Models\Symbol;
use App\Models\Watchlist;
use App\Services\Contracts\Symbols as SymbolsInterface;
use App\Services\SymbolCatalog;
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
     * Searchable symbol directory. Strictly personalised: every admin — superadmin
     * included — sees only the symbols in their own `directory_entries`.
     *
     * This is a DELIBERATE departure from the financial-statement listing, which
     * still lets a superadmin see everything (AuthServiceProvider gates and
     * SymbolController::listFinancialStatements). The directory is a personal
     * working list, not an administrative view of the shared catalog — so there is
     * no superadmin bypass here, and none should be added.
     *
     * Because the listing always filters through `directory_entries`, an orphaned
     * `symbols` row (one nobody references any more) is simply unreachable from
     * this page. That is what makes SymbolCatalog::release() housekeeping rather
     * than a correctness requirement.
     */
    public function index(Request $request)
    {
        $search   = $request->input('search');
        $exchange = $request->input('exchange');
        $meId     = auth()->guard('admins')->user()->id;

        $companies = Symbol::search($search)
            ->inDirectoryOf($meId)
            ->when($exchange, fn ($q) => $q->where('exchange', $exchange))
            ->orderBy('code')
            ->paginate(20)
            ->withQueryString();

        // The facet list must be scoped identically, or the dropdown offers
        // exchanges the admin has no symbols on.
        $exchanges = Symbol::inDirectoryOf($meId)
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

        DirectoryEntry::firstOrCreate([
            'admin_id'    => auth()->guard('admins')->user()->id,
            'symbol_code' => $symbol->code,
        ]);

        flashing("{$symbol->code} has been added to your directory")->success()->flash();
        return redirect()->route('cms.companies.show', ['code' => $symbol->code]);
    }

    /**
     * Remove a symbol from the current admin's own directory.
     *
     * Scope is strictly the caller's own directory entry: their watchlist entry and
     * their financial statements for the ticker are deliberately left alone, as are
     * every other admin's rows. The shared master `symbols` row is then reference-
     * counted and purged only if nothing anywhere still points at it.
     */
    public function destroy(string $code)
    {
        $code = strtoupper(trim($code));

        // The $deleted guard is LOAD-BEARING FOR SECURITY, not just for the flash
        // message. This route carries no `can:` middleware (see routes/web.php), so
        // without it any authenticated admin could hit DELETE /cms/companies/{code}
        // for a ticker they never added and trigger the catalog purge for it.
        $deleted = DirectoryEntry::where('admin_id', auth()->guard('admins')->user()->id)
            ->whereRaw('UPPER(symbol_code) = ?', [$code])
            ->delete();

        if (!$deleted) {
            flashing("{$code} is not in your directory")->error()->flash();
            return redirect()->route('cms.companies.index');
        }

        $purged = $this->catalog->release($code);

        flashing($purged
            ? "{$code} has been removed from your directory and purged from the catalog"
            : "{$code} has been removed from your directory (it is still referenced elsewhere, so the catalog entry was kept)"
        )->success()->flash();

        // Never back(): the company profile page posts this same DELETE, and going
        // back there re-enters show(), whose remember() call would immediately
        // re-create the row we just purged from the 12h-cached API payload.
        return redirect()->route('cms.companies.index');
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

        // Scoped to the viewer's own library (superadmin sees all), because every row
        // links to cms.financial.statements.show, which is gated on holding — an
        // unscoped list renders links that 403. withOnly() because this table shows
        // only year/quarter/puller and must not drag ~330 KB of blobs per row.
        $statements = FinancialStatement::where('symbol', $symbol->code)
            ->visibleTo(auth()->guard('admins')->user())
            ->withOnly('lastPulledBy')
            ->orderByDesc('year')->orderByDesc('quarter')->orderByDesc('id')
            ->get();

        $inWatchlist = Watchlist::where('admin_id', auth()->guard('admins')->user()->id)
            ->where('symbol_code', $symbol->code)
            ->exists();

        $inDirectory = DirectoryEntry::where('admin_id', auth()->guard('admins')->user()->id)
            ->where('symbol_code', $symbol->code)
            ->exists();

        $valuation = $this->valuation($this->symbols->getEstimatedPrice($symbol->code), $latestQuote);
        $valuationRatios = $this->valuationRatios($this->symbols->getFinancialIndicators($symbol->code));
        // P/B for the headline card now comes straight from the API (fresh, and
        // available for every company type) rather than being derived from equity.
        $priceToBook = $valuationRatios['P/B']['company'] ?? null;

        return view('cms.companies.show', compact(
            'symbol', 'profile', 'fundamentals', 'latestQuote', 'statements', 'inWatchlist',
            'inDirectory', 'priceToBook', 'valuation', 'valuationRatios'
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
