// SIPANDA PTM - Dashboard publik
const STATUS_COLOR = {
    'Tercapai': '#22c55e',
    'Perlu Ditingkatkan': '#f59e0b',
    'Belum Tercapai': '#ef4444',
    '-': '#94a3b8',
};
const INDIKATOR_COLOR = {
    'Usia Produktif': '#3b82f6',
    'Hipertensi': '#ef4444',
    'Diabetes Mellitus': '#a855f7',
    'HPV DNA': '#06b6d4',
};
const FALLBACK_INDICATOR_COLORS = ['#14b8a6', '#f97316', '#8b5cf6', '#0ea5e9', '#84cc16', '#e11d48'];
const MONTH_LABELS = { 1: 'Jan', 2: 'Feb', 3: 'Mar', 4: 'Apr', 5: 'Mei', 6: 'Jun', 7: 'Jul', 8: 'Agu', 9: 'Sep', 10: 'Okt', 11: 'Nov', 12: 'Des' };

// Target dipakai untuk scoreboard & Progress Kabupaten dihitung LANGSUNG dari data
// Excel (target_tahunan per Puskesmas), bukan angka tetap. target_tahunan konstan di
// tiap baris per puskesmas+indikator+tahun (tervalidasi terhadap rekap manual: NDAO
// Usia Produktif 2026 = 2274, total kabupaten 2025/2026 cocok persis).
//
// Bug 4 fix: fraction TIDAK lagi dihitung dari kelengkapan data (berapa bulan yang
// punya baris di rows) -- itu bikin target menyusut kalau bulan belakangan belum
// diisi walau user filter "Tahun 2026" utuh (6 dari 12 bulan terisi -> fraction 0.5
// -> target keliatan setengah, padahal user minta target 1 tahun penuh). Fraction
// sekarang deterministik dari filter periode yang DIPILIH user (getPeriodFraction),
// lepas dari baris mana yang kebetulan ada datanya.
function getPeriodFraction(periodeType, periodeValue) {
    if (periodeValue === 'all' || !periodeValue) return 1;      // Semua Periode -> target 1 tahun penuh
    if (periodeType === 'tahunan') return 1;                    // tahun spesifik dipilih -> target 1 tahun penuh
    if (periodeType === 'bulanan') return 1 / 12;                // 1 bulan dipilih -> 1/12 target tahunan
    if (periodeType === 'triwulan') return 3 / 12;               // 1 triwulan dipilih -> 3/12 target tahunan
    if (periodeType === 'semester') return 6 / 12;               // 1 semester dipilih -> 6/12 target tahunan
    return 1;
}

function computeTargetForRows(rows) {
    const byYear = new Map();
    rows.forEach((row) => {
        const year = Number(row.tahun);
        if (!byYear.has(year)) byYear.set(year, new Map());
        const targetSeen = byYear.get(year);
        const targetKey = `${row.puskesmas}|${row.indikator}`;
        if (!targetSeen.has(targetKey)) {
            targetSeen.set(targetKey, Number(row.target_tahunan || 0));
        }
    });

    const fraction = getPeriodFraction(FILTER_STATE.periodeType, FILTER_STATE.periodeValue);
    let total = 0;
    byYear.forEach((targetSeen) => {
        const annualSum = Array.from(targetSeen.values()).reduce((sum, v) => sum + v, 0);
        total += Math.round(annualSum * fraction);
    });
    return total;
}

// CAPAIAN 2025 (satuan 'triwulan', sheet MASTER_SIPANDA_2025) itu KUMULATIF per
// triwulan -- Q4 sudah termasuk Q1-Q3 (contoh nyata dari data: NDAO Usia Produktif
// Q1=123, Q2=183, Q3=183, Q4=523 -- itu total s/d akhir triwulan, bukan tambahan per
// triwulan). Nge-sum keempat baris = double/triple/quadruple count orang yang sama.
// CAPAIAN 2026 (satuan 'bulanan') sebaliknya INCREMENTAL per bulan (nilai bulan Jan
// berbeda dan gak termasuk bulan sebelumnya) -- itu memang harus dijumlah.
// Fix: untuk baris 'triwulan', ambil nilai triwulan TERAKHIR (bulan terbesar) per
// puskesmas+indikator+tahun saja. Untuk baris 'bulanan', tetap dijumlah semua.
function sumCapaianForRows(rows) {
    const cumulativeGroups = new Map();
    let incrementalTotal = 0;

    rows.forEach((row) => {
        const satuan = row.satuan || 'bulanan';
        if (satuan === 'triwulan') {
            const key = `${row.tahun}|${row.puskesmas}|${row.indikator}`;
            const bulan = Number(row.bulan) || 0;
            const existing = cumulativeGroups.get(key);
            if (!existing || bulan > existing.bulan) {
                cumulativeGroups.set(key, { bulan, capaian: Number(row.capaian || 0) });
            }
        } else {
            incrementalTotal += Number(row.capaian || 0);
        }
    });

    let cumulativeTotal = 0;
    cumulativeGroups.forEach((v) => { cumulativeTotal += v.capaian; });
    return cumulativeTotal + incrementalTotal;
}

let rankMode = 'semua';

let dashboardRows = [];
let doughnutChartInstance = null;
let lineChartInstance = null;
let barChartInstance = null;
let comboChartInstance = null;
let currentFilteredRows = [];
const FILTER_STATE = {
    puskesmas: 'Semua',
    periodeType: 'bulanan',
    periodeValue: 'all',
};

function getUniquePuskesmas(rows = dashboardRows) {
    return [...new Set(rows.map(row => row.puskesmas).filter(Boolean))].sort((a, b) => a.localeCompare(b));
}

function getUniqueIndicators(rows = dashboardRows) {
    return [...new Set(rows.map(row => row.indikator).filter(Boolean))].sort((a, b) => a.localeCompare(b));
}

function getIndicatorColor(indikator) {
    if (INDIKATOR_COLOR[indikator]) return INDIKATOR_COLOR[indikator];
    const index = getUniqueIndicators().indexOf(indikator);
    return FALLBACK_INDICATOR_COLORS[index % FALLBACK_INDICATOR_COLORS.length] || '#2563eb';
}

async function loadDashboard() {
    const res = await fetch('api/data.php');
    const data = await res.json();
    dashboardRows = Array.isArray(data.raw_rows) ? data.raw_rows : [];

    setupFilterControls();
    setupRankToggle();
    syncDoughnutFilter();
    syncComboFilter();
    renderDashboard();
}

function setupFilterControls() {
    const puskesmasSelect = document.getElementById('puskesmasFilter');
    if (puskesmasSelect) {
        const uniquePuskesmas = ['Semua', ...getUniquePuskesmas()];
        puskesmasSelect.innerHTML = uniquePuskesmas.map(item => `<option value="${item}">${item === 'Semua' ? 'Semua Puskesmas' : item}</option>`).join('');
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
    const knownMonths = [...new Set(dashboardRows.map(row => Number(row.bulan)).filter(month => month >= 1 && month <= 12))].sort((a, b) => a - b);

    let options = [{ value: 'all', label: 'Semua Periode' }];

    if (type === 'bulanan') {
        const months = knownMonths.length ? knownMonths : [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
        months.forEach(month => {
            options.push({ value: String(month), label: `${MONTH_LABELS[month] || month} ${fallbackYear}` });
        });
    }
    if (type === 'triwulan') {
        ['tw-1', 'tw-2', 'tw-3', 'tw-4'].forEach((code, index) => options.push({ value: code, label: `Triwulan ${index + 1} (${fallbackYear})` }));
    }
    if (type === 'semester') {
        ['sem-1', 'sem-2'].forEach((code, index) => options.push({ value: code, label: `Semester ${index + 1} (${fallbackYear})` }));
    }
    if (type === 'tahunan') {
        const years = knownYears.length ? knownYears : ['2026', '2025', '2024'];
        years.forEach(year => options.push({ value: `th-${year}`, label: `Tahun ${year}` }));
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
    const totalCapaian = sumCapaianForRows(rows);
    const totalTarget = computeTargetForRows(rows);
    if (totalTarget <= 0) return 0;
    return Number(((totalCapaian / totalTarget) * 100).toFixed(1));
}

// Target 0 sepanjang periode berarti indikator itu memang gak ada datanya di periode
// tsb (mis. HPV DNA Co Testing IVA di tahun 2025 -- excel_reader.php ngisi placeholder
// capaian 0/target 0 biar kombinasi puskesmas+indikator+bulan gak bolong). Beda sama
// capaian 0% yang beneran dari target asli -- makanya statusnya dibedain jadi '-'
// (netral), bukan ikut kehitung 'Belum Tercapai' (merah, misleading).
function hasTargetData(rows) {
    return computeTargetForRows(rows) > 0;
}

function getStatusFromPercent(value, hasTarget = true) {
    if (!hasTarget) return '-';
    if (value >= 100) return 'Tercapai';
    if (value >= 70) return 'Perlu Ditingkatkan';
    return 'Belum Tercapai';
}

function renderDashboard() {
    currentFilteredRows = getFilteredRows();

    renderScoreboard(currentFilteredRows);
    renderProgressKabupaten(currentFilteredRows);
    renderLineChart(currentFilteredRows);
    renderComboChart(currentFilteredRows);
    renderIndikatorRankChart(currentFilteredRows);
    renderBarChart(currentFilteredRows);
    renderDoughnut(currentFilteredRows);
}

function renderProgressKabupaten(rows) {
    const fill = document.getElementById('progressKabupatenFill');
    const percentEl = document.getElementById('progressKabupatenPercent');
    const numbersEl = document.getElementById('progressKabupatenNumbers');
    const statusEl = document.getElementById('progressKabupatenStatus');
    if (!fill || !percentEl || !numbersEl || !statusEl) return;

    const totalCapaian = sumCapaianForRows(rows);
    const totalTarget = computeTargetForRows(rows);
    const persen = totalTarget > 0 ? Number(((totalCapaian / totalTarget) * 100).toFixed(1)) : 0;
    const status = getStatusFromPercent(persen, totalTarget > 0);
    const clamped = Math.max(0, Math.min(persen, 100));

    fill.style.width = `${clamped}%`;
    fill.style.background = STATUS_COLOR[status];
    percentEl.textContent = `${persen}%`;
    numbersEl.textContent = `${totalCapaian.toLocaleString('id-ID')} / ${totalTarget.toLocaleString('id-ID')} Orang`;
    statusEl.textContent = status;
    statusEl.style.color = STATUS_COLOR[status];
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
        const totalCapaian = sumCapaianForRows(indicatorRows);
        const totalTarget = computeTargetForRows(indicatorRows);
        const persen = totalTarget > 0 ? Number(((totalCapaian / totalTarget) * 100).toFixed(1)) : 0;
        const status = getStatusFromPercent(persen, totalTarget > 0);

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
            <div class="score-sub">${s.total_capaian} / ${s.total_target} Orang</div>
            <div class="score-badge" style="background:${STATUS_COLOR[s.status]}">${s.status}</div>
        </div>`).join('');
}

function renderComboChart(rows) {
    const select = document.getElementById('comboFilter');
    const indikatorKey = select?.value || 'Semua';
    const scoped = indikatorKey === 'Semua' ? rows : rows.filter(row => row.indikator === indikatorKey);

    const grouped = scoped.reduce((acc, row) => {
        acc[row.puskesmas] = acc[row.puskesmas] || [];
        acc[row.puskesmas].push(row);
        return acc;
    }, {});

    // Cap 10-12 PKM, sort by capaian desc (reviewer feedback) instead of alphabetical.
    const ranked = Object.keys(grouped).map(pkm => ({
        pkm,
        target: computeTargetForRows(grouped[pkm]),
        capaian: sumCapaianForRows(grouped[pkm]),
    })).sort((a, b) => b.capaian - a.capaian).slice(0, 12);

    const puskesmasList = ranked.map(r => r.pkm);
    const targetData = ranked.map(r => r.target);
    const capaianData = ranked.map(r => r.capaian);

    if (comboChartInstance) comboChartInstance.destroy();
    comboChartInstance = new Chart(document.getElementById('comboChart'), {
        data: {
            labels: puskesmasList,
            datasets: [
                {
                    type: 'bar',
                    label: 'Target Bulanan',
                    data: targetData,
                    backgroundColor: '#cbd5d1',
                    order: 2,
                },
                {
                    type: 'bar',
                    label: 'Capaian',
                    data: capaianData,
                    backgroundColor: '#0E7A63',
                    order: 1,
                },
            ],
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
            scales: { x: { beginAtZero: true } },
        },
    });
}

function syncComboFilter() {
    const select = document.getElementById('comboFilter');
    if (!select) return;
    const keys = ['Semua', ...getUniqueIndicators()];
    select.innerHTML = keys.map(key => `<option value="${key}">${key}</option>`).join('');
    select.addEventListener('change', () => renderComboChart(currentFilteredRows));
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
            borderColor: getIndicatorColor(indikator),
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
    const indicatorList = getUniqueIndicators(rows);
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
    const rankRows = buildBarChartRows(rows, rankMode);
    if (barChartInstance) barChartInstance.destroy();

    barChartInstance = new Chart(document.getElementById('barChart'), {
        type: 'bar',
        data: {
            labels: rankRows.map(item => item.puskesmas),
            datasets: [{
                label: 'Skor Gabungan (%)',
                data: rankRows.map(item => item.skor_gabungan),
                backgroundColor: rankRows.map(item => {
                    const status = getStatusFromPercent(item.skor_gabungan, item.has_target);
                    return STATUS_COLOR[status];
                }),
            }],
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            onHover: (event, elements) => {
                event.native.target.style.cursor = elements.length ? 'pointer' : 'default';
            },
            onClick: (event, elements) => {
                if (!elements.length) return;
                const puskesmas = rankRows[elements[0].index]?.puskesmas;
                if (puskesmas) goToPuskesmasInTable(puskesmas);
            },
        },
    });
}

// Klik nama/bar puskesmas di ranking -> masuk ke Dashboard Puskesmas (PRD §10).
function goToPuskesmasInTable(puskesmas) {
    window.location.href = `puskesmas.php?nama=${encodeURIComponent(puskesmas)}`;
}

function setupRankToggle() {
    const wrap = document.getElementById('rankToggle');
    if (!wrap) return;
    wrap.querySelectorAll('button[data-mode]').forEach((btn) => {
        btn.addEventListener('click', () => {
            rankMode = btn.dataset.mode;
            wrap.querySelectorAll('button[data-mode]').forEach(b => b.classList.toggle('is-active', b === btn));
            renderBarChart(currentFilteredRows);
        });
    });
}

// Skor Gabungan = (total capaian across ALL indicators) / (total target across ALL indicators) x 100.
// This weights each puskesmas's rank by target size (an indicator with a big target dominates the
// score), unlike the old approach which averaged each indicator's independent percentage equally.
function buildBarChartRows(rows, mode = 'semua') {
    const grouped = rows.reduce((acc, row) => {
        acc[row.puskesmas] = acc[row.puskesmas] || [];
        acc[row.puskesmas].push(row);
        return acc;
    }, {});

    const result = Object.keys(grouped).map((puskesmas) => ({
        puskesmas,
        skor_gabungan: formatCategoryValue(grouped[puskesmas]),
        has_target: hasTargetData(grouped[puskesmas]),
    })).sort((a, b) => b.skor_gabungan - a.skor_gabungan);

    if (mode === 'top5') return result.slice(0, 5);
    if (mode === 'bottom5') return result.slice(-5).sort((a, b) => b.skor_gabungan - a.skor_gabungan);
    return result;
}

let indikatorRankChartInstance = null;

// Grafik Ranking Indikator: ranking 4 indikator se-kabupaten berdasar % capaian gabungan (roadmap v2.1).
function renderIndikatorRankChart(rows) {
    const canvas = document.getElementById('indikatorRankChart');
    if (!canvas) return;

    const grouped = rows.reduce((acc, row) => {
        acc[row.indikator] = acc[row.indikator] || [];
        acc[row.indikator].push(row);
        return acc;
    }, {});

    const ranked = Object.keys(grouped).map((indikator) => {
        const indicatorRows = grouped[indikator];
        const totalCapaian = sumCapaianForRows(indicatorRows);
        const totalTarget = computeTargetForRows(indicatorRows);
        const persen = totalTarget > 0 ? Number(((totalCapaian / totalTarget) * 100).toFixed(1)) : 0;
        return { indikator, persen, has_target: totalTarget > 0 };
    }).sort((a, b) => b.persen - a.persen);

    if (indikatorRankChartInstance) indikatorRankChartInstance.destroy();
    indikatorRankChartInstance = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: ranked.map(r => r.indikator),
            datasets: [{
                label: 'Capaian (%)',
                data: ranked.map(r => r.persen),
                backgroundColor: ranked.map(r => STATUS_COLOR[getStatusFromPercent(r.persen, r.has_target)]),
            }],
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
        },
    });
}

function renderDoughnut(rows) {
    const doughnutKey = document.getElementById('doughnutFilter')?.value || 'Semua';
    const data = buildDoughnutRows(rows);
    const selected = data[doughnutKey] || { Tercapai: 0, 'Perlu Ditingkatkan': 0, 'Belum Tercapai': 0, '-': 0 };
    const labels = ['Tercapai', 'Perlu Ditingkatkan', 'Belum Tercapai', '-'];
    const values = labels.map(label => selected[label] || 0);

    if (doughnutChartInstance) doughnutChartInstance.destroy();
    doughnutChartInstance = new Chart(document.getElementById('doughnutChart'), {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{ data: values, backgroundColor: labels.map(label => STATUS_COLOR[label]) }],
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
    });
}

function buildDoughnutRows(rows) {
    const doughnut = {
        Semua: { Tercapai: 0, 'Perlu Ditingkatkan': 0, 'Belum Tercapai': 0, '-': 0 },
    };

    getUniqueIndicators(rows).forEach((indikator) => {
        doughnut[indikator] = { Tercapai: 0, 'Perlu Ditingkatkan': 0, 'Belum Tercapai': 0, '-': 0 };
    });

    // Per-indikator breakdown: unchanged, one status per puskesmas::indikator combo.
    const groupedByPuskesmasAndIndic = rows.reduce((acc, row) => {
        const key = `${row.puskesmas}::${row.indikator}`;
        acc[key] = acc[key] || [];
        acc[key].push(row);
        return acc;
    }, {});

    Object.keys(groupedByPuskesmasAndIndic).forEach((key) => {
        const [, indikator] = key.split('::');
        const group = groupedByPuskesmasAndIndic[key];
        const persen = formatCategoryValue(group);
        const status = getStatusFromPercent(persen, hasTargetData(group));
        if (doughnut[indikator]) {
            doughnut[indikator][status] += 1;
        }
    });

    // Semua: one status per Puskesmas, combining capaian/target across ALL indikator.
    const groupedByPuskesmas = rows.reduce((acc, row) => {
        acc[row.puskesmas] = acc[row.puskesmas] || [];
        acc[row.puskesmas].push(row);
        return acc;
    }, {});

    Object.keys(groupedByPuskesmas).forEach((puskesmas) => {
        const group = groupedByPuskesmas[puskesmas];
        const persen = formatCategoryValue(group);
        const status = getStatusFromPercent(persen, hasTargetData(group));
        doughnut.Semua[status] += 1;
    });

    return doughnut;
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
    const keys = ['Semua', ...getUniqueIndicators()];
    setupDoughnutFilter(keys);
}

loadDashboard();