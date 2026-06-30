window.CdtTables = window.CdtTables || {};

window.CdtTables.escapeHtml = function (value) {
    if (value == null) {
        return '';
    }

    var element = document.createElement('div');
    element.textContent = String(value);

    return element.innerHTML;
};
