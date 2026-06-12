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
