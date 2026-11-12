# Web Stok Grosir

Aplikasi web sederhana untuk CRUD stok toko grosir (mata kuliah Sistem Basis Data).
Kelola barang, kategori, dan satuan; catat stok masuk dan stok keluar; lihat
laporan; dan dapatkan peringatan saat stok menipis.

## Fitur

- Dashboard ringkasan: jumlah jenis barang, total stok, dan daftar barang menipis
- CRUD barang, kategori, dan satuan
- Stok masuk dan stok keluar (riwayat tercatat)
- Laporan stok dan transaksi
- Peringatan stok menipis (batas default: 5)
- Migrasi SQLite ke MySQL (`migrate_sqlite_to_mysql.php`)

## Tech Stack

- PHP (PDO), tanpa framework
- Bootstrap 5 (via CDN)
- SQLite (default) atau MySQL

## Cara Menjalankan

### SQLite (default, tanpa setup database)

`data.db` sudah disertakan sebagai data awal, jadi cukup jalankan:

```bash
php -S localhost:8000
```

Lalu buka http://localhost:8000.

### MySQL (opsional)

1. Atur `config.php`: `'use_mysql' => true` dan isi host, database, user, password.
2. Buat database lalu impor `sqlite_dump_for_mysql.sql`, atau jalankan
   `migrate_sqlite_to_mysql.php` untuk memindahkan data dari SQLite.
3. Jalankan `php -S localhost:8000` seperti di atas.

## Struktur File

- `index.php` — dashboard dan ringkasan stok
- `items.php` — daftar dan CRUD barang
- `stock_in.php`, `stock_out.php` — pencatatan stok masuk / keluar
- `reports.php` — laporan stok dan transaksi
- `db.php` — koneksi database (SQLite/MySQL)
- `config.php` — konfigurasi MySQL
- `data.db` — database SQLite awal (data contoh)
- `migrate_sqlite_to_mysql.php` — migrasi SQLite ke MySQL
- `fix_fk.php`, `renumber_now.php`, `list_sqlite.php` — utilitas database
