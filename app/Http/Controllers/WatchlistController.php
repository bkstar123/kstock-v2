<?php
/**
 * WatchlistController - per-admin followed symbols.
 *
 * @author: kstock
 */
namespace App\Http\Controllers;

use App\Models\Watchlist;
use App\Services\Contracts\Symbols as SymbolsInterface;
use App\Services\SymbolCatalog;
use Illuminate\Http\Request;

class WatchlistController extends Controller
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
     * The authenticated admin's watchlist with a live-ish quote + key ratios.
     */
    public function index()
    {
        $rows = Watchlist::with('symbol')
            ->where('admin_id', auth()->guard('admins')->user()->id)
            ->orderBy('symbol_code')
            ->get()
            ->map(function (Watchlist $item) {
                $quote = $this->symbols->getLatestQuote($item->symbol_code);
                $fund  = $this->symbols->getFundamentalsData($item->symbol_code);
                return [
                    'code'        => $item->symbol_code,
                    'name'        => optional($item->symbol)->name,
                    'exchange'    => optional($item->symbol)->exchange,
                    'price'       => $quote['priceClose'] ?? null,
                    'volume'      => $quote['totalVolume'] ?? null,
                    'pe'          => $fund['pe'] ?? null,
                    'marketCap'   => $fund['marketCap'] ?? null,
                    'dividendYld' => $fund['dividendYield'] ?? null,
                ];
            });

        return view('cms.watchlist.index', compact('rows'));
    }

    /**
     * Follow a symbol.
     */
    public function store(Request $request)
    {
        $request->validate([
            'symbol' => ['required', 'string', 'regex:/^[A-Za-z0-9.]{1,20}$/'],
        ]);

        $code = strtoupper($request->input('symbol'));

        // Make sure it is a real ticker (and cache its master row).
        if (!$this->catalog->remember($code)) {
            flashing('No such symbol was found on the data provider')->error()->flash();
            return back();
        }

        Watchlist::firstOrCreate([
            'admin_id'    => auth()->guard('admins')->user()->id,
            'symbol_code' => $code,
        ]);

        flashing("{$code} added to your watchlist")->success()->flash();
        return back();
    }

    /**
     * Unfollow a symbol.
     */
    public function destroy(string $code)
    {
        Watchlist::where('admin_id', auth()->guard('admins')->user()->id)
            ->where('symbol_code', strtoupper($code))
            ->delete();

        flashing(strtoupper($code) . ' removed from your watchlist')->flash();
        return back();
    }
}
