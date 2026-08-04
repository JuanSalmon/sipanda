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

let fullTabelData = [];
let doughnutChartInstance = null;
let doughnutData = {};

async function loadDashboard() {
    const res = await fetch('api/data.php');
    const data = await res.json();

    renderScoreboard(data.scoreboard);
    renderLineChart(data.line_chart);
    renderBarChart(data.bar_chart);

    doughnutData = data.doughnut;
    setupDoughnutFilter(Object.keys(doughnutData));
    renderDoughnut('Semua');

    fullTabelData = data.tabel;
    renderTabel(fullTabelData);
}

function renderScoreboard(scoreboard) {
    const container = document.getElementById('scoreboards');
    container.innerHTML = scoreboard.map(s => {
        const persen = parseFloat(s.rata_persen) || 0;
        const status = persen >= 100 ? 'Tercapai' : (persen >= 70 ? 'Perlu Ditingkatkan' : 'Belum Tercapai');
        return `
            <div class="score-card" style="border-top-color:${STATUS_COLOR[status]}">
                <h4>${s.indikator}</h4>
                <div class="score-value">${persen.toFixed(1)}%</div>
                <div class="score-sub">${s.total_capaian} / ${s.total_target} pasien</div>
                <div class="score-badge" style="background:${STATUS_COLOR[status]}">${status}</div>
            </div>`;
    }).join('');
}

function renderLineChart(lineData) {
    const bulanLabel = {1:'Jan',2:'Feb',3:'Mar',4:'Apr',5:'Mei',6:'Jun'};
    const indikators = [...new Set(lineData.map(d => d.indikator))];

    const datasets = indikators.map(ind => {
        const rows = lineData.filter(d => d.indikator === ind);
        const byBulan = {};
        rows.forEach(r => byBulan[r.bulan] = parseFloat(r.rata_persen));
        return {
            label: ind,
            data: [1,2,3,4,5,6].map(b => byBulan[b] ?? null),
            borderColor: INDIKATOR_COLOR[ind] || '#888',
            backgroundColor: 'transparent',
            tension: 0.3,
        };
    });

    new Chart(document.getElementById('lineChart'), {
        type: 'line',
        data: { labels: [1,2,3,4,5,6].map(b => bulanLabel[b]), datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });
}

function renderBarChart(barData) {
    new Chart(document.getElementById('barChart'), {
        type: 'bar',
        data: {
            labels: barData.map(d => d.puskesmas),
            datasets: [{
                label: 'Skor Gabungan (%)',
                data: barData.map(d => d.skor_gabungan),
                backgroundColor: barData.map(d => {
                    const v = parseFloat(d.skor_gabungan);
                    return v >= 100 ? STATUS_COLOR['Tercapai'] : (v >= 70 ? STATUS_COLOR['Perlu Ditingkatkan'] : STATUS_COLOR['Belum Tercapai']);
                }),
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: { legend: { display: false } }
        }
    });
}

function setupDoughnutFilter(keys) {
    const select = document.getElementById('doughnutFilter');
    select.innerHTML = keys.map(k => `<option value="${k}">${k}</option>`).join('');
    select.addEventListener('change', () => renderDoughnut(select.value));
}

function renderDoughnut(key) {
    const d = doughnutData[key] || {};
    const labels = ['Tercapai', 'Perlu Ditingkatkan', 'Belum Tercapai'];
    const values = labels.map(l => d[l] || 0);

    if (doughnutChartInstance) doughnutChartInstance.destroy();
    doughnutChartInstance = new Chart(document.getElementById('doughnutChart'), {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{ data: values, backgroundColor: labels.map(l => STATUS_COLOR[l]) }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
}

function renderTabel(rows) {
    const tbody = document.querySelector('#monitorTable tbody');
    tbody.innerHTML = rows.map(r => `
        <tr>
            <td>${r.puskesmas}</td>
            <td>${r.indikator}</td>
            <td>${r.total_capaian} / ${r.total_target}</td>
            <td>${parseFloat(r.rata_persen).toFixed(1)}%</td>
            <td><span class="badge" style="background:${STATUS_COLOR[r.status]}">${r.status}</span></td>
        </tr>`).join('');
}

document.getElementById('searchInput')?.addEventListener('input', (e) => {
    const q = e.target.value.toLowerCase();
    const filtered = fullTabelData.filter(r =>
        r.puskesmas.toLowerCase().includes(q) || r.indikator.toLowerCase().includes(q)
    );
    renderTabel(filtered);
});

loadDashboard();