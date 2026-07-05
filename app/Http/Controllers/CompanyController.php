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

        return view('cms.companies.show', compact(
            'symbol', 'profile', 'fundamentals', 'latestQuote', 'statements', 'inWatchlist'
        ));
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
