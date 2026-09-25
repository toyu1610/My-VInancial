// --- MANAJEMEN AUTENTIKASI ---
function handleLogin(e) {
    e.preventDefault();
    const usernameInput = document.getElementById('username').value.trim();
    
    if (usernameInput !== '') {
        const userObj = { username: usernameInput };
        localStorage.setItem('currentUser', JSON.stringify(userObj));
        window.location.href = 'dashboard.html';
    }
}

function logout() {
    localStorage.removeItem('currentUser');
    window.location.href = 'login.html';
}

// Global Variables
let currentUser = null;
let targetsKey = '';
let transKey = '';
let targets = [];
let transactions = [];
let pieChartInstance = null;
let barChartInstance = null;

// --- INISIALISASI DASHBOARD ---
function initDashboard() {
    currentUser = JSON.parse(localStorage.getItem('currentUser'));
    if (!currentUser) {
        window.location.href = 'login.html';
        return;
    }

    document.getElementById('logged-user').innerText = currentUser.username;
    targetsKey = 'targets_' + currentUser.username;
    transKey = 'trans_' + currentUser.username;

    targets = JSON.parse(localStorage.getItem(targetsKey)) || [];
    transactions = JSON.parse(localStorage.getItem(transKey)) || [];

    const today = new Date();
    document.getElementById('trans-date').valueAsDate = today;
    document.getElementById('target-date').valueAsDate = today;
    document.getElementById('filter-month').value = today.getMonth();
    document.getElementById('filter-year').value = today.getFullYear();

    renderTargets();
    renderAll();
}

function formatRupiah(num) {
    return 'Rp ' + Number(num).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// --- TARGET MANAGEMENT ---
function addTarget(e) {
    e.preventDefault();
    const name = document.getElementById('target-name').value;
    const price = parseFloat(document.getElementById('target-price').value);
    const date = document.getElementById('target-date').value;

    targets.push({ id: Date.now(), name, price, date, completed: false });
    localStorage.setItem(targetsKey, JSON.stringify(targets));

    document.getElementById('target-form').reset();
    document.getElementById('target-date').valueAsDate = new Date();
    renderTargets();
}

function toggleTarget(id) {
    targets = targets.map(t => t.id === id ? { ...t, completed: !t.completed } : t);
    localStorage.setItem(targetsKey, JSON.stringify(targets));
    renderTargets();
}

function deleteTarget(id) {
    if (confirm('Hapus target ini?')) {
        targets = targets.filter(t => t.id !== id);
        localStorage.setItem(targetsKey, JSON.stringify(targets));
        renderTargets();
    }
}

function renderTargets() {
    const tbody = document.getElementById('target-table-body');
    if (!tbody) return;
    tbody.innerHTML = '';

    targets.forEach(t => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="text-align:center;"><input type="checkbox" ${t.completed ? 'checked' : ''} onchange="toggleTarget(${t.id})"></td>
            <td class="${t.completed ? 'text-strikethrough' : ''}"><strong>${t.name}</strong></td>
            <td>${formatRupiah(t.price)}</td>
            <td>${t.date}</td>
            <td><button class="btn-danger" onclick="deleteTarget(${t.id})">Hapus</button></td>
        `;
        tbody.appendChild(tr);
    });
}

// --- TRANSAKSI MANAGEMENT ---
function saveTransaction(e) {
    e.preventDefault();
    const id = document.getElementById('trans-id').value;
    const date = document.getElementById('trans-date').value;
    const type = document.getElementById('trans-type').value;
    const amount = parseFloat(document.getElementById('trans-amount').value);
    const category = document.getElementById('trans-category').value;
    const desc = document.getElementById('trans-desc').value;

    if (id) {
        transactions = transactions.map(t => t.id == id ? { id: Number(id), date, type, amount, category, desc } : t);
        document.getElementById('trans-id').value = '';
        document.getElementById('btn-save-trans').innerText = 'Simpan Transaksi';
        document.getElementById('form-trans-title').innerText = '➕ Tambah Transaksi Baru';
    } else {
        transactions.push({ id: Date.now(), date, type, amount, category, desc });
    }

    localStorage.setItem(transKey, JSON.stringify(transactions));
    document.getElementById('trans-form').reset();
    document.getElementById('trans-date').valueAsDate = new Date();
    renderAll();
}

function editTransaction(id) {
    const t = transactions.find(item => item.id === id);
    if (t) {
        document.getElementById('trans-id').value = t.id;
        document.getElementById('trans-date').value = t.date;
        document.getElementById('trans-type').value = t.type;
        document.getElementById('trans-amount').value = t.amount;
        document.getElementById('trans-category').value = t.category;
        document.getElementById('trans-desc').value = t.desc;

        document.getElementById('btn-save-trans').innerText = 'Update Transaksi';
        document.getElementById('form-trans-title').innerText = '✏️ Edit Transaksi';
        window.scrollTo({ top: document.getElementById('trans-form').offsetTop - 100, behavior: 'smooth' });
    }
}

function deleteTransaction(id) {
    if (confirm('Hapus transaksi ini?')) {
        transactions = transactions.filter(t => t.id !== id);
        localStorage.setItem(transKey, JSON.stringify(transactions));
        renderAll();
    }
}

function renderAll() {
    const monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
    const selectedMonth = parseInt(document.getElementById('filter-month').value);
    const selectedYear = parseInt(document.getElementById('filter-year').value);

    const monthText = monthNames[selectedMonth] + ' ' + selectedYear;
    document.getElementById('summary-title').innerText = `Ringkasan Saldo (${monthText})`;
    document.getElementById('chart-title').innerText = `Laporan Grafik (${monthText})`;

    const filteredTrans = transactions.filter(t => {
        const d = new Date(t.date);
        return d.getMonth() === selectedMonth && d.getFullYear() === selectedYear;
    });

    filteredTrans.sort((a, b) => new Date(b.date) - new Date(a.date));

    const tbody = document.getElementById('trans-table-body');
    if (!tbody) return;
    tbody.innerHTML = '';

    let totalPemasukan = 0;
    let totalPengeluaran = 0;
    const categorySums = {
        "Belanja": 0,
        "Makanan & Minuman": 0,
        "Transportasi": 0,
        "Tagihan & Utilitas": 0,
        "Gaji & Pendapatan": 0,
        "Lainnya": 0
    };

    filteredTrans.forEach(t => {
        if (t.type === 'Masuk') {
            totalPemasukan += t.amount;
        } else {
            totalPengeluaran += t.amount;
            if (categorySums[t.category] !== undefined) {
                categorySums[t.category] += t.amount;
            } else {
                categorySums["Lainnya"] += t.amount;
            }
        }

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${t.date}</td>
            <td class="${t.type === 'Masuk' ? 'text-masuk' : 'text-keluar'}">${t.type}</td>
            <td>${t.category}</td>
            <td>${formatRupiah(t.amount)}</td>
            <td>${t.desc}</td>
            <td>
                <button class="btn-edit" onclick="editTransaction(${t.id})">Edit</button>
                <button class="btn-danger" onclick="deleteTransaction(${t.id})">Hapus</button>
            </td>
        `;
        tbody.appendChild(tr);
    });

    document.getElementById('sum-pemasukan').innerText = formatRupiah(totalPemasukan);
    document.getElementById('sum-pengeluaran').innerText = formatRupiah(totalPengeluaran);
    document.getElementById('sum-total').innerText = formatRupiah(totalPemasukan - totalPengeluaran);

    renderCharts(totalPemasukan, totalPengeluaran, categorySums);
}

function renderCharts(pemasukan, pengeluaran, categories) {
    if (pieChartInstance) pieChartInstance.destroy();
    if (barChartInstance) barChartInstance.destroy();

    Chart.defaults.color = '#cccccc';

    const ctxPie = document.getElementById('pieChart').getContext('2d');
    pieChartInstance = new Chart(ctxPie, {
        type: 'pie',
        data: {
            labels: ['Pemasukan', 'Pengeluaran'],
            datasets: [{
                data: [pemasukan, pengeluaran],
                backgroundColor: ['#00ffaa', '#ff5555'],
                borderColor: '#1e1e1e',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { labels: { color: '#ffffff' } } }
        }
    });

    const ctxBar = document.getElementById('barChart').getContext('2d');
    barChartInstance = new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: Object.keys(categories),
            datasets: [{
                label: 'Pengeluaran (Rp)',
                data: Object.values(categories),
                backgroundColor: '#00ffaa',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { ticks: { color: '#cccccc' }, grid: { color: '#333333' } },
                y: { beginAtZero: true, ticks: { color: '#cccccc' }, grid: { color: '#333333' } }
            },
            plugins: { legend: { labels: { color: '#ffffff' } } }
        }
    });
}
