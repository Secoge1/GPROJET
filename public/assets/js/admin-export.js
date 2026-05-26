/**
 * GLOBALO Admin — Export Excel (CSV), PDF / Imprimer
 * Les boutons avec .admin-export-excel, .admin-export-pdf, .admin-export-print
 * et data-table-id="id-de-la-table" déclenchent l'export ou l'impression.
 */
(function() {
    'use strict';

    function escapeCsvCell(text) {
        if (text == null) return '';
        var s = String(text).replace(/\r/g, '').replace(/\n/g, ' ');
        if (/[";\t,]/.test(s)) return '"' + s.replace(/"/g, '""') + '"';
        return s;
    }

    function getTableText(el) {
        if (!el) return '';
        var span = el.querySelector('span');
        if (span) return (span.textContent || '').trim();
        return (el.textContent || '').trim();
    }

    function exportTableToCsv(tableId, exportName) {
        var table = document.getElementById(tableId);
        if (!table) return;
        var rows = [];
        var thead = table.querySelector('thead tr');
        if (thead) {
            var headerCells = thead.querySelectorAll('th');
            var headerRow = [];
            for (var i = 0; i < headerCells.length; i++) {
                headerRow.push(escapeCsvCell(getTableText(headerCells[i])));
            }
            rows.push(headerRow.join(';'));
        }
        var tbody = table.querySelector('tbody');
        if (tbody) {
            var trs = tbody.querySelectorAll('tr');
            for (var r = 0; r < trs.length; r++) {
                var tr = trs[r];
                if (tr.style.display === 'none') continue;
                var cells = tr.querySelectorAll('td');
                var row = [];
                for (var c = 0; c < cells.length; c++) {
                    row.push(escapeCsvCell(getTableText(cells[c])));
                }
                if (row.length) rows.push(row.join(';'));
            }
        }
        if (rows.length === 0) return;
        var csv = '\uFEFF' + rows.join('\r\n');
        var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        var name = (exportName || 'export') + '_' + new Date().toISOString().slice(0, 10) + '.csv';
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = name;
        a.click();
        URL.revokeObjectURL(a.href);
    }

    function init() {
        document.querySelectorAll('.admin-export-excel').forEach(function(btn) {
            btn.removeAttribute('disabled');
            btn.addEventListener('click', function() {
                var tableId = this.getAttribute('data-table-id');
                var name = this.getAttribute('data-export-name') || 'export';
                if (tableId) exportTableToCsv(tableId, name);
            });
        });
        document.querySelectorAll('.admin-export-pdf, .admin-export-print').forEach(function(btn) {
            btn.removeAttribute('disabled');
            btn.addEventListener('click', function() {
                window.print();
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
