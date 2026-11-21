# Libraria — Sistem Informasi Manajemen Perpustakaan Offline

Aplikasi web manajemen perpustakaan **fullstack** berbasis **Laravel 12 + Blade + Bootstrap 5 + Chart.js + Lucide Icons + MySQL/MariaDB lokal**. Seluruh aset (CSS, JS, ikon, font) disimpan di dalam proyek dan aplikasi **tidak memerlukan internet** saat dijalankan.

---

## 1. Fitur Utama

| Modul | Admin | Petugas |
|---|---|---|
| Dashboard statistik & grafik real-time | ✅ | ✅ (versi operasional) |
| Data buku (CRUD + filter + upload cover) | ✅ | lihat saja |
| Katalog pencarian (judul/ISBN/penulis/penerbit/kategori) | ✅ | ✅ |
| Kategori, Penulis, Penerbit, Rak Buku | ✅ | ❌ |
| Data anggota (hanya 3 field) | lihat saja | ✅ |
| Peminjaman (multi buku, batas & durasi dari pengaturan) | ❌ | ✅ |
| Pengembalian (termasuk sebagian) | ❌ | ✅ |
| Denda otomatis + pembayaran + bukti cetak | monitor | ✅ |
| Riwayat transaksi & filter tanggal | ✅ | ✅ |
| Monitoring petugas & log aktivitas | ✅ | ❌ |
| Laporan + cetak + PDF + Excel | ✅ | ❌ |
| Manajemen akun & pengaturan | ✅ | ❌ |
| Backup & restore database lokal | ✅ | ❌ |

### Data anggota sengaja dibatasi hanya:
`member_code` (otomatis: AGT-0001), `full_name`, `school`, `origin`, `created_at`, `updated_at`.
Tidak ada NIS, NIK, telepon, alamat, email, foto, atau data pribadi lain.

### Status otomatis
Status pinjaman, detail buku, denda, dan stok buku dihitung ulang pada setiap request (`App\Support\LoanStatus`). Jadi status "Terlambat" tetap benar walaupun komputer baru dinyalakan kembali setelah offline.

---

## 2. Persyaratan

- Laragon (disarankan) atau XAMPP
- PHP 8.2+ (dengan ekstensi `pdo_mysql`, `mbstring`, `gd`, `zip`, `intl`)
- MySQL 8 / MariaDB 10+
- Composer 2
- Node.js hanya diperlukan bila ingin mengunduh ulang aset vendor (tidak dibutuhkan saat runtime)

> Di Laragon, aktifkan `extension=zip` di `php.ini` agar Composer dapat mengekstrak paket.

---

## 3. Instalasi (Laragon)

1. Letakkan proyek di `C:\laragon\www\libraria`.
2. Pastikan MySQL Laragon berjalan, lalu buat database:
   ```sql
   CREATE DATABASE libraria CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Pastikan file `.env` berisi:
   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=libraria
   DB_USERNAME=root
   DB_PASSWORD=
   ```
   Sesuaikan `DB_USERNAME`/`DB_PASSWORD` bila password MySQL Anda tidak kosong.
4. Install dependency dan siapkan database:
   ```bash
   composer install
   php artisan key:generate
   php artisan migrate:fresh --seed
   php artisan storage:link
   ```
5. Akses aplikasi. Nilai `APP_URL` bawaan adalah `http://localhost/libraria/public`, jadi buka URL tersebut di browser saat memakai Laragon dengan document root `C:\laragon\www`.

   Jika ingin memakai `php artisan serve`, ubah dulu `APP_URL` di `.env` menjadi `http://127.0.0.1:8000`, lalu jalankan:
   ```bash
   php artisan serve
   ```
   Begitu pula bila membuat virtual host Laragon `libraria.test`, sesuaikan `APP_URL` menjadi `http://libraria.test`.

---

## 4. Aset Offline

Semua pustaka sudah disalin ke dalam proyek:

| Pustaka | Lokasi |
|---|---|
| Bootstrap 5.3 | `public/vendor/css/bootstrap.min.css` |
| Bootstrap Bundle JS | `public/vendor/js/bootstrap.bundle.min.js` |
| Chart.js 4 | `public/vendor/js/chart.umd.js` |
| Lucide Icons (SVG) | `public/vendor/icons/*.svg` |
| CSS tema | `public/css/app.css` |
| Dompdf (composer) | `vendor/barryvdh/laravel-dompdf` |

Tidak ada tag `<script src="https://...">` atau `@vite` yang memerlukan jaringan. Font menggunakan font sistem (`Segoe UI`).

Jika perlu mengunduh ulang aset:
```bash
npm install bootstrap@5.3.3 chart.js@4.4.8 lucide-static@0.468.0
# lalu salin file dist ke folder public/vendor seperti struktur di atas
```

---

## 5. Akun Awal (Seeder)

| Role | Email | Password |
|---|---|---|
| Admin | `admin@libraria.local` | `password` |
| Petugas | `petugas@libraria.local` | `password` |

Seeder juga membuat 8 kategori, 8 penulis, 6 penerbit, 5 rak, 20 buku, 10 anggota, serta contoh transaksi peminjaman/pengembalian/denda agar grafik langsung menampilkan data nyata.

> Untuk lingkungan produksi, segera ganti password kedua akun tersebut.

---

## 6. Alur Operasional Singkat

1. **Petugas → Data Anggota → Tambah Anggota** (isi 3 field).
2. **Petugas → Peminjaman**: cari anggota → pilih buku → konfirmasi. Stok berkurang otomatis, tanggal jatuh tempo dihitung dari `Pengaturan`.
3. **Petugas → Pengembalian**: cari nomor transaksi → centang buku yang dikembalikan → konfirmasi. Stok bertambah, keterlambatan dan denda dihitung otomatis.
4. **Petugas → Data Denda → Bayar** (boleh sebagian) → bukti pembayaran dapat dicetak.
5. **Admin → Laporan**: filter tanggal/petugas/kategori/status, lalu Cetak / PDF / Excel.
6. **Admin → Backup Database**: buat file `.sql` di `public/backups`, unduh, atau restore dengan konfirmasi `RESTORE`.

---

## 7. Konfigurasi Penting

**Admin → Pengaturan**
- Nama & alamat perpustakaan, logo, informasi
- Maksimal buku per anggota
- Durasi peminjaman (hari)
- Tarif denda per hari

Perubahan langsung memengaruhi transaksi baru dan perhitungan denda.

---

## 8. Struktur Proyek Ringkas

```
app/
  Http/Controllers/     Semua controller modular per fitur
  Http/Middleware/      EnsureRole, ShareLibraryData, UpdateLoanStatuses
  Models/               User, Member, Book, Loan, LoanDetail, BookReturn, Fine, ...
  Support/              Settings, ActivityLogger, LoanStatus, DateFilter
  Helpers/helpers.php   icon(), rupiah(), tanggal(), status_badge(), sort_link()
database/migrations/    Skema 14 tabel + sessions/cache/jobs
database/seeders/       Akun awal + data demo
resources/views/        Blade: layouts, dashboard, books, catalog, members,
                        loans, returns, fines, reports, monitoring, logs, ...
public/vendor/          Aset lokal (Bootstrap, Chart.js, Lucide)
public/backups/         File backup database lokal
public/css/app.css      Tema coffee-brown + caramel responsif
public/js/login-lamp.js Login interaktif: perpustakaan gelap, lampu meja, dan tali tarik
```

### Tabel database
`users, members, books, categories, authors, publishers, shelves, loans, loan_details, returns, return_details, fines, fine_payments, activity_logs, settings` (+ `sessions, cache, jobs, password_reset_tokens, migrations`).

Foreign key digunakan dengan `restrictOnDelete` pada data transaksi sehingga riwayat tidak bisa hilang.

---

## 9. Keamanan & Hak Akses

- Password di-hash (bcrypt).
- Middleware `auth` + `role:admin|petugas` diterapkan di **backend** pada grup route.
- Controller juga memeriksa peran untuk aksi sensitif (tambah anggota, transaksi, pembayaran).
- Redirect ke 403 bila peran tidak sesuai.
- CSRF aktif pada semua form.
- Log aktivitas bersifat append-only (tidak ada route edit/hapus).

---

## 10. Pengujian

```bash
php artisan test
```

Atau lakukan uji manual dengan akun demo di atas.

---

## 11. Catatan Backup / Restore

- Backup memakai `mysqldump` dari Laragon; jika biner tidak tersedia **atau gagal membuka socket** (misalnya saat memakai `php artisan serve`), sistem otomatis fallback ke dump PHP via PDO. Pemulihan juga memiliki fallback PDO dengan konfirmasi `RESTORE`.
- File backup disimpan di `public/backups` dan dapat diunduh langsung melalui browser.
- Restore memerlukan mengetik konfirmasi **`RESTORE`** dan akan menimpa database saat ini.
- Pastikan backup terbaru sudah diunduh sebelum melakukan restore.

---

Dibuat untuk penggunaan lokal/offline di localhost. Seluruh angka pada dashboard dan laporan berasal dari database MySQL, bukan data statis.
