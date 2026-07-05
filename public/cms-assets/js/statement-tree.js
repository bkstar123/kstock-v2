/**
 * statement-tree.js — thu gọn/mở rộng cho bảng cây BCTC (CĐKT/KQKD/LCTT).
 * Mỗi <tr data-id data-parent> đại diện một khoản mục; nút [data-node] bên trong dòng
 * cha bật/tắt trạng thái "children đang hiện" của chính dòng đó. Hiển thị của một dòng
 * = KHÔNG có tổ tiên nào đang ở trạng thái thu gọn (đệ quy qua data-parent).
 */
(function () {
    'use strict';

    function initTree(table) {
        var rows = Array.prototype.slice.call(table.querySelectorAll('tbody > tr[data-id]'));
        var rowById = {};
        rows.forEach(function (tr) { rowById[tr.getAttribute('data-id')] = tr; });

        // collapsed[nodeId] = true nghĩa là các con của nodeId đang bị ẩn.
        var collapsed = {};
        table.querySelectorAll('.ks-stmt-toggle').forEach(function (btn) {
            if (btn.getAttribute('data-expanded') === '0') {
                collapsed[btn.getAttribute('data-node')] = true;
            }
        });

        function isVisible(tr) {
            var parentId = tr.getAttribute('data-parent');
            while (parentId && parentId !== '-1') {
                if (collapsed[parentId]) { return false; }
                var parentRow = rowById[parentId];
                if (!parentRow) { break; }
                parentId = parentRow.getAttribute('data-parent');
            }
            return true;
        }

        function refresh() {
            rows.forEach(function (tr) {
                tr.style.display = isVisible(tr) ? '' : 'none';
            });
        }

        table.querySelectorAll('.ks-stmt-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var node = btn.getAttribute('data-node');
                var nowCollapsed = !collapsed[node];
                collapsed[node] = nowCollapsed;
                btn.setAttribute('data-expanded', nowCollapsed ? '0' : '1');
                var icon = btn.querySelector('i');
                if (icon) {
                    icon.classList.toggle('fa-chevron-down', !nowCollapsed);
                    icon.classList.toggle('fa-chevron-right', nowCollapsed);
                }
                refresh();
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-tree-table]').forEach(initTree);
    });
})();
