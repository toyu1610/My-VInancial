// Init State & LocalStorage
const currentUser = JSON.parse(localStorage.getItem('currentUser')) || { username: 'Muhammad Abdullah Davino' };
document.getElementById('logged-user').innerText = currentUser.username;

function logout() {
    localStorage.removeItem('currentUser');
    window.location.href = 'login.html';
}

const targetsKey = 'targets_' + currentUser.username;
const transKey = 'trans_' + currentUser.username;

let targets = JSON.parse(localStorage.getItem(targetsKey)) || [];
let transactions = JSON.parse(localStorage.getItem(transKey)) || [];

// Inisialisasi Tanggal Default
const today = new Date();
document.getElementById('trans-date').valueAsDate = today;
document.getElementById('target-date').valueAsDate = today;
document.getElementById('filter-month').value = today.getMonth();
document.getElementById('filter-year').value = today.getFullYear();

// Instance Chart.js
let pieChartInstance = null;
let barChartInstance = null;

// Format Currency Rupiah
function formatRupiah(num) {
    return 'Rp ' + Number(num).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// --- MANAJEMEN TARGET ---
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

// --- MANAJEMEN TRANSAKSI ---
function saveTransaction(e) {
    e.preventDefault();
    const id = document.getElementById('trans-id').value;
    const date = document.getElementById('trans-date').value;
    const type = document.getElementById('trans-type').value;
    const amount = parseFloat(document.getElementById('trans-amount').value);
    const category = document.getElementById('trans-category').value;
    const desc = document.getElementById('trans-desc').value;

    if (id) {
        // Edit Mode
        transactions = transactions.map(t => t.id == id ? { id: Number(id), date, type, amount, category, desc } : t);
        document.getElementById('trans-id').value = '';
        document.getElementById('btn-save-trans').innerText = 'Simpan Transaksi';
        document.getElementById('form-trans-title').innerText = '➕ Tambah Transaksi Baru';
    } else {
        // Add Mode
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

// --- RENDER ALL (TRANSAKSI, GRAFIK & RINGKASAN) ---
function renderAll() {
    const monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
    const selectedMonth = parseInt(document.getElementById('filter-month').value);
    const selectedYear = parseInt(document.getElementById('filter-year').value);

    const monthText = monthNames[selectedMonth] + ' ' + selectedYear;
    document.getElementById('summary-title').innerText = `Ringkasan Saldo (${monthText})`;
    document.getElementById('chart-title').innerText = `Laporan Grafik (${monthText})`;

    // Filter data sesuai bulan & tahun
    const filteredTrans = transactions.filter(t => {
        const d = new Date(t.date);
        return d.getMonth() === selectedMonth && d.getFullYear() === selectedYear;
    });

    filteredTrans.sort((a, b) => new Date(b.date) - new Date(a.date));

    // Populate Table Transaksi
    const tbody = document.getElementById('trans-table-body');
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

    // Update Ringkasan Saldo
    document.getElementById('sum-pemasukan').innerText = formatRupiah(totalPemasukan);
    document.getElementById('sum-pengeluaran').innerText = formatRupiah(totalPengeluaran);
    document.getElementById('sum-total').innerText = formatRupiah(totalPemasukan - totalPengeluaran);

    // Update Chart.js
    renderCharts(totalPemasukan, totalPengeluaran, categorySums);
}

function renderCharts(pemasukan, pengeluaran, categories) {
    if (pieChartInstance) pieChartInstance.destroy();
    if (barChartInstance) barChartInstance.destroy();

    // Pie Chart
    const ctxPie = document.getElementById('pieChart').getContext('2d');
    pieChartInstance = new Chart(ctxPie, {
        type: 'pie',
        data: {
            labels: ['Pemasukan', 'Pengeluaran'],
            datasets: [{
                data: [pemasukan, pengeluaran],
                backgroundColor: ['#28a745', '#dc3545']
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    // Bar Chart
    const ctxBar = document.getElementById('barChart').getContext('2d');
    barChartInstance = new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: Object.keys(categories),
            datasets: [{
                label: 'Pengeluaran (Rp)',
                data: Object.values(categories),
                backgroundColor: '#ffc107'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } }
        }
    });
}

// Executed on Load
renderTargets();
renderAll();
