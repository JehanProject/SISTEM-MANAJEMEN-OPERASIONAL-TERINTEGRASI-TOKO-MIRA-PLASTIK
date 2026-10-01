<?php
$userName = $user['name'] ?? 'Pemilik toko';
$userRole = $user['role'] ?? 'Owner';
$tenantName = $user['tenant_name'] ?? 'Toko Mira Plastik';
$storeName = $user['store_name'] ?? 'Cabang utama';
$userInitials = strtoupper(implode('', array_map(
    static fn (string $part): string => mb_substr($part, 0, 1),
    array_slice(preg_split('/\s+/', trim($userName)) ?: [], 0, 2),
)));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f6f7f2">
    <title>Mira Plastik | Sistem Operasional</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/mira.css">
</head>
<body data-api-mode="<?= $databaseMode ? 'database' : 'local' ?>" data-api-base="<?= esc(site_url('api/v1')) ?>" data-user-name="<?= esc($userName) ?>" data-user-role="<?= esc($userRole) ?>" data-user-initials="<?= esc($userInitials) ?>">
    <div class="app-shell">
        <aside class="sidebar" id="sidebar">
            <a class="brand" href="#dashboard" aria-label="Mira Plastik beranda">
                <span class="brand-mark">M<span>.</span></span>
                <span class="brand-copy"><strong>Mira Plastik</strong><small>OPERASIONAL TOKO</small></span>
            </a>
            <div class="store-switch"><span class="store-dot"></span><span><strong><?= esc($tenantName) ?></strong><small><?= esc($storeName) ?></small></span><span class="chevron">⌄</span></div>
            <div class="nav-label">MENU UTAMA</div>
            <nav class="main-nav" aria-label="Navigasi utama">
                <button class="nav-link active" data-view="dashboard"><span class="nav-icon">⌂</span>Ringkasan</button>
                <button class="nav-link" data-view="pos"><span class="nav-icon">▤</span>Kasir POS <kbd>F2</kbd></button>
                <button class="nav-link" data-view="inventory"><span class="nav-icon">▦</span>Barang & stok</button>
                <button class="nav-link" data-view="debts"><span class="nav-icon">◷</span>Utang & piutang <span class="nav-count" id="debt-count">2</span></button>
                <button class="nav-link" data-view="suppliers"><span class="nav-icon">⇄</span>Pemasok</button>
                <button class="nav-link" data-view="team"><span class="nav-icon">♙</span>Tim & aktivitas</button>
                <div class="nav-label nav-label-spaced">KEUANGAN</div>
                <button class="nav-link" data-view="finance"><span class="nav-icon">▱</span>Arus kas</button>
                <button class="nav-link" data-view="reports"><span class="nav-icon">▥</span>Laporan & arsip</button>
            </nav>
            <div class="sidebar-bottom">
                <div class="help-card"><span class="help-icon">i</span><span><strong>Ruang kendali toko</strong><small>Semua aktivitas tercatat.</small></span></div>
                <div class="profile-button"><span class="avatar avatar-green"><?= esc($userInitials) ?></span><span class="profile-copy"><strong id="current-user-name"><?= esc($userName) ?></strong><small id="current-user-role"><?= esc($userRole) ?></small></span></div>
            </div>
        </aside>

        <main class="main-area">
            <header class="topbar">
                <button class="mobile-menu" id="mobile-menu" aria-label="Buka menu">☰</button>
                <div class="breadcrumb"><span>Workspace</span><span class="crumb-slash">/</span><strong id="page-crumb">Ringkasan</strong></div>
                <div class="top-actions">
                    <div class="today-label"><span class="today-dot"></span><span id="today-label">Hari ini</span></div>
                    <button class="icon-button notification-button" id="notification-button" aria-label="Notifikasi" aria-haspopup="true" aria-expanded="false"><span>♧</span><i></i><span class="notification-count" id="notification-count" hidden></span></button>
                    <div class="notification-popover" id="notification-popover" hidden></div>
                    <div class="role-picker"><span class="avatar avatar-green"><?= esc($userInitials) ?></span><span><strong id="role-name"><?= esc($userName) ?></strong><small id="role-label"><?= esc($userRole) ?></small></span></div>
                    <form action="/logout" method="post" class="logout-form">
                        <?= csrf_field() ?>
                        <button class="logout-button" type="submit" aria-label="Keluar" title="Keluar">↪</button>
                    </form>
                </div>
            </header>
            <div id="view-root" class="view-root"></div>
            <footer class="app-footer"><span>MIRA PLASTIK <span class="footer-separator">/</span> SISTEM OPERASIONAL</span><span id="storage-label">Data tersimpan di perangkat ini <i class="sync-dot"></i></span></footer>
        </main>
    </div>
    <div id="modal-root"></div>
    <div class="toast" id="toast" role="status"></div>
    <script src="/assets/js/mira.js" defer></script>
</body>
</html>