/*
 | KStock — analysis report enhancements
 | 1. Inline-SVG sparklines for each metric row (dependency-free, renders even
 |    inside hidden tabs since the SVG has explicit dimensions).
 | 2. Bootstrap popovers for the metric info (ℹ️) icons (full HTML description).
 */
(function () {
    var ACCENT = '#6366f1';

    function drawSparkline(el) {
        var raw = (el.getAttribute('data-values') || '')
            .split(',')
            .map(function (s) { return parseFloat(s); })
            .filter(function (n) { return !isNaN(n); });
        if (!raw.length) { return; }

        var w = 74, h = 24, pad = 3;
        var min = Math.min.apply(null, raw);
        var max = Math.max.apply(null, raw);
        var range = (max - min) || 1;
        var n = raw.length;
        var stepX = n > 1 ? (w - pad * 2) / (n - 1) : 0;

        function x(i) { return pad + i * stepX; }
        function y(v) { return h - pad - ((v - min) / range) * (h - pad * 2); }

        var pts = raw.map(function (v, i) { return x(i).toFixed(1) + ',' + y(v).toFixed(1); }).join(' ');
        var lastX = x(n - 1).toFixed(1), lastY = y(raw[n - 1]).toFixed(1);
        var up = raw[n - 1] >= raw[0];

        el.innerHTML =
            '<svg width="' + w + '" height="' + h + '" viewBox="0 0 ' + w + ' ' + h + '" preserveAspectRatio="none">'
            + '<polyline points="' + pts + '" fill="none" stroke="' + ACCENT + '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
            + '<circle cx="' + lastX + '" cy="' + lastY + '" r="2.4" fill="' + ACCENT + '"/>'
            + '</svg>';
        el.setAttribute('title', up ? 'Xu hướng tăng' : 'Xu hướng giảm');
    }

    function init() {
        var sparks = document.querySelectorAll('.ks-spark');
        for (var i = 0; i < sparks.length; i++) { drawSparkline(sparks[i]); }

        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.popover) {
            window.jQuery('[data-toggle="popover"]').popover({
                container: 'body',
                sanitize: false // descriptions are app-generated, trusted HTML
            });
        }

        // Analysis jump-nav: expand the target group + scroll to it.
        var jumps = document.querySelectorAll('.ks-jump');
        for (var j = 0; j < jumps.length; j++) {
            jumps[j].addEventListener('click', function (e) {
                e.preventDefault();
                var body = this.getAttribute('data-body');
                var group = document.querySelector(this.getAttribute('href'));
                if (body && window.jQuery) { window.jQuery(body).collapse('show'); }
                if (group) { group.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
                for (var k = 0; k < jumps.length; k++) { jumps[k].classList.remove('active'); }
                this.classList.add('active');
            });
        }

        // Reflow Highcharts when a tab becomes visible (charts drawn inside a
        // hidden pane size to 0 until their tab is shown — outer "Biểu đồ" tab
        // and the inner group sub-tabs).
        if (window.jQuery) {
            window.jQuery(document).on('shown.bs.tab', function () {
                if (window.Highcharts && window.Highcharts.charts) {
                    window.Highcharts.charts.forEach(function (c) {
                        if (c) { try { c.reflow(); } catch (err) {} }
                    });
                }
            });
        }
    }

    if (document.readyState !== 'loading') { init(); }
    else { document.addEventListener('DOMContentLoaded', init); }
})();
