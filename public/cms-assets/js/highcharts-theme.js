/*
 | KStock — unified Highcharts theme (applied globally via setOptions).
 | Loaded AFTER highcharts.js and BEFORE graph_report.min.js so every chart the
 | compiled report script creates inherits it. Palette validated with the data-viz
 | palette checker (light surface): lightness band, chroma floor, CVD separation,
 | and >=3:1 contrast all PASS.
 */
(function () {
    if (typeof window.Highcharts === 'undefined') { return; }

    var FONT = "Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
    var INK = '#0f172a', MUTED = '#64748b', GRID = '#eef2f7', LINE = '#e6eaf1';
    var PALETTE = ['#4f46e5', '#0d9488', '#d97706', '#e11d48', '#0284c7', '#7c3aed', '#059669', '#c026d3'];

    window.Highcharts.setOptions({
        colors: PALETTE,
        chart: {
            style: { fontFamily: FONT },
            backgroundColor: 'transparent',
            height: 340,
            spacing: [16, 12, 12, 12]
        },
        title: {
            style: { color: INK, fontWeight: '700', fontSize: '15px' },
            align: 'left', margin: 4
        },
        subtitle: {
            style: { color: MUTED, fontSize: '12px' },
            align: 'left'
        },
        credits: { enabled: false },
        legend: {
            itemStyle: { color: '#334155', fontWeight: '500', fontSize: '11px' },
            itemHoverStyle: { color: INK }
        },
        xAxis: {
            lineColor: LINE, tickColor: LINE,
            labels: { style: { color: MUTED, fontSize: '11px' } },
            title: { style: { color: MUTED, fontSize: '11px' } },
            crosshair: { color: 'rgba(99,102,241,.15)' }
        },
        yAxis: {
            gridLineColor: GRID, lineWidth: 0,
            labels: { style: { color: MUTED, fontSize: '11px' } },
            title: { style: { color: MUTED, fontSize: '11px' } }
        },
        tooltip: {
            shared: true,
            useHTML: true,
            backgroundColor: '#ffffff',
            borderColor: LINE,
            borderRadius: 10,
            shadow: { color: 'rgba(15,23,42,.12)', offsetX: 0, offsetY: 6, width: 12, opacity: 0.5 },
            style: { color: '#334155', fontSize: '12px' },
            valueDecimals: 2
        },
        plotOptions: {
            series: {
                lineWidth: 2,
                marker: { radius: 3, symbol: 'circle', lineWidth: 1, lineColor: '#fff' },
                states: { hover: { lineWidthPlus: 1 } }
            },
            spline: { marker: { enabled: false, states: { hover: { enabled: true } } } },
            column: { borderRadius: 3, borderWidth: 0, groupPadding: 0.12 },
            area: { fillOpacity: 0.12 }
        },
        responsive: {
            rules: [{
                condition: { maxWidth: 500 },
                chartOptions: { legend: { enabled: false } }
            }]
        }
    });
})();
