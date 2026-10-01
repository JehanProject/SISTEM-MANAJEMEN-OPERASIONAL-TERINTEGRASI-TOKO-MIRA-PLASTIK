(() => {
    const STORE_KEY = 'mira-plastik-v1';
    const today = new Date();
    const dateLabel = new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(today);
    const currency = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value) || 0);
    const dateShort = value => new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short' }).format(new Date(`${value}T00:00:00`));
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char]);
    const dateIso = (offset = 0) => { const date = new Date(); date.setDate(date.getDate() + offset); return date.toISOString().slice(0, 10); };
    const seed = {
        products: [
            { id: 'P001', name: 'Kantong kresek ukuran M', category: 'Kantong', unit: 'pak', stock: 84, min: 20, retail: 12000, wholesale: 10500, cost: 7800, color: 'green' },
            { id: 'P002', name: 'Gelas plastik 16 oz', category: 'Kemasan', unit: 'slop', stock: 12, min: 15, retail: 18500, wholesale: 16500, cost: 12400, color: 'blue' },
            { id: 'P003', name: 'Sedotan hitam', category: 'Kemasan', unit: 'pak', stock: 46, min: 12, retail: 9500, wholesale: 8200, cost: 5700, color: 'orange' },
            { id: 'P004', name: 'Thinwall 500 ml', category: 'Kemasan', unit: 'pak', stock: 8, min: 12, retail: 22000, wholesale: 19800, cost: 15000, color: 'yellow' },
            { id: 'P005', name: 'Kertas nasi cokelat', category: 'Kertas', unit: 'ikat', stock: 31, min: 10, retail: 14500, wholesale: 12800, cost: 9100, color: 'orange' },
            { id: 'P006', name: 'Plastik klip 10x15', category: 'Kantong', unit: 'pak', stock: 57, min: 18, retail: 8000, wholesale: 6800, cost: 4500, color: 'green' },
            { id: 'P007', name: 'Cup sealer roll', category: 'Perlengkapan', unit: 'roll', stock: 19, min: 8, retail: 27000, wholesale: 24500, cost: 18800, color: 'blue' },
            { id: 'P008', name: 'Mika kue ukuran 20', category: 'Kemasan', unit: 'pak', stock: 5, min: 10, retail: 32000, wholesale: 29000, cost: 22300, color: 'yellow' },
            { id: 'P009', name: 'Kantong kresek ukuran L', category: 'Kantong', unit: 'pak', stock: 39, min: 15, retail: 16000, wholesale: 14200, cost: 10800, color: 'green' }
        ],
        transactions: [
            { id: 'TRX-260930-001', date: dateIso(), customer: 'Warung Bu Sari', payment: 'Tunai', total: 186000, cost: 124000, status: 'Lunas', items: 4, cashier: 'Rina Amelia' },
            { id: 'TRX-260930-002', date: dateIso(), customer: 'Pelanggan umum', payment: 'QRIS', total: 92500, cost: 61000, status: 'Lunas', items: 3, cashier: 'Rina Amelia' },
            { id: 'TRX-260930-003', date: dateIso(), customer: 'Kedai Kopi Sudut', payment: 'Bon', total: 275000, cost: 188000, status: 'Belum lunas', items: 6, cashier: 'Dimas Putra' },
            { id: 'TRX-260929-012', date: dateIso(-1), customer: 'Toko Kue Melati', payment: 'Tunai', total: 342000, cost: 228000, status: 'Lunas', items: 8, cashier: 'Dimas Putra' },
            { id: 'TRX-260929-011', date: dateIso(-1), customer: 'Warung Pak Rudi', payment: 'QRIS', total: 148000, cost: 97000, status: 'Lunas', items: 5, cashier: 'Rina Amelia' }
        ],
        receivables: [
            { id: 'PIU-001', customer: 'Kedai Kopi Sudut', phone: '0812-8831-2240', original: 275000, paid: 75000, due: dateIso(2), note: 'TRX-260930-003' },
            { id: 'PIU-002', customer: 'Warung Bu Sari', phone: '0813-2201-1832', original: 480000, paid: 300000, due: dateIso(5), note: 'Bon pelanggan' }
        ],
        payables: [
            { id: 'UTG-001', supplier: 'CV Plastik Jaya', original: 1850000, paid: 650000, due: dateIso(4), note: 'PO-2026-018' },
            { id: 'UTG-002', supplier: 'Sumber Kemasan', original: 920000, paid: 920000, due: dateIso(-2), note: 'Lunas' }
        ],
        suppliers: [
            { name: 'CV Plastik Jaya', contact: '0812-7700-2211', lastOrder: dateIso(-3), total: 1850000, status: 'Kredit 14 hari' },
            { name: 'Sumber Kemasan', contact: '0813-9900-5502', lastOrder: dateIso(-8), total: 920000, status: 'Tunai' },
            { name: 'Mitra Packaging', contact: '0821-4410-1670', lastOrder: dateIso(-14), total: 2750000, status: 'Kredit 30 hari' }
        ],
        expenses: [
            { id: 'EXP-001', date: dateIso(), label: 'Ongkos kirim', category: 'Operasional', amount: 35000, method: 'Tunai' },
            { id: 'EXP-002', date: dateIso(-1), label: 'Listrik toko', category: 'Utilitas', amount: 175000, method: 'Transfer' },
            { id: 'EXP-003', date: dateIso(-2), label: 'Plastik packing', category: 'Operasional', amount: 68000, method: 'Tunai' }
        ],
        employees: [
            { id: 'EMP-01', name: 'Rina Amelia', role: 'Owner', initials: 'RA', status: 'Aktif' },
            { id: 'EMP-02', name: 'Dimas Putra', role: 'Kasir', initials: 'DP', status: 'Aktif' },
            { id: 'EMP-03', name: 'Siti Nurhaliza', role: 'Petugas Gudang', initials: 'SN', status: 'Aktif' }
        ],
        activity: [
            { action: 'Menyelesaikan transaksi TRX-260930-002', user: 'Rina Amelia', time: '10.42', type: 'sale' },
            { action: 'Menerima stok Gelas plastik 16 oz', user: 'Siti Nurhaliza', time: '09.18', type: 'stock' },
            { action: 'Mencatat pembayaran bon', user: 'Dimas Putra', time: 'Kemarin', type: 'finance' }
        ], archives: [], cart: [], role: 'Owner'
    };
    const databaseMode = document.body.dataset.apiMode === 'database';
    const apiBase = document.body.dataset.apiBase || '/index.php/api/v1';
    let state;
    if (databaseMode) {
        state = { ...seed, products: [], transactions: [], receivables: [], payables: [], suppliers: [], expenses: [], employees: [], activity: [], archives: [], cart: [] };
    } else {
        try { state = JSON.parse(localStorage.getItem(STORE_KEY)) || seed; } catch { state = seed; }
    }
    let csrfToken = document.querySelector('.logout-form input[name]')?.value || '';
    async function apiRequest(path, { method = 'GET', body } = {}) {
        const headers = { Accept: 'application/json' };
        const options = { method, credentials: 'same-origin', headers };
        if (body !== undefined) {
            headers['Content-Type'] = 'application/json';
            headers['X-CSRF-TOKEN'] = csrfToken;
            options.body = JSON.stringify(body);
        }
        const response = await fetch(`${apiBase}${path}`, options);
        csrfToken = response.headers.get('X-CSRF-TOKEN') || csrfToken;
        const result = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(result.error || 'Permintaan ke server gagal.');
        return result;
    }
    async function loadServerWorkspace() {
        const workspace = await apiRequest('/workspace');
        ['products', 'transactions', 'receivables', 'payables', 'suppliers', 'expenses', 'employees', 'activity', 'archives'].forEach(key => {
            state[key] = Array.isArray(workspace[key]) ? workspace[key] : [];
        });
        state.cart = [];
    }
    const save = () => {
        if (!databaseMode) localStorage.setItem(STORE_KEY, JSON.stringify(state));
        renderNotifications();
    };
    const root = document.getElementById('view-root');
    const modalRoot = document.getElementById('modal-root');
    let currentView = 'dashboard';
    let productCategory = 'Semua';
    let productSearch = '';
    let posSaleType = 'retail';
    let posPayment = 'Tunai';
    let posCustomerName = '';

    document.getElementById('today-label').textContent = dateLabel;
    const navNames = { dashboard: 'Ringkasan', pos: 'Kasir POS', inventory: 'Barang & stok', debts: 'Utang & piutang', suppliers: 'Pemasok', team: 'Tim & aktivitas', finance: 'Arus kas', reports: 'Laporan & arsip' };
    const getUser = () => ({
        name: document.body.dataset.userName || 'Pemilik toko',
        role: document.body.dataset.userRole || 'Owner',
        initials: document.body.dataset.userInitials || 'PT'
    });
    const initials = name => String(name).split(/\s+/).slice(0, 2).map(part => part[0]).join('').toUpperCase();
    const setRoleUi = () => {
        const user = getUser();
        document.getElementById('current-user-name').textContent = user.name;
        document.getElementById('current-user-role').textContent = user.role === 'Owner' ? 'Pemilik toko' : user.role;
        document.getElementById('role-name').textContent = user.name;
        document.getElementById('role-label').textContent = user.role;
        document.querySelectorAll('.avatar-green').forEach(node => node.textContent = user.initials || initials(user.name));
    };
    const statusPill = (status, variant = '') => `<span class="pill ${variant}">${escapeHtml(status)}</span>`;
    const productIcon = product => `<span class="product-thumb ${escapeHtml(product.color || 'green')}">${({ Kantong: '▤', Kemasan: '◫', Kertas: '▧', Perlengkapan: '⌁' })[product.category] || '▦'}</span>`;
    const addActivity = action => {
        state.activity.unshift({ action, user: getUser().name, time: new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit' }).format(new Date()), type: 'stock' });
        state.activity = state.activity.slice(0, 12);
    };
    const todaySales = () => state.transactions.filter(transaction => transaction.date === dateIso()).reduce((sum, transaction) => sum + transaction.total, 0);
    const todayExpenses = () => state.expenses.filter(expense => expense.date === dateIso()).reduce((sum, expense) => sum + expense.amount, 0);
    const receivableBalance = () => state.receivables.reduce((sum, debt) => sum + Math.max(0, debt.original - debt.paid), 0);
    const payableBalance = () => state.payables.reduce((sum, debt) => sum + Math.max(0, debt.original - debt.paid), 0);
    const netProfit = transactions => transactions.reduce((sum, transaction) => sum + transaction.total - (transaction.cost || 0), 0);

    function pageHeader(title, subtitle, actions = '') {
        return `<div class="page-heading"><div><h1>${title}</h1><p>${subtitle}</p></div><div class="heading-actions">${actions}</div></div>`;
    }
    function statsCards() {
        const sales = todaySales();
        const numberOfSales = state.transactions.filter(transaction => transaction.date === dateIso()).length;
        const lowStock = state.products.filter(product => product.stock <= product.min).length;
        return `<div class="stats-grid">
            <article class="stat-card"><div class="stat-top">Penjualan hari ini <span class="stat-symbol">↗</span></div><div class="stat-value">${currency(sales)}</div><div class="stat-foot"><span class="stat-trend">${numberOfSales} transaksi</span><span>tercatat hari ini</span></div></article>
            <article class="stat-card"><div class="stat-top">Transaksi <span class="stat-symbol">▤</span></div><div class="stat-value">${numberOfSales}</div><div class="stat-foot"><span class="stat-trend">Kasir aktif</span><span>shift berjalan</span></div></article>
            <article class="stat-card"><div class="stat-top">Piutang berjalan <span class="stat-symbol">◷</span></div><div class="stat-value">${currency(receivableBalance())}</div><div class="stat-foot"><span class="stat-trend down">${state.receivables.filter(debt => debt.original > debt.paid).length} pelanggan</span><span>belum lunas</span></div></article>
            <article class="stat-card"><div class="stat-top">Perlu restock <span class="stat-symbol">▦</span></div><div class="stat-value">${lowStock} <small style="font:500 11px 'DM Sans';color:#89938b">produk</small></div><div class="stat-foot"><span class="stat-trend down">Perlu perhatian</span><span>stok di bawah batas</span></div></article>
        </div>`;
    }
    function salesChart() {
        const labels = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
        const bars = labels.map((label, index) => {
            const day = new Date(); day.setDate(day.getDate() - (6 - index));
            const key = day.toISOString().slice(0, 10);
            const dayTx = state.transactions.filter(tx => tx.date === key);
            const sales = dayTx.reduce((sum, tx) => sum + tx.total, 0);
            const expense = state.expenses.filter(item => item.date === key).reduce((sum, item) => sum + item.amount, 0);
            const max = Math.max(800000, ...state.transactions.map(tx => tx.total * 2), sales, expense);
            return `<div class="chart-group"><div class="chart-bar" style="height:${Math.max(sales ? 10 : 3, sales / max * 100)}%" title="Penjualan ${currency(sales)}"></div><div class="chart-bar expense" style="height:${Math.max(expense ? 7 : 3, expense / max * 100)}%" title="Pengeluaran ${currency(expense)}"></div><span class="chart-x">${label}</span></div>`;
        }).join('');
        return `<div class="chart-wrap"><div class="chart-y-labels"><span>800 rb</span><span>600 rb</span><span>400 rb</span><span>200 rb</span><span>0</span></div><div class="chart-gridlines"><span></span><span></span><span></span><span></span><span></span></div><div class="chart-bars">${bars}</div></div>`;
    }
    function lowStockRows(limit = 5) {
        const items = state.products.filter(product => product.stock <= product.min).sort((a, b) => a.stock - b.stock).slice(0, limit);
        return items.length ? items.map(product => `<div class="stock-row">${productIcon(product)}<div class="stock-info"><strong>${escapeHtml(product.name)}</strong><small>Minimum ${product.min} ${escapeHtml(product.unit)}</small></div><div class="stock-number"><strong>${product.stock}</strong><small>${escapeHtml(product.unit)}</small></div></div>`).join('') : '<div class="empty-state">Semua stok berada di atas batas minimum.</div>';
    }
    function transactionRows(transactions) {
        return transactions.map(tx => `<tr><td><strong>${escapeHtml(tx.id)}</strong></td><td>${dateShort(tx.date)}</td><td>${escapeHtml(tx.customer)}</td><td>${escapeHtml(tx.payment)}</td><td><strong>${currency(tx.total)}</strong></td><td>${statusPill(tx.status, tx.status === 'Lunas' ? '' : 'warning')}</td></tr>`).join('');
    }
    function activityRows(limit = 4) {
        const icons = { sale: '↗', stock: '▦', finance: '◷' };
        return state.activity.slice(0, limit).map(item => `<div class="activity-row"><span class="activity-dot ${item.type === 'sale' ? 'orange' : item.type === 'finance' ? 'blue' : ''}">${icons[item.type] || '·'}</span><div class="activity-copy"><strong>${escapeHtml(item.action)}</strong><small>${escapeHtml(item.user)} · ${escapeHtml(item.time)}</small></div></div>`).join('') || '<div class="empty-state">Belum ada aktivitas.</div>';
    }
    function renderDashboard() {
        const transactions = [...state.transactions].slice(0, 5);
        root.innerHTML = `${pageHeader('Selamat pagi, ' + escapeHtml(getUser().name.split(' ')[0]) + ' 👋', `${dateLabel} · Ini ringkasan aktivitas toko Anda.`, '<button class="button" data-action="export-report"><span class="button-icon">↓</span>Ekspor laporan</button><button class="button button-primary" data-view="pos"><span class="button-icon">＋</span>Transaksi baru</button>')}
            ${statsCards()}
            <div class="dashboard-grid"><div>
                <section class="panel"><div class="panel-heading"><div><h2 class="panel-title">Ringkasan penjualan</h2><p class="panel-subtitle">Aktivitas penjualan dan pengeluaran 7 hari terakhir</p></div><div class="chart-legend"><span><i class="legend-mark"></i>Penjualan</span><span><i class="legend-mark expense"></i>Pengeluaran</span></div></div>${salesChart()}</section>
                <section class="panel table-panel"><div class="panel-heading"><div><h2 class="panel-title">Transaksi terbaru</h2><p class="panel-subtitle">Catatan penjualan toko</p></div><button class="text-link" data-view="pos">Buka kasir →</button></div><div class="table-wrap"><table><thead><tr><th>No. transaksi</th><th>Waktu</th><th>Pelanggan</th><th>Pembayaran</th><th>Total</th><th>Status</th></tr></thead><tbody>${transactionRows(transactions)}</tbody></table></div></section>
            </div><div>
                <section class="panel"><div class="panel-heading"><div><h2 class="panel-title">Stok menipis</h2><p class="panel-subtitle">${state.products.filter(product => product.stock <= product.min).length} produk perlu perhatian</p></div><button class="text-link" data-view="inventory">Lihat stok →</button></div><div class="stock-list">${lowStockRows()}</div></section>
                <section class="panel"><div class="panel-heading"><div><h2 class="panel-title">Pengingat utang & piutang</h2><p class="panel-subtitle">Jatuh tempo dalam 7 hari dan tagihan terlambat</p></div><button class="text-link" data-view="debts">Kelola →</button></div><div class="reminder-list">${reminderRows(4)}</div></section>
                <section class="panel"><div class="panel-heading"><div><h2 class="panel-title">Aktivitas terkini</h2><p class="panel-subtitle">Jejak operasional karyawan</p></div><button class="text-link" data-view="team">Semua →</button></div><div class="activity-list">${activityRows()}</div></section>
            </div></div>`;
    }
    function renderPos() {
        const customerInput = document.getElementById('customer-name');
        if (customerInput) posCustomerName = customerInput.value;
        const categories = ['Semua', ...new Set(state.products.map(product => product.category))];
        const visible = state.products.filter(product => (productCategory === 'Semua' || product.category === productCategory) && product.name.toLowerCase().includes(productSearch.toLowerCase()));
        const cart = state.cart.map(line => ({ ...line, product: state.products.find(product => product.id === line.id) })).filter(line => line.product);
        const subtotal = cart.reduce((sum, line) => sum + line.product[posSaleType] * line.qty, 0);
        root.innerHTML = `${pageHeader('Kasir POS', 'Pilih barang, atur jumlah, lalu selesaikan transaksi.', `<span class="pill blue">${escapeHtml(getUser().role)} · ${escapeHtml(getUser().name)}</span>`)}
            <div class="pos-layout"><div class="pos-catalog"><div class="pos-tools"><div class="search-box"><span class="search-symbol">⌕</span><input class="input-control" id="product-search" placeholder="Cari nama atau kode barang…" value="${escapeHtml(productSearch)}"></div><button class="button" data-view="inventory">Kelola barang</button></div>
                <div class="category-tabs">${categories.map(category => `<button class="category-tab ${category === productCategory ? 'active' : ''}" data-category="${escapeHtml(category)}">${escapeHtml(category)}</button>`).join('')}</div>
                <div class="product-grid">${visible.map(product => `<button class="pos-product" data-add-cart="${escapeHtml(product.id)}" ${product.stock <= 0 ? 'disabled' : ''}>${productIcon(product)}<div><strong>${escapeHtml(product.name)}</strong><small>${product.stock} ${escapeHtml(product.unit)} tersedia</small></div><div class="pos-product-footer"><span>${currency(product.retail)}</span><span>＋</span></div></button>`).join('') || '<div class="empty-state">Barang tidak ditemukan.</div>'}</div>
            </div><aside class="cart-panel"><div class="cart-header"><h2>Pesanan saat ini</h2><span>${cart.reduce((sum, line) => sum + line.qty, 0)} item</span></div>
                <div class="cart-customer"><label class="field-label" for="customer-name">Nama pelanggan</label><input class="input-control" id="customer-name" style="width:100%" placeholder="Pelanggan umum" value="${escapeHtml(posCustomerName)}"></div>
                <div class="sale-type"><label><input type="radio" name="sale-type" value="retail" ${posSaleType === 'retail' ? 'checked' : ''}><span>Eceran</span></label><label><input type="radio" name="sale-type" value="wholesale" ${posSaleType === 'wholesale' ? 'checked' : ''}><span>Grosir</span></label></div>
                <div class="cart-lines">${cart.map(line => `<div class="cart-line"><div><div class="cart-line-name">${escapeHtml(line.product.name)}</div><div class="cart-line-price">${currency(line.product[posSaleType])} / ${escapeHtml(line.product.unit)}</div></div><div class="quantity-control"><button data-cart-dec="${escapeHtml(line.id)}" aria-label="Kurangi">−</button><strong>${line.qty}</strong><button data-cart-inc="${escapeHtml(line.id)}" aria-label="Tambah">＋</button></div></div>`).join('') || '<div class="cart-empty">Keranjang masih kosong.<br>Pilih barang untuk memulai.</div>'}</div>
                <div class="cart-summary"><div class="summary-line"><span>Subtotal</span><span>${currency(subtotal)}</span></div><div class="summary-line total"><span>Total tagihan</span><span>${currency(subtotal)}</span></div></div>
                <span class="field-label">Metode pembayaran</span><div class="payment-grid"><label class="payment-option"><input type="radio" name="payment" value="Tunai" ${posPayment === 'Tunai' ? 'checked' : ''}><span>Tunai</span></label><label class="payment-option"><input type="radio" name="payment" value="QRIS" ${posPayment === 'QRIS' ? 'checked' : ''}><span>QRIS</span></label><label class="payment-option"><input type="radio" name="payment" value="Bon" ${posPayment === 'Bon' ? 'checked' : ''}><span>Bon</span></label></div>
                <button class="button button-primary checkout-button" data-action="checkout" ${cart.length ? '' : 'disabled'}>Selesaikan transaksi <span>→</span></button>
            </aside></div>`;
    }
    function renderInventory() {
        const query = (document.getElementById('inventory-search')?.value || '').toLowerCase();
        const products = state.products.filter(product => `${product.name} ${product.id} ${product.category}`.toLowerCase().includes(query));
        root.innerHTML = `${pageHeader('Barang & stok', 'Pantau ketersediaan dan riwayat pergerakan barang.', '<button class="button" data-action="add-product">＋ Barang baru</button><button class="button" data-action="loss-stock">− Catat barang rusak</button><button class="button button-primary" data-action="restock">＋ Barang masuk</button>')}
            ${statsCards()}<div class="filters-row"><div class="search-box"><span class="search-symbol">⌕</span><input class="input-control" id="inventory-search" placeholder="Cari barang atau kode…" value="${escapeHtml(query)}"></div><select class="select-control" id="stock-filter"><option value="all">Semua status stok</option><option value="low">Perlu restock</option><option value="safe">Stok aman</option></select><span class="pill neutral">${state.products.length} produk terdaftar</span></div>
            <section class="panel table-panel"><div class="panel-heading"><div><h2 class="panel-title">Daftar barang</h2><p class="panel-subtitle">Harga eceran dan grosir per satuan</p></div><button class="text-link" data-action="export-stock">Ekspor CSV ↓</button></div><div class="table-wrap"><table><thead><tr><th>Produk</th><th>Kategori</th><th>Stok tersedia</th><th>Min. stok</th><th>Harga eceran</th><th>Harga grosir</th><th>Status</th><th></th></tr></thead><tbody>${products.map(product => `<tr><td><div class="product-cell">${productIcon(product)}<div class="product-copy"><strong>${escapeHtml(product.name)}</strong><small>${escapeHtml(product.id)} · per ${escapeHtml(product.unit)}</small></div></div></td><td>${escapeHtml(product.category)}</td><td><strong>${product.stock} ${escapeHtml(product.unit)}</strong></td><td>${product.min}</td><td>${currency(product.retail)}</td><td>${currency(product.wholesale)}</td><td>${statusPill(product.stock <= product.min ? (product.stock === 0 ? 'Habis' : 'Menipis') : 'Aman', product.stock === 0 ? 'danger' : product.stock <= product.min ? 'warning' : '')}</td><td><button class="text-link" data-restock-product="${escapeHtml(product.id)}">＋ Stok</button></td></tr>`).join('') || '<tr><td colspan="8" class="empty-state">Tidak ada barang yang sesuai.</td></tr>'}</tbody></table></div></section>`;
        const filter = document.getElementById('stock-filter');
        filter.value = window.stockFilter || 'all';
    }
    function debtRows(list, type) {
        const isReceivable = type === 'receivable';
        return list.map(debt => {
            const remaining = Math.max(0, debt.original - debt.paid);
            return `<tr><td><strong>${escapeHtml(isReceivable ? debt.customer : debt.supplier)}</strong><br><small style="color:#929a93;font-size:8px">${escapeHtml(debt.id)} · ${escapeHtml(debt.note || '')}</small></td><td>${dateShort(debt.due)}</td><td>${currency(debt.original)}</td><td>${currency(debt.paid)}</td><td><strong>${currency(remaining)}</strong></td><td>${statusPill(remaining === 0 ? 'Lunas' : debt.due < dateIso() ? 'Terlambat' : 'Belum lunas', remaining === 0 ? '' : debt.due < dateIso() ? 'danger' : 'warning')}</td><td>${remaining ? `<button class="text-link" data-pay-debt="${escapeHtml(debt.id)}" data-debt-type="${type}">Catat bayar</button>` : '—'}</td></tr>`;
        }).join('') || '<tr><td colspan="7" class="empty-state">Belum ada catatan.</td></tr>';
    }
    function debtReminderItems() {
        const startOfToday = new Date();
        startOfToday.setHours(0, 0, 0, 0);
        return [
            ...state.receivables.map(debt => ({ ...debt, type: 'receivable', party: debt.customer })),
            ...state.payables.map(debt => ({ ...debt, type: 'payable', party: debt.supplier }))
        ].filter(debt => debt.original > debt.paid).map(debt => {
            const dueDate = new Date(`${debt.due}T00:00:00`);
            const daysRemaining = Math.round((dueDate - startOfToday) / 86400000);
            return { ...debt, daysRemaining, balance: debt.original - debt.paid };
        }).filter(debt => Number.isFinite(debt.daysRemaining) && debt.daysRemaining <= 7)
            .sort((left, right) => left.daysRemaining - right.daysRemaining);
    }
    function dueLabel(daysRemaining) {
        if (daysRemaining < 0) return `Terlambat ${Math.abs(daysRemaining)} hari`;
        if (daysRemaining === 0) return 'Jatuh tempo hari ini';
        if (daysRemaining === 1) return 'Jatuh tempo besok';
        return `Jatuh tempo ${daysRemaining} hari lagi`;
    }
    function reminderRows(limit = Infinity) {
        const reminders = debtReminderItems().slice(0, limit);
        return reminders.map(debt => `<div class="reminder-row"><span class="notification-mark ${debt.daysRemaining < 0 ? 'overdue' : ''}">${debt.daysRemaining < 0 ? '!' : '◷'}</span><div class="reminder-copy"><strong>${escapeHtml(debt.party)} · ${debt.type === 'receivable' ? 'Piutang' : 'Utang'}</strong><small>${dueLabel(debt.daysRemaining)}</small></div><span class="reminder-amount">${currency(debt.balance)}</span></div>`).join('') || '<div class="empty-state">Tidak ada tagihan yang jatuh tempo dalam 7 hari.</div>';
    }
    function renderNotifications() {
        const reminders = debtReminderItems();
        const lowStock = state.products.filter(product => product.stock <= product.min);
        const totalAlerts = reminders.length + lowStock.length;
        const count = document.getElementById('notification-count');
        const dot = document.querySelector('.notification-button i');
        const button = document.getElementById('notification-button');
        const panel = document.getElementById('notification-popover');
        if (!count || !dot || !button || !panel) return;
        count.textContent = totalAlerts > 9 ? '9+' : String(totalAlerts);
        count.hidden = totalAlerts === 0;
        dot.hidden = totalAlerts === 0;
        button.setAttribute('aria-label', `Notifikasi, ${totalAlerts} pengingat`);
        document.getElementById('debt-count').textContent = String(reminders.length);
        panel.innerHTML = `<div class="notification-head"><strong>Pengingat</strong><small>${totalAlerts} perlu perhatian</small></div>
            <div class="notification-section-label">Utang & piutang · ${reminders.length}</div>
            ${reminders.map(debt => `<button class="notification-item" data-view="debts"><span class="notification-mark ${debt.daysRemaining < 0 ? 'overdue' : ''}">${debt.daysRemaining < 0 ? '!' : '◷'}</span><span class="notification-copy"><strong>${escapeHtml(debt.party)} · ${debt.type === 'receivable' ? 'Piutang' : 'Utang'}</strong><small>${dueLabel(debt.daysRemaining)} · sisa ${currency(debt.balance)}</small></span></button>`).join('') || '<div class="empty-state">Tidak ada tagihan mendekati jatuh tempo.</div>'}
            <div class="notification-section-label">Stok menipis · ${lowStock.length}</div>
            ${lowStock.map(product => `<button class="notification-item" data-view="inventory"><span class="notification-mark stock">▦</span><span class="notification-copy"><strong>${escapeHtml(product.name)}</strong><small>Sisa ${product.stock} ${escapeHtml(product.unit)} · minimum ${product.min}</small></span></button>`).join('') || '<div class="empty-state">Semua stok aman.</div>'}`;
    }
    function renderDebts() {
        const activeReceivables = state.receivables.filter(debt => debt.original > debt.paid);
        const activePayables = state.payables.filter(debt => debt.original > debt.paid);
        root.innerHTML = `${pageHeader('Utang & piutang', 'Kelola tagihan pelanggan dan kewajiban kepada pemasok.', '<button class="button" data-action="add-payable">＋ Catat utang pemasok</button><button class="button button-primary" data-action="add-receivable">＋ Catat piutang</button>')}
            <div class="two-column" style="margin-bottom:15px"><section class="panel"><div class="debt-card-head"><span class="stat-symbol">◷</span><div><h2 class="panel-title">Piutang pelanggan</h2><div class="debt-detail">${activeReceivables.length} bon belum lunas</div></div></div><div class="debt-total">${currency(receivableBalance())}</div><div class="debt-detail">Total saldo yang masih harus diterima</div></section><section class="panel"><div class="debt-card-head"><span class="stat-symbol" style="color:#b87631;background:#fff3e5">⇄</span><div><h2 class="panel-title">Utang pemasok</h2><div class="debt-detail">${activePayables.length} tagihan terbuka</div></div></div><div class="debt-total">${currency(payableBalance())}</div><div class="debt-detail">Total saldo kewajiban pembayaran</div></section></div>
            <section class="panel table-panel" style="margin-bottom:15px"><div class="panel-heading"><div><h2 class="panel-title">Piutang pelanggan</h2><p class="panel-subtitle">Riwayat bon, cicilan, dan tanggal jatuh tempo</p></div></div><div class="table-wrap"><table><thead><tr><th>Pelanggan</th><th>Jatuh tempo</th><th>Nilai awal</th><th>Sudah dibayar</th><th>Sisa piutang</th><th>Status</th><th>Aksi</th></tr></thead><tbody>${debtRows(state.receivables, 'receivable')}</tbody></table></div></section>
            <section class="panel table-panel"><div class="panel-heading"><div><h2 class="panel-title">Utang pemasok</h2><p class="panel-subtitle">Kewajiban dan pembayaran distributor</p></div></div><div class="table-wrap"><table><thead><tr><th>Pemasok</th><th>Jatuh tempo</th><th>Nilai awal</th><th>Sudah dibayar</th><th>Sisa utang</th><th>Status</th><th>Aksi</th></tr></thead><tbody>${debtRows(state.payables, 'payable')}</tbody></table></div></section>`;
    }
    function renderSuppliers() {
        root.innerHTML = `${pageHeader('Pemasok', 'Riwayat pembelian dan pengiriman distributor.', '<button class="button button-primary" data-action="restock">＋ Catat pembelian</button>')}
            <div class="stats-grid"><article class="stat-card"><div class="stat-top">Pemasok terdaftar <span class="stat-symbol">⇄</span></div><div class="stat-value">${state.suppliers.length}</div><div class="stat-foot">Mitra pengadaan toko</div></article><article class="stat-card"><div class="stat-top">Nilai utang aktif <span class="stat-symbol">◷</span></div><div class="stat-value">${currency(payableBalance())}</div><div class="stat-foot">Kewajiban pemasok terbuka</div></article><article class="stat-card"><div class="stat-top">Barang tercatat <span class="stat-symbol">▦</span></div><div class="stat-value">${state.products.length}</div><div class="stat-foot">SKU dalam katalog toko</div></article><article class="stat-card"><div class="stat-top">Pengadaan tersimpan <span class="stat-symbol">▤</span></div><div class="stat-value">${state.suppliers.reduce((sum, supplier) => sum + (supplier.orders || 0), 0) + state.activity.filter(item => item.action.startsWith('Menerima')).length}</div><div class="stat-foot">Riwayat penerimaan barang</div></article></div>
            <section class="panel table-panel"><div class="panel-heading"><div><h2 class="panel-title">Daftar pemasok</h2><p class="panel-subtitle">Informasi kontak dan pembelian terakhir</p></div><button class="text-link" data-action="add-supplier">＋ Tambah pemasok</button></div><div class="table-wrap"><table><thead><tr><th>Nama pemasok</th><th>Kontak</th><th>Pembelian terakhir</th><th>Nilai pesanan</th><th>Skema pembayaran</th><th>Status</th></tr></thead><tbody>${state.suppliers.map(supplier => `<tr><td><strong>${escapeHtml(supplier.name)}</strong></td><td>${escapeHtml(supplier.contact)}</td><td>${dateShort(supplier.lastOrder)}</td><td><strong>${currency(supplier.total)}</strong></td><td>${escapeHtml(supplier.status)}</td><td>${statusPill('Aktif')}</td></tr>`).join('')}</tbody></table></div></section>
            <section class="panel table-panel"><div class="panel-heading"><div><h2 class="panel-title">Penerimaan barang terakhir</h2><p class="panel-subtitle">Log restock dan nilai pengadaan</p></div></div><div class="table-wrap"><table><thead><tr><th>Tanggal</th><th>Aktivitas</th><th>Petugas</th><th>Nilai</th></tr></thead><tbody>${state.activity.filter(item => item.action.startsWith('Menerima')).map(item => `<tr><td>${escapeHtml(item.time)}</td><td><strong>${escapeHtml(item.action)}</strong></td><td>${escapeHtml(item.user)}</td><td>—</td></tr>`).join('') || '<tr><td colspan="4" class="empty-state">Penerimaan barang akan tampil di sini.</td></tr>'}</tbody></table></div></section>`;
    }
    function renderTeam() {
        const actions = databaseMode ? '' : '<button class="button button-primary" data-action="add-employee">＋ Tambah karyawan</button>';
        const roleControl = employee => databaseMode
            ? `<span>${escapeHtml(employee.role)}</span>`
            : `<select class="select-control employee-role" data-employee-role="${escapeHtml(employee.id)}"><option ${employee.role === 'Owner' ? 'selected' : ''}>Owner</option><option ${employee.role === 'Kasir' ? 'selected' : ''}>Kasir</option><option ${employee.role === 'Petugas Gudang' ? 'selected' : ''}>Petugas Gudang</option></select>`;
        root.innerHTML = `${pageHeader('Tim & aktivitas', databaseMode ? 'Membership tenant dan role dari database aktif.' : 'Atur peran operasional dan telusuri jejak aktivitas.', actions)}
            <section class="panel table-panel" style="margin-bottom:15px"><div class="panel-heading"><div><h2 class="panel-title">Pengguna sistem</h2><p class="panel-subtitle">Hak akses berdasarkan peran pengguna</p></div></div><div class="table-wrap"><table><thead><tr><th>Nama karyawan</th><th>ID pengguna</th><th>Peran akses</th><th>Hak akses utama</th><th>Status</th></tr></thead><tbody>${state.employees.map(employee => `<tr><td><div class="product-cell"><span class="avatar avatar-green">${escapeHtml(employee.initials)}</span><strong>${escapeHtml(employee.name)}</strong></div></td><td>${escapeHtml(employee.id)}</td><td>${roleControl(employee)}</td><td>${employee.role === 'Owner' ? 'Semua modul & laporan' : employee.role === 'Kasir' ? 'POS & transaksi' : 'Barang & penerimaan stok'}</td><td>${statusPill(employee.status)}</td></tr>`).join('')}</tbody></table></div></section>
            <section class="panel table-panel"><div class="panel-heading"><div><h2 class="panel-title">Log aktivitas</h2><p class="panel-subtitle">Perubahan transaksi, stok, dan keuangan terbaru</p></div><button class="text-link" data-action="export-activity">Ekspor log ↓</button></div><div class="table-wrap"><table><thead><tr><th>Waktu</th><th>Aktivitas</th><th>Pengguna</th><th>Jenis</th></tr></thead><tbody>${state.activity.map(item => `<tr><td>${escapeHtml(item.time)}</td><td><strong>${escapeHtml(item.action)}</strong></td><td>${escapeHtml(item.user)}</td><td>${statusPill(item.type === 'sale' ? 'Transaksi' : item.type === 'finance' ? 'Keuangan' : 'Inventaris', item.type === 'sale' ? 'blue' : '')}</td></tr>`).join('')}</tbody></table></div></section>`;
    }
    function renderFinance() {
        const cashIn = state.transactions.filter(tx => tx.date === dateIso() && tx.payment !== 'Bon').reduce((sum, tx) => sum + tx.total, 0);
        const cashOut = state.expenses.filter(item => item.date === dateIso()).reduce((sum, item) => sum + item.amount, 0);
        root.innerHTML = `${pageHeader('Arus kas', 'Pantau pemasukan, biaya operasional, dan saldo harian.', '<button class="button" data-action="export-cashflow">Ekspor CSV ↓</button><button class="button button-primary" data-action="add-expense">＋ Catat pengeluaran</button>')}
            <div class="stats-grid"><article class="stat-card"><div class="stat-top">Kas masuk hari ini <span class="stat-symbol">↙</span></div><div class="stat-value">${currency(cashIn)}</div><div class="stat-foot">Pembayaran tunai dan QRIS</div></article><article class="stat-card"><div class="stat-top">Kas keluar hari ini <span class="stat-symbol">↗</span></div><div class="stat-value">${currency(cashOut)}</div><div class="stat-foot">Operasional dan pembelian</div></article><article class="stat-card"><div class="stat-top">Saldo bersih hari ini <span class="stat-symbol">▱</span></div><div class="stat-value">${currency(cashIn - cashOut)}</div><div class="stat-foot"><span class="stat-trend">Penerimaan dikurangi pengeluaran</span></div></article><article class="stat-card"><div class="stat-top">Utang pemasok <span class="stat-symbol">◷</span></div><div class="stat-value">${currency(payableBalance())}</div><div class="stat-foot">Saldo kewajiban berjalan</div></article></div>
            <div class="two-column"><section class="panel table-panel"><div class="panel-heading"><div><h2 class="panel-title">Pengeluaran operasional</h2><p class="panel-subtitle">Pencatatan biaya toko</p></div></div><div class="table-wrap"><table><thead><tr><th>Tanggal</th><th>Keterangan</th><th>Kategori</th><th>Metode</th><th>Jumlah</th></tr></thead><tbody>${state.expenses.map(item => `<tr><td>${dateShort(item.date)}</td><td><strong>${escapeHtml(item.label)}</strong></td><td>${escapeHtml(item.category)}</td><td>${escapeHtml(item.method)}</td><td><strong>${currency(item.amount)}</strong></td></tr>`).join('') || '<tr><td colspan="5" class="empty-state">Belum ada pengeluaran tercatat.</td></tr>'}</tbody></table></div></section>
            <section class="panel table-panel"><div class="panel-heading"><div><h2 class="panel-title">Penerimaan penjualan</h2><p class="panel-subtitle">Transaksi tunai dan pembayaran digital</p></div></div><div class="table-wrap"><table><thead><tr><th>Tanggal</th><th>No. transaksi</th><th>Metode</th><th>Jumlah</th></tr></thead><tbody>${state.transactions.filter(tx => tx.payment !== 'Bon').map(tx => `<tr><td>${dateShort(tx.date)}</td><td><strong>${escapeHtml(tx.id)}</strong></td><td>${escapeHtml(tx.payment)}</td><td><strong>${currency(tx.total)}</strong></td></tr>`).join('')}</tbody></table></div></section></div>`;
    }
    function monthlySummary(year, month) {
        const transactions = state.transactions.filter(tx => tx.date.slice(0, 7) === `${year}-${String(month).padStart(2, '0')}`);
        const expenses = state.expenses.filter(item => item.date.slice(0, 7) === `${year}-${String(month).padStart(2, '0')}` && !['Pembelian stok', 'Pembayaran utang'].includes(item.category)).reduce((sum, item) => sum + item.amount, 0);
        const sales = transactions.reduce((sum, tx) => sum + tx.total, 0);
        const cogs = transactions.reduce((sum, tx) => sum + (tx.cost || 0), 0);
        return { transactions: transactions.length, sales, expenses, cogs, profit: sales - expenses - cogs };
    }
    function renderReports() {
        const year = Number(document.getElementById('report-year')?.value || today.getFullYear());
        const summary = Array.from({ length: 12 }, (_, index) => monthlySummary(year, index + 1)).reduce((acc, month) => ({ sales: acc.sales + month.sales, expenses: acc.expenses + month.expenses, cogs: acc.cogs + month.cogs, count: acc.count + month.transactions }), { sales: 0, expenses: 0, cogs: 0, count: 0 });
        const profit = summary.sales - summary.expenses - summary.cogs;
        root.innerHTML = `${pageHeader('Laporan & arsip', 'Evaluasi performa usaha dan buka kembali laporan historis.', '<button class="button" data-action="print-report"><span class="button-icon">▧</span>Cetak</button><button class="button button-primary" data-action="export-report"><span class="button-icon">↓</span>Ekspor CSV</button>')}
            <div class="report-highlight"><div><span class="report-eyebrow">RINGKASAN TAHUNAN</span><h2>Laba bersih ${year}: ${currency(profit)}</h2><p>Penjualan dikurangi modal barang dan biaya operasional yang tercatat.</p></div><label class="form-field"><span class="report-eyebrow">PILIH TAHUN</span><select class="select-control" id="report-year" style="margin-top:5px">${[today.getFullYear(), today.getFullYear() - 1, today.getFullYear() - 2].map(option => `<option ${option === year ? 'selected' : ''}>${option}</option>`).join('')}</select></label></div>
            <div class="report-metrics"><div class="report-metric"><small>Total penjualan</small><strong>${currency(summary.sales)}</strong></div><div class="report-metric"><small>Modal barang terjual</small><strong>${currency(summary.cogs)}</strong></div><div class="report-metric"><small>Biaya operasional</small><strong>${currency(summary.expenses)}</strong></div></div>
            <div class="two-column" style="margin-top:15px"><section class="panel"><div class="panel-heading"><div><h2 class="panel-title">Arsip bulanan</h2><p class="panel-subtitle">Bekukan ringkasan dan simpan laporan periode ini</p></div><button class="button button-primary" data-action="archive-month">Arsipkan bulan</button></div><div class="form-grid"><div class="form-field"><label for="archive-month">Bulan</label><select class="select-control" id="archive-month" style="width:100%">${Array.from({ length: 12 }, (_, index) => `<option value="${String(index + 1).padStart(2, '0')}" ${index + 1 === today.getMonth() + 1 ? 'selected' : ''}>${new Intl.DateTimeFormat('id-ID', { month: 'long' }).format(new Date(2026, index, 1))}</option>`).join('')}</select></div><div class="form-field"><label for="archive-year">Tahun</label><input class="input-control" id="archive-year" type="number" value="${today.getFullYear()}" style="width:100%"></div></div><p class="panel-subtitle" style="line-height:1.6;margin-top:12px">Arsip menyimpan snapshot penjualan, biaya, modal barang, dan posisi stok. Aktivitas transaksi baru tetap berjalan terpisah.</p></section>
                <section class="panel"><div class="panel-heading"><div><h2 class="panel-title">Penyesuaian kerugian</h2><p class="panel-subtitle">Catat barang rusak atau kehilangan untuk mengurangi stok.</p></div></div><div class="report-metrics" style="margin-top:0"><div class="report-metric"><small>Piutang belum lunas</small><strong>${currency(receivableBalance())}</strong></div><div class="report-metric"><small>Utang belum lunas</small><strong>${currency(payableBalance())}</strong></div><div class="report-metric"><small>Barang stok menipis</small><strong>${state.products.filter(product => product.stock <= product.min).length} produk</strong></div></div><button class="button" style="margin-top:13px" data-view="inventory">Kelola stok & kerugian →</button></section></div>
            <section class="panel" style="margin-top:15px"><div class="panel-heading"><div><h2 class="panel-title">Laporan tersimpan</h2><p class="panel-subtitle">Arsip periode yang ditutup</p></div><div class="search-box" style="max-width:210px"><span class="search-symbol">⌕</span><input class="input-control" id="archive-search" placeholder="Cari periode…"></div></div><div id="archive-list">${archiveRows()}</div></section>`;
    }
    function archiveRows(filter = '') {
        const items = [...state.archives].sort((a, b) => b.period.localeCompare(a.period)).filter(item => item.period.includes(filter));
        return items.map(item => `<div class="archive-row"><span class="archive-date">▤</span><div class="archive-copy"><strong>Laporan ${escapeHtml(item.label)}</strong><small>Diarsipkan ${dateShort(item.archivedAt)} · ${item.transactions} transaksi</small></div><strong>${currency(item.profit)}</strong><button class="text-link" data-export-archive="${escapeHtml(item.period)}">Ekspor ↓</button></div>`).join('') || '<div class="empty-state">Belum ada arsip. Pilih periode, lalu arsipkan untuk menyimpan snapshot.</div>';
    }
    const renderers = { dashboard: renderDashboard, pos: renderPos, inventory: renderInventory, debts: renderDebts, suppliers: renderSuppliers, team: renderTeam, finance: renderFinance, reports: renderReports };
    function navigate(view) {
        if (!renderers[view]) return;
        currentView = view;
        document.querySelectorAll('.nav-link').forEach(link => link.classList.toggle('active', link.dataset.view === view));
        document.getElementById('page-crumb').textContent = navNames[view];
        document.getElementById('notification-popover').hidden = true;
        document.getElementById('notification-button').setAttribute('aria-expanded', 'false');
        document.getElementById('sidebar').classList.remove('open');
        renderers[view]();
        window.location.hash = view;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    function showToast(message) {
        const toast = document.getElementById('toast'); toast.textContent = message; toast.classList.add('visible');
        clearTimeout(showToast.timer); showToast.timer = setTimeout(() => toast.classList.remove('visible'), 2600);
    }
    function showModal(title, subtitle, fields, onSubmit, submitText = 'Simpan catatan') {
        modalRoot.innerHTML = `<div class="modal-backdrop"><section class="modal" role="dialog" aria-modal="true"><div class="modal-head"><div><h2>${title}</h2><p>${subtitle}</p></div><button class="modal-close" data-close-modal aria-label="Tutup">×</button></div><form id="modal-form"><div class="form-grid">${fields}</div><div class="modal-actions"><button type="button" class="button" data-close-modal>Batal</button><button class="button button-primary" type="submit">${submitText}</button></div></form></section></div>`;
        modalRoot.querySelector('[data-close-modal]').focus();
        modalRoot.querySelector('[data-close-modal]').addEventListener('click', closeModal);
        modalRoot.querySelector('.modal-backdrop').addEventListener('click', event => { if (event.target.classList.contains('modal-backdrop')) closeModal(); });
        modalRoot.querySelector('#modal-form').addEventListener('submit', async event => {
            event.preventDefault();
            const form = event.currentTarget;
            const submitButton = form.querySelector('[type="submit"]');
            const data = Object.fromEntries(new FormData(form));
            submitButton.disabled = true;
            try {
                await onSubmit(data);
                closeModal();
                save();
                renderers[currentView]();
            } catch (error) {
                showToast(error.message || 'Data gagal disimpan.');
            } finally {
                if (submitButton.isConnected) submitButton.disabled = false;
            }
        });
    }
    function closeModal() { modalRoot.innerHTML = ''; }
    function field(label, name, type = 'text', options = {}) {
        const required = options.required === false ? '' : 'required';
        if (type === 'select') return `<div class="form-field ${options.full ? 'full' : ''}"><label>${label}</label><select name="${name}" ${required}>${options.options.map(option => `<option value="${escapeHtml(option)}">${escapeHtml(option)}</option>`).join('')}</select></div>`;
        const numericAttributes = type === 'number' ? `min="${options.min ?? 1}" step="${options.step ?? 1}"` : '';
        return `<div class="form-field ${options.full ? 'full' : ''}"><label>${label}</label><input name="${name}" type="${type}" ${numericAttributes} ${options.value ? `value="${escapeHtml(options.value)}"` : ''} ${required} ${options.placeholder ? `placeholder="${escapeHtml(options.placeholder)}"` : ''}></div>`;
    }
    function addProductModal() {
        showModal('Tambah barang', 'Tambahkan produk ke katalog toko dan catat stok awal.', `${field('SKU', 'sku')}${field('Nama barang', 'name')}${field('Kategori', 'category')}${field('Satuan', 'unit')}${field('Stok minimum', 'minimum_stock', 'number', { min: 0, step: '0.001', value: 0 })}${field('Stok awal', 'opening_stock', 'number', { min: 0, step: '0.001', value: 0 })}${field('Harga modal (Rp)', 'cost_rupiah', 'number', { min: 0, value: 0 })}${field('Harga eceran (Rp)', 'retail_rupiah', 'number', { min: 0, value: 0 })}${field('Harga grosir (Rp)', 'wholesale_rupiah', 'number', { min: 0, value: 0 })}`, async data => {
            if (databaseMode) {
                await apiRequest('/products', { method: 'POST', body: {
                    sku: data.sku,
                    name: data.name,
                    category: data.category,
                    unit: data.unit,
                    minimum_stock: Number(data.minimum_stock),
                    opening_stock: Number(data.opening_stock),
                    cost_rupiah: Number(data.cost_rupiah),
                    retail_rupiah: Number(data.retail_rupiah),
                    wholesale_rupiah: Number(data.wholesale_rupiah)
                } });
                await loadServerWorkspace();
                showToast('Barang tersimpan di database toko.');
                return;
            }

            state.products.unshift({ id: `P-${Date.now()}`, ...data, min: Number(data.minimum_stock), stock: Number(data.opening_stock), cost: Number(data.cost_rupiah), retail: Number(data.retail_rupiah), wholesale: Number(data.wholesale_rupiah), color: 'green' });
            addActivity(`Menambahkan barang ${data.name}`);
            showToast('Barang ditambahkan.');
        }, 'Simpan barang');
    }
    function restockModal(productId = '') {
        const productOptions = state.products.map(product => `<option value="${escapeHtml(product.id)}" ${product.id === productId ? 'selected' : ''}>${escapeHtml(product.name)} · stok ${product.stock}</option>`).join('');
        const supplierOptions = state.suppliers.map(supplier => supplier.name).join('|');
        const supplierHtml = `<div class="form-field"><label>Pemasok</label><select name="supplier" required>${supplierOptions.split('|').map(name => `<option>${escapeHtml(name)}</option>`).join('')}<option>Pemasok baru</option></select></div>`;
        showModal('Catat barang masuk', 'Jumlah stok akan ditambahkan ke inventaris dan log aktivitas.', `<div class="form-field full"><label>Produk</label><select name="product" required>${productOptions}</select></div>${field('Jumlah masuk', 'quantity', 'number')}${field('Harga modal per unit (Rp)', 'cost', 'number')}${supplierHtml}${field('Pembayaran', 'payment', 'select', { options: ['Tunai', 'Utang pemasok'] })}`, async data => {
            if (databaseMode) {
                await apiRequest('/stock-movements', { method: 'POST', body: {
                    product_id: Number(data.product),
                    quantity_delta: Number(data.quantity),
                    movement_type: 'purchase_receipt',
                    unit_cost_rupiah: Number(data.cost),
                    supplier_name: data.supplier,
                    payment_method: data.payment
                } });
                await loadServerWorkspace();
                showToast('Penerimaan pembelian tersimpan di server.');
                return;
            }
            const product = state.products.find(item => item.id === data.product); if (!product) return;
            const quantity = Number(data.quantity); const cost = Number(data.cost);
            product.stock += quantity; product.cost = cost;
            const supplier = state.suppliers.find(item => item.name === data.supplier);
            if (supplier) { supplier.lastOrder = dateIso(); supplier.total = quantity * cost; }
            if (data.payment === 'Utang pemasok') state.payables.unshift({ id: `UTG-${String(Date.now()).slice(-5)}`, supplier: data.supplier, original: quantity * cost, paid: 0, due: dateIso(14), note: `Restock ${product.name}` });
            else state.expenses.unshift({ id: `EXP-${String(Date.now()).slice(-5)}`, date: dateIso(), label: `Pembelian ${product.name}`, category: 'Pembelian stok', amount: quantity * cost, method: 'Tunai' });
            addActivity(`Menerima stok ${product.name} (${quantity} ${product.unit})`); showToast('Stok berhasil diperbarui.');
        });
    }
    function markLossModal() {
        const options = state.products.map(product => `<option value="${escapeHtml(product.id)}">${escapeHtml(product.name)} · tersedia ${product.stock} ${escapeHtml(product.unit)}</option>`).join('');
        showModal('Catat barang rusak / hilang', 'Stok akan disesuaikan dan nilai kerugian tercatat.', `<div class="form-field full"><label>Produk</label><select name="product" required>${options}</select></div>${field('Jumlah barang', 'quantity', 'number')}${field('Keterangan', 'note', 'text', { placeholder: 'Contoh: kemasan rusak' })}`, async data => {
            if (databaseMode) {
                await apiRequest('/stock-movements', { method: 'POST', body: {
                    product_id: Number(data.product),
                    quantity_delta: -Number(data.quantity),
                    movement_type: 'damage',
                    note: data.note
                } });
                await loadServerWorkspace();
                showToast('Penyesuaian stok tersimpan di server.');
                return;
            }
            const product = state.products.find(item => item.id === data.product); const quantity = Number(data.quantity);
            if (!product || quantity > product.stock) { showToast('Jumlah melebihi stok tersedia.'); return; }
            product.stock -= quantity;
            state.expenses.unshift({ id: `LOSS-${String(Date.now()).slice(-5)}`, date: dateIso(), label: `Kerugian ${product.name}: ${data.note}`, category: 'Kerugian barang', amount: quantity * product.cost, method: 'Penyesuaian' });
            addActivity(`Penyesuaian stok ${product.name}: -${quantity} ${product.unit}`); showToast('Penyesuaian stok tersimpan.');
        });
    }
    function addExpenseModal() {
        showModal('Catat pengeluaran', 'Pengeluaran akan masuk ke laporan arus kas.', `${field('Keterangan', 'label', 'text', { full: true })}${field('Kategori', 'category', 'select', { options: ['Operasional', 'Utilitas', 'Transportasi', 'Pembelian stok', 'Kerugian barang', 'Lainnya'] })}${field('Jumlah (Rp)', 'amount', 'number')}${field('Metode pembayaran', 'method', 'select', { options: ['Tunai', 'Transfer', 'QRIS'] })}`, async data => {
            if (databaseMode) {
                await apiRequest('/expenses', { method: 'POST', body: { ...data, amount: Number(data.amount) } });
                await loadServerWorkspace();
                showToast('Pengeluaran tersimpan di server.');
                return;
            }
            state.expenses.unshift({ id: `EXP-${String(Date.now()).slice(-5)}`, date: dateIso(), ...data, amount: Number(data.amount) }); addActivity(`Mencatat pengeluaran ${data.label}`); showToast('Pengeluaran berhasil dicatat.');
        });
    }
    function addDebtModal(type) {
        const isReceivable = type === 'receivable';
        showModal(isReceivable ? 'Catat piutang pelanggan' : 'Catat utang pemasok', 'Masukkan nilai tagihan dan tanggal jatuh tempo.', `${field(isReceivable ? 'Nama pelanggan' : 'Nama pemasok', 'party')}${field(isReceivable ? 'Nomor telepon' : 'Nomor referensi', 'note', 'text', { required: false })}${field('Nilai tagihan (Rp)', 'amount', 'number')}${field('Jatuh tempo', 'due', 'date', { value: dateIso(14) })}`, async data => {
            if (databaseMode) {
                await apiRequest('/debts', { method: 'POST', body: { ...data, type, amount: Number(data.amount) } });
                await loadServerWorkspace();
                showToast(`${isReceivable ? 'Piutang' : 'Utang'} tersimpan di server.`);
                return;
            }
            const list = isReceivable ? state.receivables : state.payables;
            list.unshift({ id: `${isReceivable ? 'PIU' : 'UTG'}-${String(Date.now()).slice(-5)}`, [isReceivable ? 'customer' : 'supplier']: data.party, ...(isReceivable ? { phone: data.note } : {}), original: Number(data.amount), paid: 0, due: data.due, note: data.note });
            addActivity(`Mencatat ${isReceivable ? 'piutang' : 'utang'} ${data.party}`); showToast('Tagihan berhasil dicatat.');
        });
    }
    function addSupplierModal() {
        showModal('Tambah pemasok', 'Simpan informasi kontak distributor baru.', `${field('Nama pemasok', 'name')}${field('Nomor telepon', 'contact', 'tel')}${field('Skema pembayaran', 'status', 'select', { options: ['Tunai', 'Kredit 14 hari', 'Kredit 30 hari'] })}`, async data => {
            if (databaseMode) {
                await apiRequest('/suppliers', { method: 'POST', body: { name: data.name, phone: data.contact, payment_terms: data.status } });
                await loadServerWorkspace();
                showToast('Pemasok tersimpan di server.');
                return;
            }
            state.suppliers.unshift({ ...data, lastOrder: dateIso(), total: 0 }); addActivity(`Menambahkan pemasok ${data.name}`); showToast('Pemasok ditambahkan.');
        });
    }
    function addEmployeeModal() {
        showModal('Tambah karyawan', 'Berikan peran akses sesuai tanggung jawab operasional.', `${field('Nama lengkap', 'name')}${field('Peran', 'role', 'select', { options: ['Kasir', 'Petugas Gudang', 'Owner'] })}`, data => { state.employees.push({ id: `EMP-${String(Date.now()).slice(-4)}`, name: data.name, role: data.role, initials: initials(data.name), status: 'Aktif' }); addActivity(`Menambahkan pengguna ${data.name}`); showToast('Pengguna baru ditambahkan.'); });
    }
    function recordDebtPayment(id, type) {
        const debt = (type === 'receivable' ? state.receivables : state.payables).find(item => item.id === id);
        if (!debt) return;
        showModal(type === 'receivable' ? 'Catat cicilan pelanggan' : 'Catat pembayaran pemasok', `Sisa saldo ${currency(debt.original - debt.paid)}.`, `${field('Jumlah pembayaran (Rp)', 'amount', 'number')}${field('Metode', 'method', 'select', { options: ['Tunai', 'Transfer', 'QRIS'] })}`, async data => {
            if (databaseMode) {
                await apiRequest('/debt-payments', { method: 'POST', body: { id: Number(id), type, amount: Number(data.amount), method: data.method } });
                await loadServerWorkspace();
                showToast('Pembayaran tersimpan di server.');
                return;
            }
            const amount = Number(data.amount); if (amount > debt.original - debt.paid) { showToast('Pembayaran melebihi sisa saldo.'); return; }
            debt.paid += amount;
            if (type === 'payable') state.expenses.unshift({ id: `PAY-${String(Date.now()).slice(-5)}`, date: dateIso(), label: `Pembayaran ${debt.supplier}`, category: 'Pembayaran utang', amount, method: data.method });
            addActivity(`Mencatat pembayaran ${type === 'receivable' ? 'piutang' : 'utang'} ${type === 'receivable' ? debt.customer : debt.supplier}`); showToast('Pembayaran berhasil dicatat.');
        });
    }
    function checkout() {
        const saleType = document.querySelector('input[name="sale-type"]:checked')?.value || 'retail';
        const payment = document.querySelector('input[name="payment"]:checked')?.value || 'Tunai';
        const name = document.getElementById('customer-name').value.trim() || 'Pelanggan umum';
        const lines = state.cart.map(line => ({ ...line, product: state.products.find(product => product.id === line.id) })).filter(line => line.product);
        if (!lines.length) return;
        if (databaseMode) { void checkoutOnServer(lines, saleType, payment, name); return; }
        const invalid = lines.find(line => line.qty > line.product.stock);
        if (invalid) { showToast(`Stok ${invalid.product.name} tidak mencukupi.`); return; }
        const total = lines.reduce((sum, line) => sum + line.product[saleType] * line.qty, 0);
        const cost = lines.reduce((sum, line) => sum + line.product.cost * line.qty, 0);
        const id = `TRX-${today.toISOString().slice(2, 10).replaceAll('-', '')}-${String(Date.now()).slice(-4)}`;
        lines.forEach(line => { line.product.stock -= line.qty; });
        state.transactions.unshift({ id, date: dateIso(), customer: name, payment, total, cost, status: payment === 'Bon' ? 'Belum lunas' : 'Lunas', items: lines.reduce((sum, line) => sum + line.qty, 0), cashier: getUser().name });
        if (payment === 'Bon') state.receivables.unshift({ id: `PIU-${String(Date.now()).slice(-5)}`, customer: name, phone: '', original: total, paid: 0, due: dateIso(14), note: id });
        state.cart = []; addActivity(`Menyelesaikan transaksi ${id}`); save();
        if (confirm(`Transaksi ${id} berhasil. Total ${currency(total)}.\nCetak struk sekarang?`)) printReceipt({ id, name, total, payment, lines, saleType });
        else showToast(`Transaksi ${id} berhasil disimpan.`);
        navigate('dashboard');
    }
    async function checkoutOnServer(lines, saleType, payment, name) {
        try {
            const response = await apiRequest('/transactions', { method: 'POST', body: {
                customer_name: name,
                payment_method: payment,
                sale_type: saleType,
                lines: lines.map(line => ({ product_id: Number(line.id), quantity: Number(line.qty) }))
            } });
            await loadServerWorkspace();
            showToast(`Transaksi ${response.transaction.id} tersimpan di server.`);
            navigate('dashboard');
        } catch (error) {
            showToast(error.message || 'Transaksi gagal disimpan.');
        }
    }
    function printReceipt(sale) {
        const receipt = window.open('', '_blank', 'width=360,height=640');
        if (!receipt) { showToast('Izinkan pop-up untuk mencetak struk.'); return; }
        receipt.document.write(`<html><head><title>Struk ${escapeHtml(sale.id)}</title><style>body{font:12px monospace;width:280px;margin:24px auto;color:#222}h2,p{text-align:center;margin:4px 0}hr{border:0;border-top:1px dashed #555;margin:12px 0}.row{display:flex;justify-content:space-between;margin:7px 0}.small{font-size:10px;color:#555}</style></head><body><h2>TOKO MIRA PLASTIK</h2><p>Struk penjualan</p><hr><div class="row"><span>${escapeHtml(sale.id)}</span><span>${new Date().toLocaleString('id-ID')}</span></div><div class="row"><span>Pelanggan</span><span>${escapeHtml(sale.name)}</span></div>${sale.lines.map(line => `<div class="row"><span>${escapeHtml(line.product.name)} × ${line.qty}</span><span>${currency(line.product[sale.saleType] * line.qty)}</span></div>`).join('')}<hr><div class="row"><strong>Total</strong><strong>${currency(sale.total)}</strong></div><div class="row"><span>Pembayaran</span><span>${escapeHtml(sale.payment)}</span></div><hr><p>Terima kasih telah berbelanja</p><script>window.print()<\/script></body></html>`);
        receipt.document.close();
    }
    function exportCsv(filename, rows) {
        const csv = rows.map(row => row.map(value => `"${String(value ?? '').replaceAll('"', '""')}"`).join(',')).join('\r\n');
        const link = document.createElement('a'); link.href = URL.createObjectURL(new Blob(['\ufeff', csv], { type: 'text/csv;charset=utf-8' })); link.download = filename; link.click(); URL.revokeObjectURL(link.href);
    }
    function exportReport() {
        exportCsv('laporan-mira-plastik.csv', [['Tanggal', 'Transaksi', 'Pelanggan', 'Pembayaran', 'Penjualan', 'Modal', 'Status'], ...state.transactions.map(tx => [tx.date, tx.id, tx.customer, tx.payment, tx.total, tx.cost, tx.status]), [], ['Pengeluaran', 'Tanggal', 'Kategori', 'Jumlah'], ...state.expenses.map(item => [item.label, item.date, item.category, item.amount])]);
    }

    document.addEventListener('click', event => {
        const nav = event.target.closest('[data-view]'); if (nav) { navigate(nav.dataset.view); return; }
        if (event.target.closest('[data-action="add-product"]')) { addProductModal(); return; }
        const category = event.target.closest('[data-category]'); if (category) { productCategory = category.dataset.category; renderPos(); return; }
        const add = event.target.closest('[data-add-cart]'); if (add) { const product = state.products.find(item => item.id === add.dataset.addCart); const line = state.cart.find(item => item.id === product.id); if (line) line.qty += 1; else state.cart.push({ id: product.id, qty: 1 }); save(); renderPos(); return; }
        const increment = event.target.closest('[data-cart-inc]'); if (increment) { const line = state.cart.find(item => item.id === increment.dataset.cartInc); const product = state.products.find(item => item.id === line.id); if (line.qty < product.stock) line.qty += 1; else showToast('Jumlah melebihi stok tersedia.'); save(); renderPos(); return; }
        const decrement = event.target.closest('[data-cart-dec]'); if (decrement) { const line = state.cart.find(item => item.id === decrement.dataset.cartDec); if (line.qty <= 1) state.cart = state.cart.filter(item => item.id !== line.id); else line.qty -= 1; save(); renderPos(); return; }
        if (event.target.closest('[data-action="checkout"]')) { checkout(); return; }
        if (event.target.closest('[data-action="restock"]')) { restockModal(); return; }
        const restockProduct = event.target.closest('[data-restock-product]'); if (restockProduct) { restockModal(restockProduct.dataset.restockProduct); return; }
        if (event.target.closest('[data-action="loss-stock"]')) { markLossModal(); return; }
        if (event.target.closest('[data-action="add-expense"]')) { addExpenseModal(); return; }
        if (event.target.closest('[data-action="add-receivable"]')) { addDebtModal('receivable'); return; }
        if (event.target.closest('[data-action="add-payable"]')) { addDebtModal('payable'); return; }
        if (event.target.closest('[data-action="add-supplier"]')) { addSupplierModal(); return; }
        if (event.target.closest('[data-action="add-employee"]')) { addEmployeeModal(); return; }
        const payDebt = event.target.closest('[data-pay-debt]'); if (payDebt) { recordDebtPayment(payDebt.dataset.payDebt, payDebt.dataset.debtType); return; }
        if (event.target.closest('[data-action="archive-month"]')) {
            const month = document.getElementById('archive-month').value; const year = document.getElementById('archive-year').value; const period = `${year}-${month}`;
            if (state.archives.some(item => item.period === period)) { showToast('Periode ini sudah diarsipkan.'); return; }
            const summary = monthlySummary(Number(year), Number(month)); const label = new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(new Date(Number(year), Number(month) - 1, 1));
            if (databaseMode) {
                void (async () => {
                    await apiRequest('/report-archives', { method: 'POST', body: { period, snapshot: { label, ...summary, stock: state.products.map(({ id, name, stock }) => ({ id, name, stock })) } } });
                    await loadServerWorkspace();
                    renderReports();
                    showToast(`Laporan ${label} tersimpan di server.`);
                })().catch(error => showToast(error.message || 'Arsip laporan gagal disimpan.'));
                return;
            }
            state.archives.push({ period, label, archivedAt: dateIso(), ...summary, stock: state.products.map(({ id, name, stock }) => ({ id, name, stock })) }); addActivity(`Mengarsipkan laporan ${label}`); save(); renderReports(); showToast(`Laporan ${label} berhasil diarsipkan.`); return;
        }
        if (event.target.closest('[data-action="export-report"]')) { exportReport(); return; }
        if (event.target.closest('[data-action="export-stock"]')) { exportCsv('stok-mira-plastik.csv', [['Kode', 'Produk', 'Kategori', 'Stok', 'Minimum', 'Eceran', 'Grosir'], ...state.products.map(item => [item.id, item.name, item.category, item.stock, item.min, item.retail, item.wholesale])]); return; }
        if (event.target.closest('[data-action="export-cashflow"]')) { exportCsv('arus-kas-mira-plastik.csv', [['Tanggal', 'Keterangan', 'Kategori', 'Metode', 'Jumlah'], ...state.expenses.map(item => [item.date, item.label, item.category, item.method, -item.amount]), ...state.transactions.filter(item => item.payment !== 'Bon').map(item => [item.date, item.id, 'Penjualan', item.payment, item.total])]); return; }
        if (event.target.closest('[data-action="export-activity"]')) { exportCsv('aktivitas-mira-plastik.csv', [['Waktu', 'Aktivitas', 'Pengguna', 'Jenis'], ...state.activity.map(item => [item.time, item.action, item.user, item.type])]); return; }
        const archiveExport = event.target.closest('[data-export-archive]'); if (archiveExport) { const item = state.archives.find(archive => archive.period === archiveExport.dataset.exportArchive); if (item) exportCsv(`arsip-mira-${item.period}.csv`, [['Periode', item.label], ['Transaksi', item.transactions], ['Penjualan', item.sales], ['Modal barang', item.cogs], ['Biaya operasional', item.expenses], ['Laba bersih', item.profit], [], ['Kode', 'Nama barang', 'Stok akhir'], ...item.stock.map(product => [product.id, product.name, product.stock])]); return; }
        if (event.target.closest('[data-action="print-report"]')) { window.print(); return; }
        if (event.target.closest('#notification-button')) {
            const button = document.getElementById('notification-button');
            const panel = document.getElementById('notification-popover');
            panel.hidden = !panel.hidden;
            button.setAttribute('aria-expanded', String(!panel.hidden));
            return;
        }
        const backdrop = event.target.closest('[data-close-modal]'); if (backdrop) closeModal();
    });
    document.addEventListener('input', event => {
        if (event.target.id === 'product-search') { productSearch = event.target.value; const cursor = event.target.selectionStart; renderPos(); const input = document.getElementById('product-search'); input.focus(); input.setSelectionRange(cursor, cursor); }
        if (event.target.id === 'inventory-search') { const cursor = event.target.selectionStart; renderInventory(); const input = document.getElementById('inventory-search'); input.focus(); input.setSelectionRange(cursor, cursor); }
        if (event.target.id === 'archive-search') document.getElementById('archive-list').innerHTML = archiveRows(event.target.value);
    });
    document.addEventListener('change', event => {
        if (event.target.name === 'sale-type') {
            posSaleType = event.target.value;
            const lines = state.cart.map(line => ({ ...line, product: state.products.find(product => product.id === line.id) }));
            lines.forEach((line, index) => { const price = root.querySelectorAll('.cart-line-price')[index]; if (price) price.textContent = `${currency(line.product[posSaleType])} / ${line.product.unit}`; });
            const subtotal = lines.reduce((sum, line) => sum + line.product[posSaleType] * line.qty, 0);
            root.querySelectorAll('.summary-line span:last-child').forEach(value => { value.textContent = currency(subtotal); });
        }
        if (event.target.name === 'payment') posPayment = event.target.value;
        if (event.target.id === 'report-year') renderReports();
        if (event.target.id === 'stock-filter') {
            window.stockFilter = event.target.value;
            const query = (document.getElementById('inventory-search')?.value || '').toLowerCase();
            const rows = [...root.querySelectorAll('tbody tr')];
            rows.forEach(row => { const text = row.textContent.toLowerCase(); const isLow = text.includes('menipis') || text.includes('habis'); row.hidden = (window.stockFilter === 'low' && !isLow) || (window.stockFilter === 'safe' && isLow) || !text.includes(query); });
        }
        if (event.target.matches('.employee-role')) {
            const employee = state.employees.find(item => item.id === event.target.dataset.employeeRole);
            if (employee) { employee.role = event.target.value; addActivity(`Mengubah peran akses ${employee.name} menjadi ${employee.role}`); save(); renderTeam(); showToast('Hak akses diperbarui.'); }
        }
    });
    document.addEventListener('keydown', event => { if (event.key === 'F2') { event.preventDefault(); navigate('pos'); document.getElementById('product-search')?.focus(); } if (event.key === 'Escape') closeModal(); });
    document.getElementById('mobile-menu').addEventListener('click', () => document.getElementById('sidebar').classList.toggle('open'));
    async function initializeWorkspace() {
        setRoleUi();
        if (databaseMode) {
            document.getElementById('storage-label').innerHTML = 'Data tersimpan di server <i class="sync-dot"></i>';
            try {
                await loadServerWorkspace();
            } catch (error) {
                root.innerHTML = `<section class="panel"><h1 class="panel-title">Data toko belum dapat dimuat</h1><p class="panel-subtitle">${escapeHtml(error.message)}</p></section>`;
                return;
            }
        }
        renderNotifications();
        const initialView = window.location.hash.slice(1);
        navigate(renderers[initialView] ? initialView : 'dashboard');
    }
    void initializeWorkspace();
})();