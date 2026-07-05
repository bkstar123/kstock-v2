/**
 * institution-charts.js — vẽ biểu đồ chỉ số định chế tài chính (ngân hàng/chứng khoán
 * /bảo hiểm). Dựng động từ các container [data-inst-chart] do view sinh ra; mỗi
 * container mang tên chỉ số, đơn vị, danh sách kỳ và giá trị (thứ tự thời gian tăng dần,
 * ngăn cách bởi "|"). Kỳ nào không tính được (rỗng) => điểm null (Highcharts tự ngắt).
 * Chỉ số không có giá trị số nào sẽ hiển thị thông báo thiếu dữ liệu thay vì biểu đồ.
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
        return '';
    }

    // Màu theo giá trị mới nhất so với trục 0 cho các chỉ số tăng trưởng; còn lại dùng
    // màu chủ đạo của theme (Highcharts.setOptions ở highcharts-theme.js).
    function render(el) {
        if (!window.Highcharts) { return; }
        var name = el.getAttribute('data-name') || '';
        var unit = el.getAttribute('data-unit') || '';
        var periods = (el.getAttribute('data-periods') || '').split('|');
        var values = parseValues(el.getAttribute('data-values'));

        var hasData = values.some(function (v) { return v !== null; });
        if (!hasData) {
            el.innerHTML = '<div class="ks-chart-empty text-muted"><i class="fas fa-ban"></i> ' +
                name + ': không đủ dữ liệu</div>';
            return;
        }

        var suffix = unitSuffix(unit);
        var isGrowth = /YoY|QoQ|ăng trưởng/.test(name);

        Highcharts.chart(el, {
            chart: { type: isGrowth ? 'column' : 'spline', height: 260 },
            title: { text: name, style: { fontSize: '13px', fontWeight: '600' } },
            xAxis: { categories: periods, crosshair: true },
            yAxis: { title: { text: null }, labels: { format: '{value}' + (suffix === '%' ? '%' : '') } },
            legend: { enabled: false },
            credits: { enabled: false },
            tooltip: {
                pointFormat: '<b>{point.y:,.2f}' + suffix + '</b>',
                shared: true
            },
            plotOptions: {
                spline: { marker: { enabled: true, radius: 4 }, lineWidth: 2 },
                column: {
                    borderRadius: 3,
                    negativeColor: '#e11d48',
                    color: '#0d9488'
                }
            },
            series: [{
                name: name,
                data: values,
                connectNulls: false
            }]
        });
    }

    function renderAll() {
        document.querySelectorAll('[data-inst-chart]').forEach(function (el) {
            if (el.getAttribute('data-rendered')) { return; }
            // Chỉ vẽ khi container đã hiển thị (có kích thước) để Highcharts tính đúng bề rộng.
            if (el.offsetParent === null) { return; }
            render(el);
            el.setAttribute('data-rendered', '1');
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        renderAll();
        // Vẽ (và reflow) khi chuyển tab — các tab-pane ẩn ban đầu có bề rộng 0.
        document.querySelectorAll('a[data-toggle="tab"]').forEach(function (a) {
            a.addEventListener('shown.bs.tab', function () {
                renderAll();
                if (window.Highcharts) {
                    window.Highcharts.charts.forEach(function (c) { if (c) { c.reflow(); } });
                }
            });
            // jQuery Bootstrap 4 phát sự kiện qua jQuery, bắt thêm bằng jQuery nếu có.
        });
        if (window.jQuery) {
            window.jQuery('a[data-toggle="tab"]').on('shown.bs.tab', function () {
                renderAll();
                window.Highcharts.charts.forEach(function (c) { if (c) { c.reflow(); } });
            });
        }
    });
})();
