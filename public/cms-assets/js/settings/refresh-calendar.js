/*
 | KStock — Settings › Refresh Calendar.
 | Dependency-free month calendar for picking VN exchange-holiday dates to
 | exclude from data refresh. Weekends are auto-excluded (shown muted, not
 | clickable); weekday holidays are toggled and persisted as a JSON array in
 | the hidden `holidays` input, posted to cms.settings.marketCalendar.update.
 */
(function () {
    var root = document.getElementById('ks-calendar');
    if (!root) { return; }

    var input   = document.getElementById('ks-cal-input');
    var chips   = document.getElementById('ks-cal-chips');
    var countEl = document.getElementById('ks-cal-count');

    var DOW = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN']; // Monday-first
    var MONTHS = ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6',
                  'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'];

    var seed = Array.isArray(window.ksMarketHolidays) ? window.ksMarketHolidays : [];
    var holidays = {};
    seed.forEach(function (d) { if (typeof d === 'string') { holidays[d] = true; } });

    var now = new Date();
    var viewYear = now.getFullYear();
    var viewMonth = now.getMonth(); // 0-11

    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function iso(y, m, d) { return y + '-' + pad(m + 1) + '-' + pad(d); }
    function todayIso() { return iso(now.getFullYear(), now.getMonth(), now.getDate()); }

    function syncInput() {
        var list = Object.keys(holidays).sort();
        input.value = JSON.stringify(list);
        countEl.textContent = list.length;
        renderChips(list);
    }

    function renderChips(list) {
        chips.innerHTML = '';
        if (!list.length) {
            var empty = document.createElement('span');
            empty.className = 'ks-cal-empty';
            empty.textContent = 'Chưa có ngày nào được loại trừ.';
            chips.appendChild(empty);
            return;
        }
        list.forEach(function (d) {
            var chip = document.createElement('span');
            chip.className = 'ks-cal-chip';
            chip.appendChild(document.createTextNode(d));
            var x = document.createElement('button');
            x.type = 'button';
            x.innerHTML = '&times;';
            x.setAttribute('aria-label', 'Remove ' + d);
            x.addEventListener('click', function () {
                delete holidays[d];
                syncInput();
                render();
            });
            chip.appendChild(x);
            chips.appendChild(chip);
        });
    }

    function render() {
        root.innerHTML = '';

        // Header: navigation + title.
        var head = document.createElement('div');
        head.className = 'ks-cal-head';

        var navL = document.createElement('div');
        navL.className = 'ks-cal-nav';
        navL.appendChild(navBtn('«', function () { viewYear--; render(); }));
        navL.appendChild(navBtn('‹', function () { shiftMonth(-1); }));

        var title = document.createElement('div');
        title.className = 'ks-cal-title';
        title.textContent = MONTHS[viewMonth] + ' ' + viewYear;

        var navR = document.createElement('div');
        navR.className = 'ks-cal-nav';
        navR.appendChild(navBtn('›', function () { shiftMonth(1); }));
        navR.appendChild(navBtn('»', function () { viewYear++; render(); }));

        head.appendChild(navL);
        head.appendChild(title);
        head.appendChild(navR);
        root.appendChild(head);

        // Grid.
        var grid = document.createElement('div');
        grid.className = 'ks-cal-grid';

        DOW.forEach(function (label) {
            var h = document.createElement('div');
            h.className = 'ks-cal-dow';
            h.textContent = label;
            grid.appendChild(h);
        });

        var first = new Date(viewYear, viewMonth, 1);
        var offset = (first.getDay() + 6) % 7; // Monday-first leading blanks
        for (var i = 0; i < offset; i++) {
            var blank = document.createElement('div');
            blank.className = 'ks-cal-cell is-empty';
            grid.appendChild(blank);
        }

        var daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();
        var tIso = todayIso();
        for (var day = 1; day <= daysInMonth; day++) {
            var date = new Date(viewYear, viewMonth, day);
            var dow = date.getDay(); // 0 Sun .. 6 Sat
            var key = iso(viewYear, viewMonth, day);
            var cell = document.createElement('div');
            cell.className = 'ks-cal-cell';
            cell.textContent = day;

            if (key === tIso) { cell.classList.add('is-today'); }

            if (dow === 0 || dow === 6) {
                cell.classList.add('is-weekend');
                cell.title = 'Cuối tuần — tự động loại trừ';
            } else {
                if (holidays[key]) { cell.classList.add('is-holiday'); }
                (function (k) {
                    cell.addEventListener('click', function () {
                        if (holidays[k]) { delete holidays[k]; } else { holidays[k] = true; }
                        syncInput();
                        render();
                    });
                })(key);
            }
            grid.appendChild(cell);
        }
        root.appendChild(grid);
    }

    function navBtn(label, handler) {
        var b = document.createElement('button');
        b.type = 'button';
        b.textContent = label;
        b.addEventListener('click', handler);
        return b;
    }

    function shiftMonth(delta) {
        viewMonth += delta;
        if (viewMonth < 0) { viewMonth = 11; viewYear--; }
        else if (viewMonth > 11) { viewMonth = 0; viewYear++; }
        render();
    }

    syncInput();
    render();
})();
