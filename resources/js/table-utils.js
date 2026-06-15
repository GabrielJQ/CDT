window.CdtTables = window.CdtTables || {};

window.CdtTables.escapeHtml = function (value) {
    if (value == null) {
        return '';
    }

    var element = document.createElement('div');
    element.textContent = String(value);

    return element.innerHTML;
};

window.CdtTables.formatDate = function (value) {
    if (!value) {
        return '—';
    }

    var date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return window.CdtTables.escapeHtml(value);
    }

    return date.toLocaleDateString('es-MX', { year: 'numeric', month: 'short', day: '2-digit' });
};

window.CdtTables.isSortableColumn = function (column, excludedColumns) {
    return !excludedColumns.includes(column);
};

window.CdtTables.sortableHeader = function (column, label, sort, excludedColumns) {
    var escapedLabel = window.CdtTables.escapeHtml(label || column);
    var baseClass = 'text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase bg-gray-50 dark:bg-gray-800';

    if (!window.CdtTables.isSortableColumn(column, excludedColumns)) {
        return '<th class="' + baseClass + '">' + escapedLabel + '</th>';
    }

    var active = sort && sort.column === column;
    var arrow = active ? (sort.direction === 'asc' ? ' ▲' : ' ▼') : ' ↕';
    var activeClass = active ? ' text-gray-800 dark:text-gray-100' : '';

    return '<th data-sort-col="' + window.CdtTables.escapeHtml(column) + '" class="' + baseClass + activeClass + ' cursor-pointer select-none hover:text-gray-800 dark:hover:text-gray-100" title="Ordenar columna">' + escapedLabel + '<span class="ml-1 text-[10px]">' + arrow + '</span></th>';
};

window.CdtTables.bindServerSort = function (headerElement, sort) {
    if (!headerElement) {
        return;
    }

    headerElement.addEventListener('click', function (event) {
        var header = event.target.closest('th[data-sort-col]');
        if (!header) {
            return;
        }

        var column = header.dataset.sortCol;
        var direction = sort && sort.column === column && sort.direction === 'asc' ? 'desc' : 'asc';
        var url = new URL(window.location.href);
        url.searchParams.set('sort', column);
        url.searchParams.set('direction', direction);
        url.searchParams.set('page', '1');
        window.location.href = url.toString();
    });
};
