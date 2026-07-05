/**
 * comparison-charts.js — biểu đồ cột so sánh 1 chỉ số giữa nhiều mã cổ phiếu.
 * Dựng từ container [data-compare-chart] do view sinh ra: data-label (tên chỉ số),
 * data-unit ('%'|'scalar'|...), data-categories (mã, ngăn "|"), data-values (giá trị theo
 * mã, ngăn "|"; rỗng = không có -> null). Cột âm tô đỏ, dương tô xanh ngọc (theme).
 */
(function () {
    'use strict';

    function parseValues(raw) {
        return (raw || '').split('|').map(function (v) {
            v = (v || '').trim();
            return v === '' || isNaN(parseFloat(v)) ? null : parseFloat(v);
        });
    }

    function unitSuffix(unit) {
        if (unit === '%') { return '%'; }
        if (unit === 'scalar') { return ' Lần'; }
        if (unit === 'cycles') { return ' Vòng'; }
        if (unit === 'days') { return ' Ngày'; }
        return '';
    }

    function render(el) {
        if (!window.Highcharts) { return; }
        var label = el.getAttribute('data-label') || '';
        var unit = el.getAttribute('data-unit') || '';
        var categories = (el.getAttribute('data-categories') || '').split('|');
        var values = parseValues(el.getAttribute('data-values'));

        if (!values.some(function (v) { return v !== null; })) {
            el.innerHTML = '<div class="ks-chart-empty text-muted"><i class="fas fa-ban"></i> ' +
                label + ': không đủ dữ liệu</div>';
            return;
        }
        var suffix = unitSuffix(unit);

        Highcharts.chart(el, {
            chart: { type: 'column', height: 260 },
            title: { text: label, style: { fontSize: '13px', fontWeight: '600' } },
            xAxis: { categories: categories, crosshair: true },
            yAxis: { title: { text: null }, labels: { format: '{value}' + (suffix === '%' ? '%' : '') } },
            legend: { enabled: false },
            credits: { enabled: false },
            tooltip: { pointFormat: '<b>{point.y:,.2f}' + suffix + '</b>' },
            plotOptions: {
                column: { borderRadius: 3, colorByPoint: true, negativeColor: '#e11d48' }
            },
            series: [{ name: label, data: values }]
        });
    }

    function renderAll() {
        document.querySelectorAll('[data-compare-chart]').forEach(function (el) {
            if (el.getAttribute('data-rendered')) { return; }
            if (el.offsetParent === null) { return; }
            render(el);
            el.setAttribute('data-rendered', '1');
        });
    }

    document.addEventListener('DOMContentLoaded', renderAll);
})();
