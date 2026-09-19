const parseDate = (value) => {
    if (!value) return null;
    if (value instanceof Date) return value;
    if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        const [year, month, day] = value.split('-').map(Number);
        return new Date(year, month - 1, day);
    }

    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? null : date;
};

export function formatFriendlyDate(value) {
    const date = parseDate(value);
    return date ? new Intl.DateTimeFormat('es-CO', { day: 'numeric', month: 'long', year: 'numeric' }).format(date) : '—';
}

export function formatFriendlyDateTime(value) {
    const date = parseDate(value);
    return date ? new Intl.DateTimeFormat('es-CO', { day: 'numeric', month: 'long', year: 'numeric', hour: 'numeric', minute: '2-digit' }).format(date) : '—';
}

export function formatCompactDate(value) {
    const date = parseDate(value);
    return date ? new Intl.DateTimeFormat('es-CO', { day: 'numeric', month: 'short' }).format(date) : '—';
}
