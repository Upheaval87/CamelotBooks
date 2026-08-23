/**
 * Client-side CSV export for .tx-* suite tables.
 * Usage: <button onclick="window.txExportTable(this, 'tax-periods')">Export</button>
 * Exports the closest table to the button (skipping action columns).
 */
function txExportTable(button, filename) {
    const table = button.closest('.tx-card, .tx-wrap, body').querySelector('table.tx-table');

    if (!table) {
        return;
    }

    const rows = [];

    table.querySelectorAll('tr').forEach((tr) => {
        if (tr.closest('tfoot')) {
            return;
        }

        const cells = [...tr.querySelectorAll('th, td')].filter((cell) =>
            cell.classList.contains('tx-row-act') === false &&
            cell.querySelector('.tx-row-act') === null
        );

        if (cells.length === 0) {
            return;
        }

        rows.push(cells.map((cell) => {
            const text = (cell.getAttribute('data-export-value') ?? cell.textContent)
                .replace(/\s+/g, ' ')
                .trim();

            return `"${text.replace(/"/g, '""')}"`;
        }).join(','));
    });

    while (rows.length > 0 && rows[rows.length - 1] === '') {
        rows.pop();
    }

    const blob = new Blob(['\uFEFF' + rows.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `${filename}-${new Date().toISOString().slice(0, 10)}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(link.href);
}

window.txExportTable = txExportTable;
