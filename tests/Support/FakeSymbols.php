<?php

namespace Tests\Support;

use App\Services\Contracts\Symbols as SymbolsInterface;

/**
 * In-memory fake of the external Symbols service for tests — no network calls.
 * Knows about FPT and VNM; everything else is treated as "not found".
 */
class FakeSymbols implements SymbolsInterface
{
    private array $known = [
        'FPT' => ['symbol' => 'FPT', 'isListing' => true, 'name' => 'CTCP FPT', 'exchange' => 'HSX', 'type' => 'stock', 'industryCode' => '3570', 'icbCode' => '45102020'],
        'VNM' => ['symbol' => 'VNM', 'isListing' => true, 'name' => 'CTCP Sữa Việt Nam', 'exchange' => 'HSX', 'type' => 'stock', 'industryCode' => '3570', 'icbCode' => '45102020'],
    ];

    public function getFullFinancialStatement(string $symbol, int $type, string $year, int $quarter, int $limit = 1)
    {
        return json_encode([]);
    }

    public function getFundamentals(string $symbol)
    {
        return json_encode(['companyType' => 'stock']);
    }

    public function getSymbol(string $symbol)
    {
        return $this->known[strtoupper($symbol)] ?? null;
    }

    public function getProfile(string $symbol)
    {
        if (!isset($this->known[strtoupper($symbol)])) {
            return null;
        }
        return [
            'companyName'    => $this->known[strtoupper($symbol)]['name'],
            'exchange'       => 'HSX',
            'dateOfListing'  => '2006-12-13T00:00:00',
            'employees'      => 48000,
            'webAddress'     => 'https://fpt.com.vn',
            'charterCapital' => 14700000000000,
            'headQuarters'   => 'Ha Noi',
            'overview'       => 'A leading technology corporation.',
            'history'        => '<ul><li>1988: Th&agrave;nh lập</li><li>2006: Ni&ecirc;m yết HSX</li></ul>',
            'businessAreas'  => '<ul><li>C&ocirc;ng nghệ</li><li>Viễn th&ocirc;ng</li></ul>',
        ];
    }

    public function getFundamentalsData(string $symbol)
    {
        if (!isset($this->known[strtoupper($symbol)])) {
            return null;
        }
        return [
            'symbol'          => strtoupper($symbol),
            'marketCap'       => 124185669120000.0,
            'pe'              => 12.83,
            'eps'             => 5100.0,
            'dividendYield'   => 0.021,
            'low52Week'       => 90.0,
            'high52Week'      => 140.0,
            'foreignOwnership' => 0.49,
        ];
    }

    public function getHistoricalQuotes(string $symbol, string $startDate, string $endDate, int $limit = 365)
    {
        if (!isset($this->known[strtoupper($symbol)])) {
            return [];
        }
        // Most-recent-first, matching the real API ordering.
        return [
            ['date' => '2024-06-28T00:00:00', 'priceOpen' => 132.8, 'priceHigh' => 132.8, 'priceLow' => 130.4, 'priceClose' => 130.5, 'totalVolume' => 6633000.0],
            ['date' => '2024-06-27T00:00:00', 'priceOpen' => 131.0, 'priceHigh' => 133.0, 'priceLow' => 130.0, 'priceClose' => 132.8, 'totalVolume' => 5100000.0],
        ];
    }

    public function getLatestQuote(string $symbol)
    {
        $q = $this->getHistoricalQuotes($symbol, '', '');
        return $q[0] ?? null;
    }

    public function getHolders(string $symbol)
    {
        return [];
    }

    public function getDividends(string $symbol)
    {
        return [];
    }

    public function getEstimatedPrice(string $symbol)
    {
        $symbol = strtoupper($symbol);
        if ($symbol === 'FPT') {
            // composedPrice = Σ price × weight/100 = 70,300 (blend of 6 methods).
            return [
                'estimatedPriceDCF' => 67675.18, 'proportionDCF' => 43.53,
                'estimatedPricePE' => 76612.81, 'proportionPE' => 18.10,
                'estimatedPricePB' => 39614.70, 'proportionPB' => 3.72,
                'estimatedPriceGraham1' => 85719.75, 'proportionGraham1' => 7.41,
                'estimatedPriceGraham2' => 75956.99, 'proportionGraham2' => 20.20,
                'estimatedPriceGraham3' => 54075.84, 'proportionGraham3' => 7.04,
                'composedPrice' => 70300.0,
            ];
        }
        if ($symbol === 'VNM') {
            // Financial-institution-like: all methods null (no valuation available).
            return [
                'estimatedPriceDCF' => null, 'proportionDCF' => null,
                'estimatedPricePE' => null, 'proportionPE' => null,
                'estimatedPricePB' => null, 'proportionPB' => null,
                'estimatedPriceGraham1' => null, 'proportionGraham1' => null,
                'estimatedPriceGraham2' => null, 'proportionGraham2' => null,
                'estimatedPriceGraham3' => null, 'proportionGraham3' => null,
                'composedPrice' => null,
            ];
        }
        return null;
    }

    public function getFinancialIndicators(string $symbol)
    {
        if (strtoupper($symbol) !== 'FPT') {
            return [];
        }
        return [
            ['shortName' => 'P/E', 'name' => 'P/E', 'groupName' => 'Định giá', 'value' => 12.48, 'industryValue' => 12.67],
            ['shortName' => 'P/S', 'name' => 'P/S', 'groupName' => 'Định giá', 'value' => 1.82, 'industryValue' => 1.58],
            ['shortName' => 'P/B', 'name' => 'P/B', 'groupName' => 'Định giá', 'value' => 3.10, 'industryValue' => 2.75],
            ['shortName' => 'ROE', 'name' => 'ROE (%)', 'groupName' => 'Hiệu quả quản lý', 'value' => 24.82, 'industryValue' => 21.25],
        ];
    }
}
