// SIPANDA PTM - Dashboard Puskesmas (PRD §12-17)
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
const FALLBACK_INDICATOR_COLORS = ['#14b8a6', '#f97316', '#8b5cf6', '#0ea5e9', '#84cc16', '#e11d48'];
const MONTH_LABELS = { 1: 'Jan', 2: 'Feb', 3: 'Mar', 4: 'Apr', 5: 'Mei', 6: 'Jun', 7: 'Jul', 8: 'Agu', 9: 'Sep', 10: 'Okt', 11: 'Nov', 12: 'Des' };

let allRows = [];
let pkmTrendChartInstance = null;
const PKM_FILTER_STATE = { periodeType: 'bulanan', periodeValue: 'all' };

function getIndicatorColor(indikator, indicatorList) {
    if (INDIKATOR_COLOR[indikator]) return INDIKATOR_COLOR[indikator];
    const index = indicatorList.indexOf(indikator);
    return FALLBACK_INDICATOR_COLORS[index % FALLBACK_INDICATOR_COLORS.length] || '#2563eb';
}

function getStatusFromPercent(value) {
    if (value >= 100) return 'Tercapai';
    if (value >= 70) return 'Perlu Ditingkatkan';
    return 'Belum Tercapai';
}

function formatCategoryValue(rows) {
    const totalCapaian = rows.reduce((sum, row) => sum + Number(row.capaian || 0), 0);
    const totalTarget = rows.reduce((sum, row) => sum + Number(row.target_bulanan || 0), 0);
    if (totalTarget <= 0) return 0;
    return Number(((totalCapaian / totalTarget) * 100).toFixed(1));
}

function computeTargetForRows(rows) {
    const monthsPresent = new Set(rows.map((r) => r.bulan)).size || 12;
    const fraction = Math.min(monthsPresent / 12, 1);
    const seen = new Map();
    rows.forEach((row) => {
        const key = `${row.puskesmas}|${row.indikator}`;
        if (!seen.has(key)) seen.set(key, Number(row.target_tahunan || 0));
    });
    const annualSum = Array.from(seen.values()).reduce((sum, v) => sum + v, 0);
    return Math.round(annualSum * fraction);
}

function matchesPeriod(row, type, value) {
    if (value === 'all' || !value) return true;
    const month = Number(row.bulan);
    if (type === 'bulanan') return month === Number(value);
    if (type === 'triwulan') {
        if (value === 'tw-1') return month >= 1 && month <= 3;
        if (value === 'tw-2') return month >= 4 && month <= 6;
        if (value === 'tw-3') return month >= 7 && month <= 9;
        if (value === 'tw-4') return month >= 10 && month <= 12;
    }
    if (type === 'semester') {
        if (value === 'sem-1') return month >= 1 && month <= 6;
        if (value === 'sem-2') return month >= 7 && month <= 12;
    }
    if (type === 'tahunan') return String(row.tahun) === String(value).replace('th-', '');
    return true;
}

function getSelectedPuskesmas() {
    return document.getElementById('pkmSelect')?.value || '';
}

async function loadPuskesmasDashboard() {
    const res = await fetch('api/data.php');
    const data = await res.json();
    allRows = Array.isArray(data.raw_rows) ? data.raw_rows : [];

    const puskesmasList = [...new Set(allRows.map(r => r.puskesmas).filter(Boolean))].sort((a, b) => a.localeCompare(b));
    const params = new URLSearchParams(window.location.search);
    const requested = params.get('nama');
    const initial = (requested && puskesmasList.includes(requested)) ? requested : puskesmasList[0];

    const select = document.getElementById('pkmSelect');
    select.innerHTML = puskesmasList.map(p => `<option value="${p}">${p}</option>`).join('');
    select.value = initial || '';
    select.addEventListener('change', () => {
        const url = new URL(window.location.href);
        url.searchParams.set('nama', select.value);
        window.history.replaceState({}, '', url);
        renderPuskesmasDashboard();
    });

    const periodeType = document.getElementById('pkmPeriodeType');
    const periodeValue = document.getElementById('pkmPeriodeValue');
    periodeType.addEventListener('change', () => {
        PKM_FILTER_STATE.periodeType = periodeType.value;
        PKM_FILTER_STATE.periodeValue = 'all';
        buildPeriodOptions();
        renderPuskesmasDashboard();
    });
    periodeValue.addEventListener('change', () => {
        PKM_FILTER_STATE.periodeValue = periodeValue.value;
        renderPuskesmasDashboard();
    });

    buildPeriodOptions();

    if (!puskesmasList.length) {
        document.getElementById('pkmInfoCard').innerHTML = `<p>Belum ada data. Admin perlu upload file Excel terlebih dahulu.</p>`;
        return;
    }

    renderPuskesmasDashboard();
}

function buildPeriodOptions() {
    const type = document.getElementById('pkmPeriodeType').value;
    const periodeValue = document.getElementById('pkmPeriodeValue');
    const knownYears = [...new Set(allRows.map(row => String(row.tahun)))].sort().reverse();
    const fallbackYear = knownYears[0] || '2026';
    const knownMonths = [...new Set(allRows.map(row => Number(row.bulan)).filter(m => m >= 1 && m <= 12))].sort((a, b) => a - b);

    let options = [{ value: 'all', label: 'Semua Periode' }];
    if (type === 'bulanan') {
        const months = knownMonths.length ? knownMonths : [1,2,3,4,5,6,7,8,9,10,11,12];
        months.forEach(m => options.push({ value: String(m), label: `${MONTH_LABELS[m] || m} ${fallbackYear}` }));
    }
    if (type === 'triwulan') {
        ['tw-1','tw-2','tw-3','tw-4'].forEach((code, i) => options.push({ value: code, label: `Triwulan ${i + 1} (${fallbackYear})` }));
    }
    if (type === 'semester') {
        ['sem-1','sem-2'].forEach((code, i) => options.push({ value: code, label: `Semester ${i + 1} (${fallbackYear})` }));
    }
    if (type === 'tahunan') {
        const years = knownYears.length ? knownYears : ['2026','2025','2024'];
        years.forEach(y => options.push({ value: `th-${y}`, label: `Tahun ${y}` }));
    }

    periodeValue.innerHTML = options.map(o => `<option value="${o.value}">${o.label}</option>`).join('');
    PKM_FILTER_STATE.periodeValue = options.some(o => o.value === PKM_FILTER_STATE.periodeValue) ? PKM_FILTER_STATE.periodeValue : 'all';
    periodeValue.value = PKM_FILTER_STATE.periodeValue;
}

function renderPuskesmasDashboard() {
    const puskesmas = getSelectedPuskesmas();
    if (!puskesmas) return;

    const rows = allRows.filter(r => r.puskesmas === puskesmas && matchesPeriod(r, PKM_FILTER_STATE.periodeType, PKM_FILTER_STATE.periodeValue));
    const indicatorList = [...new Set(allRows.filter(r => r.puskesmas === puskesmas).map(r => r.indikator))].sort((a, b) => a.localeCompare(b));

    renderInfo(puskesmas, rows);
    renderKpiCards(puskesmas, rows, indicatorList);
    renderProgressBars(puskesmas, rows, indicatorList);
    renderTrendChart(rows, indicatorList);
    renderStatusTable(puskesmas, rows, indicatorList);
}

// §13 Info Puskesmas: kode/wilayah/jumlah penduduk tidak ada di sumber data Excel saat ini,
// jadi hanya ditampilkan kalau tersedia (PRD: "jika data tersedia").
function renderInfo(puskesmas, rows) {
    const card = document.getElementById('pkmInfoCard');
    if (!card) return;
    const totalCapaian = rows.reduce((sum, r) => sum + Number(r.capaian || 0), 0);
    const totalTarget = computeTargetForRows(rows);
    const bulanCount = new Set(rows.map(r => r.bulan)).size;

    card.innerHTML = `
        <h3>${puskesmas}</h3>
        <div class="score-sub">Data tersedia untuk ${bulanCount} bulan pada periode terpilih</div>
        <div class="score-sub">Total capaian: ${totalCapaian.toLocaleString('id-ID')} / target: ${totalTarget.toLocaleString('id-ID')} orang</div>
    `;
}

function renderKpiCards(puskesmas, rows, indicatorList) {
    const container = document.getElementById('pkmScoreboards');
    if (!container) return;

    const cards = indicatorList.map((indikator) => {
        const subset = rows.filter(r => r.indikator === indikator);
        const persen = formatCategoryValue(subset);
        const status = getStatusFromPercent(persen);
        const totalCapaian = subset.reduce((sum, r) => sum + Number(r.capaian || 0), 0);
        const totalTarget = computeTargetForRows(subset);
        return { indikator, persen, status, totalCapaian, totalTarget };
    });

    container.innerHTML = cards.map(c => `
        <div class="score-card" style="border-top-color:${STATUS_COLOR[c.status]}">
            <h4>${c.indikator}</h4>
            <div class="score-value">${Number(c.persen).toFixed(1)}%</div>
            <div class="score-sub">${c.totalCapaian} / ${c.totalTarget} Orang</div>
            <div class="score-badge" style="background:${STATUS_COLOR[c.status]}">${c.status}</div>
        </div>`).join('');
}

function renderProgressBars(puskesmas, rows, indicatorList) {
    const card = document.getElementById('pkmProgressCard');
    if (!card) return;

    card.innerHTML = indicatorList.map((indikator) => {
        const subset = rows.filter(r => r.indikator === indikator);
        const persen = formatCategoryValue(subset);
        const status = getStatusFromPercent(persen);
        const clamped = Math.max(0, Math.min(persen, 100));
        return `
            <div class="progress-kabupaten-label">
                <span>${indikator}</span>
                <strong>${Number(persen).toFixed(1)}%</strong>
            </div>
            <div class="progress-kabupaten-track">
                <div class="progress-kabupaten-fill" style="width:${clamped}%;background:${STATUS_COLOR[status]}"></div>
            </div>`;
    }).join('');
}

function renderTrendChart(rows, indicatorList) {
    const canvas = document.getElementById('pkmTrendChart');
    if (!canvas) return;

    const months = [...new Set(rows.map(r => Number(r.bulan)))].sort((a, b) => a - b);
    const datasets = indicatorList.map((indikator) => {
        const byMonth = {};
        months.forEach((m) => {
            const subset = rows.filter(r => r.indikator === indikator && Number(r.bulan) === m);
            if (subset.length) byMonth[m] = formatCategoryValue(subset);
        });
        return {
            label: indikator,
            data: months.map(m => byMonth[m] ?? null),
            borderColor: getIndicatorColor(indikator, indicatorList),
            backgroundColor: 'transparent',
            tension: 0.3,
        };
    });

    if (pkmTrendChartInstance) pkmTrendChartInstance.destroy();
    pkmTrendChartInstance = new Chart(canvas, {
        type: 'line',
        data: { labels: months.map(m => MONTH_LABELS[m] || m), datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
        },
    });
}

function renderStatusTable(puskesmas, rows, indicatorList) {
    const tbody = document.querySelector('#pkmStatusTableEl tbody');
    if (!tbody) return;

    tbody.innerHTML = indicatorList.map((indikator) => {
        const subset = rows.filter(r => r.indikator === indikator);
        const persen = formatCategoryValue(subset);
        const status = getStatusFromPercent(persen);
        const totalCapaian = subset.reduce((sum, r) => sum + Number(r.capaian || 0), 0);
        const totalTarget = computeTargetForRows(subset);
        return `
            <tr>
                <td>${indikator}</td>
                <td>${totalCapaian} / ${totalTarget}</td>
                <td>${Number(persen).toFixed(1)}%</td>
                <td><span class="badge" style="background:${STATUS_COLOR[status]}">${status}</span></td>
            </tr>`;
    }).join('');
}

loadPuskesmasDashboard();
