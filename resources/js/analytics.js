import {
    Chart,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
    Filler
} from 'chart.js';

Chart.register(
    CategoryScale, LinearScale, PointElement, LineElement,
    BarElement, ArcElement, Title, Tooltip, Legend, Filler
);

Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.font.size = 12;
Chart.defaults.color = '#6b7280';

export function createChart(canvas, config) {
    return new Chart(canvas, config);
}

export function formatCurrency(value) {
    if (value === null || value === undefined || isNaN(value)) return '0.00';
    if (typeof window !== 'undefined' && typeof window.formatMoney === 'function') {
        return window.formatMoney(value);
    }
    var symbol = (typeof window !== 'undefined' && window.currencySymbol) || '$';
    return symbol + Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

export function formatPercent(value) {
    if (value === null || value === undefined || isNaN(value)) return '0.0%';
    return (value * 100).toFixed(1) + '%';
}

export function formatNumber(value) {
    if (value === null || value === undefined || isNaN(value)) return '0';
    return new Intl.NumberFormat('en-US').format(value);
}

export function chartColors(count) {
    const palette = [
        '#128F8E', '#0C3539', '#17565D', '#7FD1C0',
        '#149897', '#46708C', '#9BC7C5',
        '#15803D', '#34d399', '#6ee7b7',
        '#D97706', '#fbbf24', '#fcd34d',
        '#DC2626', '#f87171', '#fca5a5',
        '#E2A33C', '#E07A5F', '#94a3b8',
    ];
    return palette.slice(0, count);
}
