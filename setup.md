# Panduan Deploy JP Document Tracking

## Prasyarat Server Windows

- PHP 8.3 terinstall di `D:\php-8.3.16-nts\`
- Composer terinstall di `C:\ProgramData\ComposerSetup\bin\`
- IIS dengan modul **URL Rewrite** aktif
- SQL Server dapat diakses di jaringan lokal
- Jenkins terinstall dan berjalan

---

## 1. Persiapan IIS

### 1.1 Install Modul URL Rewrite (jika belum ada)

Download dan install dari: https://www.iis.net/downloads/microsoft/url-rewrite

### 1.2 Install PHP Manager for IIS (opsional, untuk kemudahan konfigurasi)

Download dari: https://www.iis.net/downloads/community/2018/05/php-manager-150-for-iis-10

### 1.3 Daftarkan PHP ke IIS

1. Buka **IIS Manager**
2. Klik server (root) → **Handler Mappings** → **Add Module Mapping**
   - Request path: `*.php`
   - Module: `FastCgiModule`
   - Executable: `D:\php-8.3.16-nts\php-cgi.exe`
   - Name: `PHP_via_FastCGI`

> Atau gunakan PHP Manager for IIS untuk langkah ini lebih mudah.

### 1.4 Buat App Pool Baru

1. Di IIS Manager → **Application Pools** → **Add Application Pool**
   - Name: `jp-document-tracking`
   - .NET CLR Version: **No Managed Code**
   - Pipeline mode: **Integrated**

### 1.5 Tambahkan Application di Site

1. Di IIS Manager, expand **Sites** → klik kanan `web.jessindo.net` → **Add Application**
   - Alias: `jp-document-tracking`
   - Application pool: `jp-document-tracking`
   - Physical path: `D:\projects\jp-document-tracking\public`

2. Klik **OK**

### 1.6 Permission Folder

Pastikan user App Pool (`IIS AppPool\jp-document-tracking`) punya akses **Read/Write** ke:
- `D:\projects\jp-document-tracking\storage`
- `D:\projects\jp-document-tracking\bootstrap\cache`

Caranya: klik kanan folder → Properties → Security → Edit → Add → masukkan `IIS AppPool\jp-document-tracking`

### 1.7 Buat Folder Project

Buat folder deploy terlebih dahulu jika belum ada:
```
mkdir D:\projects\jp-document-tracking
```

---

## 2. Persiapan File .env Production

Buat file `.env` untuk production dengan isi berikut, lalu sesuaikan nilainya:

```env
APP_NAME="JP Document Tracking"
APP_ENV=production
APP_KEY=base64:****
APP_DEBUG=false
APP_URL=https://web.jessindo.net/jp-document-tracking

DB_CONNECTION=sqlsrv
DB_HOST=****
DB_PORT=1433
DB_DATABASE=JP_DOCUMENT_TRACKING
DB_USERNAME=****
DB_PASSWORD=****
DB_TRUST_SERVER_CERTIFICATE=yes
DB_ENCRYPT=no

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local
```

> **Catatan:** Generate `APP_KEY` dengan menjalankan `php artisan key:generate --show` di lokal, lalu copy hasilnya ke `.env` production.

---

## 3. Setup Credentials di Jenkins

`Manage Jenkins` → `Credentials` → `Global` → `Add Credentials`

| Field | Value |
|---|---|
| Kind | Secret file |
| ID | `jp-doc-env` |
| File | Upload file `.env` production yang sudah disiapkan di langkah 2 |

---

## 4. Buat Pipeline Project di Jenkins

1. `New Item` → nama: `jp-document-tracking` → pilih **Pipeline** → OK

2. Di bagian **Pipeline**:
   - Definition: `Pipeline script from SCM`
   - SCM: `Git`
   - Repository URL: `https://github.com/mdwisu/jp-document-tracking`
   - Branch: `*/main`
   - Script Path: `Jenkinsfile`

3. Klik **Save** lalu **Build Now**

---

## 5. Struktur Pipeline (Jenkinsfile)

| Stage | Keterangan |
|---|---|
| Checkout | Clone repo dari GitHub ke workspace Jenkins |
| Install Dependencies | `composer install --no-dev` |
| Prepare .env | Copy `.env` dari Jenkins Credentials ke workspace |
| Migrate | Jalankan `php artisan migrate --force` |
| Deploy ke IIS | Copy file ke `D:\projects\jp-document-tracking` via robocopy |
| Optimize di Deploy Dir | Jalankan config/route/view cache + storage:link dari deploy dir |
| Recycle App Pool | Restart app pool IIS `jp-document-tracking` |

---

## 6. Akses Aplikasi

Setelah build sukses, aplikasi dapat diakses di:

```
https://web.jessindo.net/jp-document-tracking
```

---

## Troubleshooting

**Build gagal di stage Install Dependencies**
- Pastikan `D:\php-8.3.16-nts` ada di PATH environment Jenkins
- Gunakan `php composer.phar` bukan `composer.bat` (`.bat` konflik dengan env var `COMPOSER` internal)

**storage:link error "already exists"**
- Normal jika sudah pernah dibuild sebelumnya, pipeline otomatis hapus symlink lama sebelum buat baru

**Halaman 500 / tidak bisa diakses**
- Cek `D:\projects\jp-document-tracking\storage\logs\laravel.log`
- Pastikan `APP_DEBUG=false` dan `APP_ENV=production` di `.env` production
- Pastikan IIS Physical path mengarah ke folder `\public`, bukan root project

**Halaman tidak ditemukan (404)**
- Pastikan modul URL Rewrite sudah terinstall di IIS
- Cek `web.config` ada di `D:\projects\jp-document-tracking\public\`
