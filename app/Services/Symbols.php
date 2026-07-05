<?php
/**
 * Symbols service - Interact with API endpoints that relate to enterprise securities symbols
 *
 * @author: tuanha
 * @date: 27-July-2022
 */
namespace App\Services;

use Exception;
use App\Services\Base;
use App\Services\Contracts\Symbols as SymbolsInterface;
use Illuminate\Support\Facades\Cache;

class Symbols extends Base implements SymbolsInterface
{
    /**
     * Cached JSON-decoded GET. Never caches a failed (null) response so a
     * transient API error does not get pinned for the whole TTL.
     *
     * @param  string  $key
     * @param  string  $path
     * @param  int  $ttl  seconds
     * @return array|null
     */
    protected function cachedGet(string $key, string $path, int $ttl)
    {
        $cached = Cache::get($key);
        if ($cached !== null) {
            return $cached;
        }
        $data = $this->getDecoded($path);
        if ($data !== null) {
            Cache::put($key, $data, $ttl);
        }
        return $data;
    }

    /**
     * Basic master data for a symbol: code, name, exchange, type, industry.
     * External API: GET /symbols/{symbol}
     *
     * @return array|null
     */
    public function getSymbol(string $symbol)
    {
        $enc = rawurlencode($symbol);
        return $this->cachedGet("fa:symbol:$enc", "/symbols/$enc", 43200);
    }

    /**
     * Company profile (overview, sector, headquarters, listing info).
     * External API: GET /symbols/{symbol}/profile
     *
     * @return array|null
     */
    public function getProfile(string $symbol)
    {
        $enc = rawurlencode($symbol);
        return $this->cachedGet("fa:profile:$enc", "/symbols/$enc/profile", 43200);
    }

    /**
     * Fundamental snapshot as a decoded array (market cap, P/E, EPS, yield,
     * 52-week range, ownership...). This is separate from getFundamentals(),
     * which returns the raw body string consumed by the analysis job.
     * External API: GET /symbols/{symbol}/fundamental
     *
     * @return array|null
     */
    public function getFundamentalsData(string $symbol)
    {
        $enc = rawurlencode($symbol);
        return $this->cachedGet("fa:fundamental:$enc", "/symbols/$enc/fundamental", 900);
    }

    /**
     * Daily OHLCV history between two dates (Y-m-d). Requires start & end dates.
     * External API: GET /symbols/{symbol}/historical-quotes?startDate=&endDate=&offset=&limit=
     *
     * @return array|null
     */
    public function getHistoricalQuotes(string $symbol, string $startDate, string $endDate, int $limit = 365)
    {
        $enc = rawurlencode($symbol);
        $path = "/symbols/$enc/historical-quotes?startDate=" . rawurlencode($startDate)
              . "&endDate=" . rawurlencode($endDate) . "&offset=0&limit=$limit";
        return $this->cachedGet("fa:quotes:$enc:$startDate:$endDate:$limit", $path, 900);
    }

    /**
     * The most recent daily quote (API returns most-recent-first).
     *
     * @return array|null
     */
    public function getLatestQuote(string $symbol)
    {
        $end = date('Y-m-d');
        $start = date('Y-m-d', strtotime('-12 days'));
        $quotes = $this->getHistoricalQuotes($symbol, $start, $end, 12);
        return (is_array($quotes) && !empty($quotes)) ? $quotes[0] : null;
    }

    /**
     * Major shareholders. External API: GET /symbols/{symbol}/holders
     *
     * @return array|null
     */
    public function getHolders(string $symbol)
    {
        $enc = rawurlencode($symbol);
        return $this->cachedGet("fa:holders:$enc", "/symbols/$enc/holders", 43200);
    }

    /**
     * Dividend history. External API: GET /symbols/{symbol}/dividends
     *
     * @return array|null
     */
    public function getDividends(string $symbol)
    {
        $enc = rawurlencode($symbol);
        return $this->cachedGet("fa:dividends:$enc", "/symbols/$enc/dividends", 43200);
    }

    /**
     * Get full financial statement of a symbol
     *
     * @param string $symbol
     * @param int $type
     * @param string $year
     * @param int $quarter
     * @param int $limit
     *
     * @return string | false | null
     */
    public function getFullFinancialStatement(string $symbol, int $type, string $year, int $quarter, int $limit = 1)
    {
        $symbol = rawurlencode($symbol);
        $path = "/symbols/$symbol/full-financial-reports?type=$type&year=$year&quarter=$quarter&limit=$limit";
        try {
            $res = $this->client->request('GET', $path);
            if ($res->getStatusCode() == '200') {
                return $res->getBody()->getContents();
            } else {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get fundamental information of a symbol
     *
     * @param string $symbol
     *
     * @return string | false | null
     */
    public function getFundamentals(string $symbol)
    {
        $symbol = rawurlencode($symbol);
        $path = "/symbols/$symbol/fundamental";
        try {
            $res = $this->client->request('GET', $path);
            if ($res->getStatusCode() == '200') {
                return $res->getBody()->getContents();
            } else {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
    }
}
