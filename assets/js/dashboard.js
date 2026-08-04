// SIPANDA PTM - Dashboard publik
const STATUS_COLOR = {
    'Tercapai': '#22c55e',
    'Perlu Ditingkatkan': '#f59e0b',
    'Belum Tercapai': '#ef4444',
};
const INDIKATOR_COLOR = {
    'Usia Produktif': '#3b82f6',
    'Hipertensi': '#ef4444',
    'Diabetes Mellitus': '#a855f7',
    'HPV DNA': '#06b6d4',
};
const MONTH_LABELS = {1:'Jan',2:'Feb',3:'Mar',4:'Apr',5:'Mei',6:'Jun',7:'Jul',8:'Agu',9:'Sep',10:'Okt',11:'Nov',12:'Des'};
const PUSKESMAS_LIST = ['Eahun','Sotimori','Korbafo','Sonimanu','Feapopi','Baa','Oele','Busalangga','Oelaba','Batutua','Delha','Ndao'];

let dashboardRows = [];
let doughnutChartInstance = null;
let lineChartInstance = null;
let barChartInstance = null;
let currentFilteredRows = [];
const FILTER_STATE = {
    puskesmas: 'Semua',
    periodeType: 'bulanan',
    periodeValue: 'all',
};

async function loadDashboard() {
    const res = await fetch('api/data.php');
    const data = await res.json();
    dashboardRows = Array.isArray(data.raw_rows) ? data.raw_rows : [];

    setupFilterControls();
    syncDoughnutFilter();
    renderDashboard();
}

function setupFilterControls() {
    const puskesmasSelect = document.getElementById('puskesmasFilter');
    if (puskesmasSelect) {
        const uniquePuskesmas = ['Semua', ...PUSKESMAS_LIST];
        puskesmasSelect.innerHTML = uniquePuskesmas.map(item => `<option value="${item}">${item === 'Semua' ? 'Semua Puskesmas' : `Puskesmas ${item}`}</option>`).join('');
        puskesmasSelect.value = FILTER_STATE.puskesmas;
        puskesmasSelect.addEventListener('change', () => {
            FILTER_STATE.puskesmas = puskesmasSelect.value;
            renderDashboard();
        });
    }

    const periodeType = document.getElementById('periodeType');
    const periodeValue = document.getElementById('periodeValue');
    if (periodeType && periodeValue) {
        periodeType.addEventListener('change', () => {
            FILTER_STATE.periodeType = periodeType.value;
            FILTER_STATE.periodeValue = 'all';
            buildPeriodOptions();
            renderDashboard();
        });

        buildPeriodOptions();
        periodeValue.addEventListener('change', () => {
            FILTER_STATE.periodeValue = periodeValue.value;
            renderDashboard();
        });
    }
}

function buildPeriodOptions() {
    const periodeType = document.getElementById('periodeType');
    const periodeValue = document.getElementById('periodeValue');
    if (!periodeType || !periodeValue) return;

    const type = periodeType.value;
    const knownYears = [...new Set(dashboardRows.map(row => String(row.tahun)))].sort().reverse();
    const fallbackYear = knownYears[0] || '2026';

    let options = [{ value: 'all', label: 'Semua Periode' }];

    if (type === 'bulanan') {
        [1,2,3,4,5,6,7,8,9,10,11,12].forEach(month => {
            options.push({ value: String(month), label: `${MONTH_LABELS[month]} ${fallbackYear}` });
        });
    }
    if (type === 'triwulan') {
        ['tw-1', 'tw-2', 'tw-3', 'tw-4'].forEach((code, index) => options.push({ value: code, label: `Triwulan ${index + 1} (${fallbackYear})` }));
    }
    if (type === 'semester') {
        ['sem-1', 'sem-2'].forEach((code, index) => options.push({ value: code, label: `Semester ${index + 1} (${fallbackYear})` }));
    }
    if (type === 'tahunan') {
        ['2026', '2025', '2024'].forEach(year => options.push({ value: `th-${year}`, label: `Tahun ${year}` }));
    }

    periodeValue.innerHTML = options.map(opt => `<option value="${opt.value}">${opt.label}</option>`).join('');
    FILTER_STATE.periodeValue = options.some(opt => opt.value === FILTER_STATE.periodeValue) ? FILTER_STATE.periodeValue : 'all';
    periodeValue.value = FILTER_STATE.periodeValue;
}

function getFilteredRows() {
    return dashboardRows.filter((row) => {
        const puskesmasMatch = FILTER_STATE.puskesmas === 'Semua' || row.puskesmas === FILTER_STATE.puskesmas;
        const periodeMatch = matchesPeriod(row, FILTER_STATE.periodeType, FILTER_STATE.periodeValue);
        return puskesmasMatch && periodeMatch;
    });
}

function matchesPeriod(row, type, value) {
    if (value === 'all' || !value) return true;

    if (type === 'bulanan') {
        return Number(row.bulan) === Number(value);
    }

    if (type === 'triwulan') {
        const month = Number(row.bulan);
        if (value === 'tw-1') return month >= 1 && month <= 3;
        if (value === 'tw-2') return month >= 4 && month <= 6;
        if (value === 'tw-3') return month >= 7 && month <= 9;
        if (value === 'tw-4') return month >= 10 && month <= 12;
    }

    if (type === 'semester') {
        const month = Number(row.bulan);
        if (value === 'sem-1') return month >= 1 && month <= 6;
        if (value === 'sem-2') return month >= 7 && month <= 12;
    }

    if (type === 'tahunan') {
        return String(row.tahun) === String(value).replace('th-', '');
    }

    return true;
}

function formatCategoryValue(rows) {
    const totalCapaian = rows.reduce((sum, row) => sum + Number(row.capaian || 0), 0);
    const totalTarget = rows.reduce((sum, row) => sum + Number(row.target_bulanan || 0), 0);
    if (totalTarget <= 0) return 0;
    return Number(((totalCapaian / totalTarget) * 100).toFixed(1));
}

function getStatusFromPercent(value) {
    if (value >= 100) return 'Tercapai';
    if (value >= 70) return 'Perlu Ditingkatkan';
    return 'Belum Tercapai';
}

function renderDashboard() {
    currentFilteredRows = getFilteredRows();
    const groupedForTable = buildTableRows(currentFilteredRows);

    renderScoreboard(currentFilteredRows);
    renderLineChart(currentFilteredRows);
    renderBarChart(currentFilteredRows);
    renderDoughnut(currentFilteredRows);
    renderTabel(groupedForTable);
}

function renderScoreboard(rows) {
    const container = document.getElementById('scoreboards');
    if (!container) return;

    const indicatorGroups = rows.reduce((acc, row) => {
        acc[row.indikator] = acc[row.indikator] || [];
        acc[row.indikator].push(row);
        return acc;
    }, {});

    const scoreboard = Object.keys(indicatorGroups).map((indikator) => {
        const indicatorRows = indicatorGroups[indikator];
        const persen = formatCategoryValue(indicatorRows);
        const status = getStatusFromPercent(persen);
        const totalCapaian = indicatorRows.reduce((sum, row) => sum + Number(row.capaian || 0), 0);
        const totalTarget = indicatorRows.reduce((sum, row) => sum + Number(row.target_bulanan || 0), 0);

        return {
            indikator,
            rata_persen: persen,
            total_capaian: totalCapaian,
            total_target: totalTarget,
            status,
        };
    });

    container.innerHTML = scoreboard.map((s) => `
        <div class="score-card" style="border-top-color:${STATUS_COLOR[s.status]}">
            <h4>${s.indikator}</h4>
            <div class="score-value">${Number(s.rata_persen).toFixed(1)}%</div>
            <div class="score-sub">${s.total_capaian} / ${s.total_target} pasien</div>
            <div class="score-badge" style="background:${STATUS_COLOR[s.status]}">${s.status}</div>
        </div>`).join('');
}

function renderLineChart(rows) {
    const lineData = buildLineChartRows(rows);
    const lines = [...new Set(lineData.map(item => item.indikator))];
    const months = [...new Set(rows.map(row => Number(row.bulan)))].sort((a, b) => a - b);

    const datasets = lines.map((indikator) => {
        const byMonth = {};
        lineData.filter(item => item.indikator === indikator).forEach(item => {
            byMonth[item.bulan] = Number(item.rata_persen);
        });

        return {
            label: indikator,
            data: months.map(b => byMonth[b] ?? null),
            borderColor: INDIKATOR_COLOR[indikator] || '#888',
            backgroundColor: 'transparent',
            tension: 0.3,
        };
    });

    if (lineChartInstance) lineChartInstance.destroy();
    lineChartInstance = new Chart(document.getElementById('lineChart'), {
        type: 'line',
        data: { labels: months.map(b => MONTH_LABELS[b] || b), datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
        },
    });
}

function buildLineChartRows(rows) {
    const out = [];
    const indicatorList = ['Usia Produktif', 'Hipertensi', 'Diabetes Mellitus'];
    indicatorList.forEach((indikator) => {
        const monthGroups = rows.filter(row => row.indikator === indikator).reduce((acc, row) => {
            acc[row.bulan] = acc[row.bulan] || [];
            acc[row.bulan].push(row);
            return acc;
        }, {});

        Object.keys(monthGroups).forEach((bulan) => {
            out.push({
                indikator,
                bulan: Number(bulan),
                rata_persen: formatCategoryValue(monthGroups[bulan]),
            });
        });
    });
    return out;
}

function renderBarChart(rows) {
    const rankRows = buildBarChartRows(rows);
    if (barChartInstance) barChartInstance.destroy();

    barChartInstance = new Chart(document.getElementById('barChart'), {
        type: 'bar',
        data: {
            labels: rankRows.map(item => item.puskesmas),
            datasets: [{
                label: 'Skor Gabungan (%)',
                data: rankRows.map(item => item.skor_gabungan),
                backgroundColor: rankRows.map(item => {
                    const status = getStatusFromPercent(item.skor_gabungan);
                    return STATUS_COLOR[status];
                }),
            }],
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: { legend: { display: false } },
        },
    });
}

function buildBarChartRows(rows) {
    const grouped = rows.reduce((acc, row) => {
        acc[row.puskesmas] = acc[row.puskesmas] || [];
        acc[row.puskesmas].push(row);
        return acc;
    }, {});

    const result = Object.keys(grouped).map((puskesmas) => {
        const indikatorValues = ['Usia Produktif', 'Hipertensi', 'Diabetes Mellitus', 'HPV DNA'].map((indikator) => {
            const subset = grouped[puskesmas].filter(row => row.indikator === indikator);
            return subset.length ? formatCategoryValue(subset) : 0;
        }).filter(value => value > 0);

        const skor_gabungan = indikatorValues.length
            ? Number((indikatorValues.reduce((sum, value) => sum + value, 0) / indikatorValues.length).toFixed(1))
            : 0;

        return { puskesmas, skor_gabungan };
    }).sort((a, b) => b.skor_gabungan - a.skor_gabungan);

    return result;
}

function renderDoughnut(rows) {
    const doughnutKey = document.getElementById('doughnutFilter')?.value || 'Semua';
    const data = buildDoughnutRows(rows);
    const selected = data[doughnutKey] || { Tercapai: 0, 'Perlu Ditingkatkan': 0, 'Belum Tercapai': 0 };
    const labels = ['Tercapai', 'Perlu Ditingkatkan', 'Belum Tercapai'];
    const values = labels.map(label => selected[label] || 0);

    if (doughnutChartInstance) doughnutChartInstance.destroy();
    doughnutChartInstance = new Chart(document.getElementById('doughnutChart'), {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{ data: values, backgroundColor: labels.map(label => STATUS_COLOR[label]) }],
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } },
    });
}

function buildDoughnutRows(rows) {
    const doughnut = {
        Semua: { Tercapai: 0, 'Perlu Ditingkatkan': 0, 'Belum Tercapai': 0 },
    };

    ['Usia Produktif', 'Hipertensi', 'Diabetes Mellitus', 'HPV DNA'].forEach((indikator) => {
        doughnut[indikator] = { Tercapai: 0, 'Perlu Ditingkatkan': 0, 'Belum Tercapai': 0 };
    });

    const groupedByPuskesmasAndIndic = rows.reduce((acc, row) => {
        const key = `${row.puskesmas}::${row.indikator}`;
        acc[key] = acc[key] || [];
        acc[key].push(row);
        return acc;
    }, {});

    Object.keys(groupedByPuskesmasAndIndic).forEach((key) => {
        const [puskesmas, indikator] = key.split('::');
        const persen = formatCategoryValue(groupedByPuskesmasAndIndic[key]);
        const status = getStatusFromPercent(persen);

        doughnut.Semua[status] += 1;
        doughnut[indikator][status] += 1;
    });

    return doughnut;
}

function renderTabel(rows) {
    const tbody = document.querySelector('#monitorTable tbody');
    if (!tbody) return;

    const query = document.getElementById('searchInput')?.value?.toLowerCase() || '';
    const source = query
        ? rows.filter(row => row.puskesmas.toLowerCase().includes(query) || row.indikator.toLowerCase().includes(query))
        : rows;

    tbody.innerHTML = source.map((row) => `
        <tr>
            <td>${row.puskesmas}</td>
            <td>${row.indikator}</td>
            <td>${row.total_capaian} / ${row.total_target}</td>
            <td>${Number(row.rata_persen).toFixed(1)}%</td>
            <td><span class="badge" style="background:${STATUS_COLOR[row.status]}">${row.status}</span></td>
        </tr>`).join('');
}

function buildTableRows(rows) {
    const aggByPkmIndic = rows.reduce((acc, row) => {
        const key = `${row.puskesmas}::${row.indikator}`;
        acc[key] = acc[key] || [];
        acc[key].push(row);
        return acc;
    }, {});

    const result = Object.keys(aggByPkmIndic).map((key) => {
        const [puskesmas, indikator] = key.split('::');
        const subset = aggByPkmIndic[key];
        const rataPersen = formatCategoryValue(subset);
        const status = getStatusFromPercent(rataPersen);

        return {
            puskesmas,
            indikator,
            rata_persen: rataPersen,
            total_capaian: subset.reduce((sum, row) => sum + Number(row.capaian || 0), 0),
            total_target: subset.reduce((sum, row) => sum + Number(row.target_bulanan || 0), 0),
            status,
        };
    });

    return result.sort((a, b) => a.puskesmas.localeCompare(b.puskesmas) || a.indikator.localeCompare(b.indikator));
}

function setupDoughnutFilter(keys) {
    const select = document.getElementById('doughnutFilter');
    if (!select) return;

    const options = ['Semua', ...keys.filter(key => key !== 'Semua')];
    select.innerHTML = options.map(key => `<option value="${key}">${key}</option>`).join('');
    select.addEventListener('change', () => renderDoughnut(currentFilteredRows));
}

function syncDoughnutFilter() {
    const select = document.getElementById('doughnutFilter');
    if (!select) return;
    const keys = ['Semua', 'Usia Produktif', 'Hipertensi', 'Diabetes Mellitus', 'HPV DNA'];
    setupDoughnutFilter(keys);
}

document.getElementById('searchInput')?.addEventListener('input', () => {
    renderTabel(buildTableRows(currentFilteredRows));
});

loadDashboard();