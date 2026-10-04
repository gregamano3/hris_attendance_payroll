import Chart from 'chart.js/auto';

const data = JSON.parse(document.getElementById('analytics-data')?.textContent || '{}');
const css = getComputedStyle(document.documentElement);
const color = (name, fallback) => css.getPropertyValue(name).trim() || fallback;
const primary = color('--bs-primary', '#0d6efd');
const success = color('--bs-success', '#198754');
const danger = color('--bs-danger', '#dc3545');
const warning = color('--bs-warning', '#ffc107');

Chart.defaults.color = color('--bs-body-color', '#212529');
Chart.defaults.borderColor = color('--bs-border-color', '#dee2e6');

const bar = (id, labels, datasets, options = {}) => {
    const el = document.getElementById(id);
    if (el) new Chart(el, { type: 'bar', data: { labels, datasets }, options: { responsive: true, maintainAspectRatio: false, ...options } });
};

if (data.by_department) {
    bar('chart-department', Object.keys(data.by_department), [{ label: 'Employees', data: Object.values(data.by_department), backgroundColor: primary }],
        { indexAxis: 'y', plugins: { legend: { display: false } } });
}

if (data.movement) {
    bar('chart-movement', data.movement.map((m) => m.month), [
        { label: 'Hires', data: data.movement.map((m) => m.hires), backgroundColor: success },
        { label: 'Separations', data: data.movement.map((m) => m.separations), backgroundColor: danger },
    ]);
}

if (data.payroll_cost) {
    bar('chart-payroll', data.payroll_cost.map((m) => m.month), [
        { label: 'Gross pay', data: data.payroll_cost.map((m) => m.gross), backgroundColor: primary, stack: 'cost' },
        { label: 'Employer contributions', data: data.payroll_cost.map((m) => m.employer), backgroundColor: warning, stack: 'cost' },
    ], { scales: { x: { stacked: true }, y: { stacked: true } } });
}
