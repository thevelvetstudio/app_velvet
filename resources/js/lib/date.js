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

const bogota = { timeZone: 'America/Bogota' };

export function formatFriendlyDate(value) {
    const date = parseDate(value);
    return date ? new Intl.DateTimeFormat('es-CO', { ...bogota, day: 'numeric', month: 'long', year: 'numeric' }).format(date) : '—';
}

export function formatFriendlyDateTime(value) {
    const date = parseDate(value);
    return date ? new Intl.DateTimeFormat('es-CO', { ...bogota, day: 'numeric', month: 'long', year: 'numeric', hour: 'numeric', minute: '2-digit' }).format(date) : '—';
}

export function formatRelativeTime(value, now = Date.now()) {
    const date = parseDate(value);
    if (!date) return 'Hace un momento';

    const seconds = Math.max(0, Math.floor((now - date.getTime()) / 1000));
    if (seconds < 10) return 'Hace un momento';
    if (seconds < 60) return `Hace ${seconds} segundos`;
    const minutes = Math.floor(seconds / 60);
    if (minutes === 1) return 'Hace 1 minuto';
    if (minutes < 60) return `Hace ${minutes} minutos`;
    const hours = Math.floor(minutes / 60);
    if (hours === 1) return 'Hace 1 hora';
    if (hours < 24) return `Hace ${hours} horas`;
    const days = Math.floor(hours / 24);
    if (days === 1) return 'Ayer';
    if (days < 7) return `Hace ${days} días`;
    return formatFriendlyDate(value);
}

export function formatCompactDate(value) {
    const date = parseDate(value);
    return date ? new Intl.DateTimeFormat('es-CO', { ...bogota, day: 'numeric', month: 'short' }).format(date) : '—';
}


