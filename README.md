# Sistem Informasi Manajemen Operasional Terintegrasi (SIOM-POS)
> **Multi-Tenant Retail Management & Operational System** — *Studi Kasus: Toko Mira Plastik*

![CodeIgniter 4](https://img.shields.io/badge/Framework-CodeIgniter%204-EF4223?style=flat-square&logo=codeigniter&logoColor=white)
![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-777BB4?style=flat-square&logo=php&logoColor=white)
![Database](https://img.shields.io/badge/Database-MySQL%20%2F%20MariaDB-4479A1?style=flat-square&logo=mysql&logoColor=white)
![Architecture](https://img.shields.io/badge/Architecture-Multi--Tenant%20%26%20RBAC-007ACC?style=flat-square)
![License](https://img.shields.io/badge/License-MIT-green.style=flat-square)

---

## 📌 Deskripsi Proyek

**Sistem Informasi Manajemen Operasional Terintegrasi (SIOM-POS)** adalah aplikasi berbasis web yang dirancang untuk mengelola operasional bisnis retail modern secara menyeluruh. Dibuat menggunakan framework **CodeIgniter 4**, sistem ini tidak hanya melayani transaksi Point of Sale (POS) harian, tetapi juga menyediakan manajemen persediaan barang, pencatatan keuangan, jejak audit (*audit log*), dan arsitektur **Multi-Tenant / Multi-Workspace** untuk mendukung pengelolaan banyak cabang/toko dalam satu platform terpusat.

Proyek ini dikembangkan sebagai portofolio sistem informasi enterprise dengan penerapan prinsip *clean code*, *Role-Based Access Control* (RBAC), serta keamanan transaksi terintegrasi.

---

## ✨ Fitur Utama

### 1. 🏢 Arsitektur Multi-Tenant & Workspace
* **Tenant Isolation:** Pemisahan data antar toko/tenant secara aman pada tingkatan database dan aplikasi.
* **Workspace Switcher:** Pengguna dapat mengelola beberapa cabang/toko sesuai dengan hak akses yang dimiliki.
* **Tenant Setup Wizard:** Alur registrasi dan konfigurasi awal ruang kerja baru secara cepat.

### 2. 🔐 Autentikasi & Keamanan Ketat
* **Role-Based Access Control (RBAC):** Pembatasan hak akses berbasis peran (Admin, Kasir, Gudang, Pemilik).
* **Login Rate Throttling:** Perlindungan terhadap serangan *brute-force* pada halaman autentikasi.
* **CSRF & Permission Filters:** Middleware validasi token keamanan dan izin akses endpoint API/Halaman secara *real-time*.

### 3. 🛍️ Operasional Retail & POS (Point of Sale)
* **Pencatatan Transaksi:** Antarmuka kasir cepat dengan perhitungan otomatis diskon dan kembalian.
* **Manajemen Stok & Inventori:** Pemantauan masuk-keluar barang, stok minimum, dan riwayat pergerakan produk.
* **Manajemen Katalog Produk:** Pengelompokan kategori, varian harga, serta pencarian instan berbasis SKU/Barcode.

### 4. 📊 Keuangan, Laporan & Jejak Audit
* **Laporan Penjualan & Keuangan:** Ringkasan pendapatan harian, mingguan, dan bulanan per cabang.
* **Audit Trail:** Pencatatan aktivitas sistem untuk transparansi operasional dan keamanan data keuangan.

---

## 🛠️ Tech Stack & Modul Utama

* **Backend Framework:** CodeIgniter 4 (PHP 8.1+)
* **Database:** MySQL / MariaDB (Migration & Seeder berbasis CI4 CLI)
* **Frontend:** Bootstrap / Custom CSS (`auth.css`, responsive layout)
* **Architecture Design:**
  * Custom Libraries: `TenantAuthRepository`, `TenantWorkspaceRepository`, `LocalUserStore`
  * Security Filters: `AuthFilter`, `PermissionFilter`, `CsrfFilter`, `LoginThrottleFilter`
  * Migrations: `CreateTenantIdentity`, `CreateTenantRetailOperations`, `CreateTenantFinanceAndAudit`

---

## 📂 Struktur Direktori Proyek

```text
CI-4SIOMPOS_tokomira/
├── app/
│   ├── Commands/             # Custom CLI Commands (e.g. VerifyPermissionFilter)
│   ├── Config/               # Konfigurasi Auth, Routes, Filters, & Database
│   ├── Controllers/
│   │   ├── Api/              # REST API Endpoint (Workspace, Transaksi, dll.)
│   │   ├── Auth.php          # Controller Autentikasi
│   │   └── Home.php          # Controller Dashboard Utama
│   ├── Database/
│   │   └── Migrations/       # Skema Database Multi-Tenant & Retail
│   ├── Filters/              # Middleware Keamanan & RBAC
│   ├── Libraries/            # Repositori & Business Logic kustom
│   └── Views/                # Antarmuka Tampilan (Auth, Setup, Dashboard)
├── docs/
│   └── database/             # Dokumentasi Skema Database Multi-Tenant (.md)
├── public/                   # Asset Statis (CSS, JS, Images) & Entry Point
└── env                       # Konfigurasi Environment Aplikasi