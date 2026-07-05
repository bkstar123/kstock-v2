/*
 | KStock — company price chart (Highcharts base; area + volume).
 | Highstock/candlestick isn't bundled, so we draw a close-price area chart
 | with a volume column on a secondary axis. Lazy-loads when the Price tab is
 | first shown (Highcharts can't size a hidden container).
 */
(function () {
    if (typeof window.jQuery === 'undefined') { return; }
    var $ = window.jQuery;
    var chart = null, loaded = false, currentRange = '1y';

    function render(range) {
        currentRange = range || currentRange;
        $('#price-chart').html('<div class="text-center text-muted p-5">Loading price history…</div>');
        $.getJSON(window.ksPriceHistoryUrl + '?range=' + currentRange)
            .done(function (data) {
                var ohlc = data.ohlc || [];
                var closes = ohlc.map(function (r) { return [r[0], r[4]]; });
                var volume = data.volume || [];
                if (!closes.length) {
                    $('#price-chart').html('<div class="text-center text-muted p-5">No price data available for this symbol.</div>');
                    return;
                }
                $('#price-chart').empty();
                chart = window.Highcharts.chart('price-chart', {
                    chart: { zoomType: 'x' },
                    title: { text: null },
                    credits: { enabled: false },
                    legend: { enabled: false },
                    tooltip: { shared: true },
                    xAxis: { type: 'datetime' },
                    yAxis: [
                        { title: { text: 'Price' }, height: '70%', lineWidth: 1 },
                        { title: { text: 'Volume' }, top: '75%', height: '25%', offset: 0, lineWidth: 1 }
                    ],
                    series: [
                        {
                            type: 'area', name: window.ksSymbolCode, data: closes, yAxis: 0,
                            fillOpacity: 0.15, lineWidth: 2, tooltip: { valueDecimals: 1 }
                        },
                        { type: 'column', name: 'Volume', data: volume, yAxis: 1 }
                    ]
                });
            })
            .fail(function () {
                $('#price-chart').html('<div class="text-center text-danger p-5">Failed to load price history.</div>');
            });
    }

    $(document).on('shown.bs.tab', 'a[href="#tab-price"]', function () {
        if (!loaded) { loaded = true; render(currentRange); }
        else if (chart) { chart.reflow(); }
    });

    $('#price-range').on('click', 'button', function () {
        $('#price-range button').removeClass('active');
        $(this).addClass('active');
        render($(this).data('range'));
    });
})();
