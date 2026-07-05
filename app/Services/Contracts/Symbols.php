<?php
/**
 * Symbols interface
 *
 * @author: tuanha
 * @date: 27-July-2022
 */
namespace App\Services\Contracts;

interface Symbols
{
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
    public function getFullFinancialStatement(string $symbol, int $type, string $year, int $quarter, int $limit = 1);

    /**
     * Get fundamental information of a symbol
     *
     * @param string $symbol
     *
     * @return string | false | null
     */
    public function getFundamentals(string $symbol);

    /**
     * Basic master data (code, name, exchange, type, industry).
     *
     * @return array|null
     */
    public function getSymbol(string $symbol);

    /**
     * Company profile (overview, sector, headquarters, listing info).
     *
     * @return array|null
     */
    public function getProfile(string $symbol);

    /**
     * Fundamental snapshot as a decoded array (market cap, P/E, EPS, ...).
     *
     * @return array|null
     */
    public function getFundamentalsData(string $symbol);

    /**
     * Daily OHLCV history between two Y-m-d dates.
     *
     * @return array|null
     */
    public function getHistoricalQuotes(string $symbol, string $startDate, string $endDate, int $limit = 365);

    /**
     * The most recent daily quote.
     *
     * @return array|null
     */
    public function getLatestQuote(string $symbol);

    /**
     * Major shareholders.
     *
     * @return array|null
     */
    public function getHolders(string $symbol);

    /**
     * Dividend history.
     *
     * @return array|null
     */
    public function getDividends(string $symbol);
}
