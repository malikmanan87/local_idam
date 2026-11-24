# DOKUMEN PERMINTAAN PERUBAHAN SISTEM (CHANGE REQUEST DOCUMENT)
**Sistem:** iDAM (Integrated Disposal & Archive Management) - HoSZA UniSZA  
**Tarikh Dikemaskini:** 24 November 2026  
**Klasifikasi Permintaan:**  
1. **RequestByUser (CR)**: Permintaan keperluan bisnes & fungsi aliran kerja daripada pengguna makmal.
2. **RequestByMe (RBM)**: Permintaan penambahbaikan teknikal, UI/UX, penyeragaman antaramuka, keselamatan & operasi oleh Pentadbir Sistem (Malik Manan).

---

# BAHAGIAN 1: PERMINTAAN OLEH PENGGUNA MAKMAL (REQUEST BY USER)

## 1.1 Ringkasan Status Permintaan Bisnes Pengguna

| No. CR | Tajuk Permintaan | Modul Terlibat | Status / Tindakan | Commit Berkaitan |
| :--- | :--- | :--- | :--- | :--- |
| **CR-01** | Profil Storan Baharu: **Chiller 14 Days** | Maintenance, DB Schema, Specimen, Colors | **SELESAI (Sedia Digunakan)** | `5201a8b`, `131c2b9` |
| **CR-02** | Butang Pilihan Saiz Grid Slot: **100, 70, atau 50 Slots** pada New Entry | New Entry (`bulk_registration`), Controller (`saveBulk`), All List | **TERTANGGUH** (Menunggu Pengesahan Sebelum Pelaksanaan) | - |
| **CR-03** | Butang Pelupusan Pukal: **Dispose All in Queue** | Disposal List, SpecimenController (`disposeAllAjax`) | **SELESAI (Sedia Digunakan)** | `edac385` |
| **CR-04** | Penapisan Kontena & Storan Tidak Aktif (**Filter Inactive**) | All List, New Entry, Maintenance | **SELESAI (Sedia Digunakan)** | `bbfafc5` |
| **CR-05** | Profil Tempoh Fleksibel (**Custom Days Retention**) | DB Schema, Maintenance, Specimen, Disposal Queue, Dashboard | **DALAM PERANCANGAN** (Disimpan untuk Semakan) | - |
| **CR-06** | Pilihan Tarikh Simpanan (**Backdated Sample Registration**) | New Entry (`bulk_registration.php`), Controller (`SpecimenController`) | **DALAM PERANCANGAN** (Disimpan untuk Semakan) | - |

---

## 1.2 Perincian CR-02: Pilihan Saiz Grid (100, 70, 50 Slots) pada New Entry

### A. Latar Belakang & Masalah Semasa
* Pada menu **New Entry** (`specimen` ➔ `forms/bulk_registration.php`), bahagian **Specimen Matrix Slot Placement Grid (Auto-Populating)** dikunci secara kekal pada saiz **100 slot** (`$maxSlots = 100`).
* Dalam operasi makmal sebenar, terdapat pelbagai jenis rak tiub/kotak spesimen dengan kapasiti yang lebih kecil seperti rak 50 lubang atau kotak 70 lubang.

### B. Keperluan Pengguna
Menyediakan butang pilihan segmen (*toggle button group*) di atas grid:  
`[ 100 Slots ]`  `[ 70 Slots ]`  `[ 50 Slots ]` (Lalai: 100 Slots).  
Pengguna boleh memilih saiz grid yang sesuai sebelum memulakan imbasan/pendaftaran sampel.

### C. Aliran Kerja & Logik Yang Disediakan
1. Apabila pengguna menekan butang (contohnya `50 Slots`):
   * Grid secara dinamik menyembunyikan slot 51 hingga 100, hanya memaparkan slot **1 hingga 50**.
   * Kaunter kapasiti (*slotCounter*) terus mengira nisbah berasaskan grid 50 (contoh: `14 / 50 slots occupied`).
   * Fungsi auto-populating textarea imbasan kod spesimen (`processBulkAutoPopulationMapping`) hanya akan menyasarkan slot kosong dalam lingkungan nombor **1 hingga 50**.
   * Jika jumlah imbasan melebihi baki slot dalam grid 50, amaran amaran had kapasiti (*exceed capacity warning*) akan menghalang butang simpan daripada ditekan.
2. Di backend (`SpecimenController::saveBulk`):
   * Menerima input `grid_size` yang dipilih.
   * Memastikan loop carian slot kosong dihadkan sehingga had grid berkenaan (tidak sesekali melepaskan sampel ke slot 51 ke atas jika memilih grid 50).

### D. Kesan Terhadap All List (`/specimen/all_list`)
* **Integriti & Kestabilan**: Tidak akan mengalami ralat (crash-safe).
* **Tingkah Laku Paparan**:
  * Pada fail [all_list.php](file:///c:/xampp/htdocs/idam/app/Views/tables/all_list.php), kod sedia ada melukis 100 slot SVG bagi setiap kotak kontena.
  * Sekiranya kotak tersebut didaftarkan menggunakan grid 50, slot 1 hingga 50 yang ada sampel akan berwarna mengikut tiub spesimen, manakala slot 51 hingga 100 akan kekal kosong berwarna kelabu muda (`#e9ecef`).
  * Jika mahu paparan di `all_list` turut mengecil mengikut kotak (hanya lukis 5 baris bagi kotak 50), maklumat kapasiti kotak boleh disimpan ke dalam jadual `containers` pada fasa berikutnya.

### E. Senarai Fail Yang Terlibat
1. `app/Views/forms/bulk_registration.php`
2. `app/Controllers/SpecimenController.php`

---

## 1.3 Perincian CR-05: Profil Tempoh Custom (Custom Days Retention)

### A. Latar Belakang & Masalah Semasa
* Sistem iDAM sedia ada mengikat kitaran hayat spesimen kepada 4 kategori piawai:
  * `duration_id = 1`: Chiller (7 days)
  * `duration_id = 4`: Chiller (14 days)
  * `duration_id = 2`: Freezer (14 days)
  * `duration_id = 3`: Freezer (21 days)
* Namun, terdapat sampel makmal khas (seperti sampel penyelidikan, rujukan, atau pemantauan klinikal berpanjangan) yang memerlukan tempoh simpanan khusus mengikut tarikh tertentu.

### B. Prinsip Reka Bentuk
1. **Kekalkan Kategori Sedia Ada (7, 14, 21 Hari):**
   * Aliran rutin harian tidak diusik bagi mengekalkan kestabilan sistem.
2. **Tambah Kategori Ke-5 (`duration_id = 5: Custom Days`):**
   * Storan dan kontena khas dicipta di modul Maintenance dengan profil ini.
3. **Penetapan Tarikh Luput Menggunakan Calendar Date Picker:**
   * Apabila pengguna memilih kotak berprofil **Custom Days**:
     * Satu medan input kalendar (**Target Disposal Date / Tarikh Luput Simpanan**) muncul secara automatik.
     * Juruteknologi makmal memilih tarikh sasaran pelupusan secara terus pada kalendar.
     * **Validasi Kalendar**: Tarikh luput mestilah bermula dari hari semasa ke atas (`min="<?= date('Y-m-d') ?>"`).
     * **Pengiraan Hari Automatik**: Sistem secara automatik mengira dan memaparkan bilangan hari simpanan (*contoh: "Tarikh dipilih: 31/12/2026 (Bersamaan 38 hari tempoh simpanan)"*).
     * Nilai tarikh ini disimpan ke dalam lajur `expiry_date` jadual `specimens`.

### C. Aliran Kerja di Modul Pelupusan (Disposal List)
* Diwujudkan **Tab Ke-4: Custom Queue** (`datatableCustom`) di `app/Views/forms/disposal_list.php`.
* Sampel automatik disenaraikan dalam giliran apabila `Tarikh Semasa >= expiry_date`.

### D. Perubahan Modul & Fail Terlibat
1. Pangkalan Data: `specimens.expiry_date DATETIME NULL`.
2. `app/Controllers/MaintenanceController.php` & `app/Views/tables/maintenance.php`.
3. `app/Controllers/SpecimenController.php` & `app/Views/forms/bulk_registration.php`.
4. `app/Views/forms/disposal_list.php` & `app/Controllers/SpecimenController.php`.
5. `app/Controllers/DashboardController.php` & `app/Views/dashboard.php`.
6. `app/Views/tables/all_list.php`.

---

## 1.4 Perincian CR-06: Pilihan Tarikh Simpanan bagi Sampel Backdated

### A. Latar Belakang & Masalah Semasa
* Sampel makmal yang diambil pada hari Jumaat atau syif malam hujung minggu kadangkala hanya sempat diimbas ke dalam sistem pada hari Isnin berikutnya.
* Kekangan sedia ada: Pilihan kotak dikunci kepada hari semasa (`date('w')`) dan umur sampel bermula dari hari imbasan, bukannya hari sampel sebenar disimpan.

### B. Prinsip Reka Bentuk & Kawalan Keselamatan
1. **Medan Pilihan Tarikh di Borang Pendaftaran (`bulk_registration.php`):**
   * Tambah medan: **"Tarikh Simpanan (Storage Date)"** (Lalai: Hari Ini).
   * **Had Ketat 3 Hari Kebelakang**: Pengguna hanya dibenarkan memilih tarikh antara **hari ini sehingga maksimum 3 hari lepas sahaja** (`min="<?= date('Y-m-d', strtotime('-3 days')) ?>"` dan `max="<?= date('Y-m-d') ?>"`). Sebarang tarikh lebih lampau akan disekat oleh validasi frontend dan backend.
2. **Penyelarasan Kotak Dinamik:**
   * Sistem memuatkan senarai kotak yang sepadan dengan hari minggu bagi tarikh yang dipilih (`w = date('w', strtotime($date))`).
3. **Pengiraan Umur Tepat:**
   * Nilai `created_at` disimpan mengikut tarikh simpanan pilihan pengguna.
4. **Audit Trail:**
   * Catat dalam lajur `remark` spesimen:  
     `"Backdated entry: Stored on DD-MM-YYYY, registered by [user] on DD-MM-YYYY"`.

---

# BAHAGIAN 2: PERMINTAAN & PENYELARASAN OLEH PENTADBIR (REQUEST BY ME - MALIK MANAN)

Bahagian ini mendokumentasikan semua permintaan penambahbaikan teknikal, penyelarasan antaramuka (UI/UX), pembersihan kod, kawalan sesi, dan penyelarasan gaya yang dipohon secara langsung oleh Malik Manan. **Semua tugasan di bawah telah berjaya disiapkan dan di-push ke GitHub.**

## 2.1 Ringkasan Status Permintaan Pentadbir (RequestByMe Overview)

| No. RBM | Tajuk Permintaan & Penambahbaikan | Modul / Fail Terlibat | Status Pelaksanaan | No. Commit Git |
| :--- | :--- | :--- | :--- | :--- |
| **RBM-01** | Auto-Redirect & Pembersihan Butang Manual Logout | `app/Views/forms/logout.php` | **SELESAI** | `32c8936` |
| **RBM-02** | Penyelarasan Susunan Paparan `all_list` (Status & Tarikh) | `SpecimenController.php`, `all_list.php` | **SELESAI** | `2051d81` |
| **RBM-03** | Pengendalian Session Expiration (Senario A, B, C) & Sekatan Laluan Admin | `RoleFilter.php`, `SessionController.php`, `session.php`, `Routes.php` | **SELESAI** | `6d2c621`, `4e5da59` |
| **RBM-04** | Pembuangan Skrip Usang (`upload.bat`, `pull.bat`) & Konfigurasi Rclone Native | Root workspace, `%APPDATA%\rclone\rclone.conf` | **SELESAI** | `6d2c621` |
| **RBM-05** | Tooltip Bahasa Inggeris pada Butang Tindakan Senarai Pelupusan | `app/Views/forms/disposal_list.php` | **SELESAI** | `0f0c678` |
| **RBM-06** | Penggabungan 3 Jadual Pengguna Menjadi 1 Kad Bertab pada `/role` | `app/Views/tables/role.php` | **SELESAI** | `dac1757` |
| **RBM-07** | Pemulihan & Penyeragaman Navigasi Breadcrumbs Dashboard | `role.php`, `combined_report.php`, `admin/settings.php` | **SELESAI** | `e3a72fa`, `5263360` |
| **RBM-08** | Penyeragaman Antaramuka Jadual `/maintenance` Mengikut Gaya `/role` | `app/Views/tables/maintenance.php` | **SELESAI** | `574b81f` |
| **RBM-09** | Penyeragaman Antaramuka Jadual `/admin/settings` Mengikut Gaya `/role` | `app/Views/admin/settings.php` | **SELESAI** | `5f8e657` |
| **RBM-10** | Penyeragaman Antaramuka Jadual Laporan Terpusat (`/reports`) Mengikut Gaya `/role` | `app/Views/tables/combined_report.php` | **SELESAI** | `76d7b97` |
| **RBM-11** | Penyeragaman Ikon Sidebar FontAwesome Solid & Lencana Notifikasi Pengguna Baharu | `app/Views/layout/page_layout.php` | **SELESAI** | `c9d975a` |
| **RBM-12** | Penyelarasan Saiz Fon dan Gaya Pengepala (*Thead*) pada `/reports` | `app/Views/tables/combined_report.php` | **SELESAI** | `312f7c4` |
| **RBM-13** | Penyeragaman Format Tarikh 12-Jam & Pemotongan Domain Email pada `all_list` | `app/Controllers/SpecimenController.php` | **SELESAI** | `b995212` |

---

## 2.2 Perincian Lengkap Setiap Permintaan Pentadbir (RequestByMe Details)

### RBM-01: Auto-Redirect & Pembersihan Butang Manual Logout
* **Tujuan**: Memastikan staf makmal yang mendaftar keluar dialihkan secara automatik kembali ke portal rasmi iStaff UniSZA tanpa memerlukan klik tambahan.
* **Tindakan**:
  * Mengemaskini fail [logout.php](file:///c:/xampp/htdocs/idam/app/Views/forms/logout.php).
  * Memasang pemasa undur 5 saat JavaScript dengan bar kemajuan visual (*progress countdown*).
  * Membuang butang manual navigasi untuk antaramuka yang lebih bersih dan selamat.
* **Commit**: `32c8936`

### RBM-02: Penyelarasan Susunan Paparan `all_list`
* **Tujuan**: Meningkatkan ketepatan carian dan pengauditan spesimen aktif dalam jadual utama All Specimens.
* **Tindakan**:
  * Mengemaskini susunan lalai (*default sorting*) DataTables di [all_list.php](file:///c:/xampp/htdocs/idam/app/Views/tables/all_list.php) dan [SpecimenController.php](file:///c:/xampp/htdocs/idam/app/Controllers/SpecimenController.php).
  * Susunan mengutamakan status spesimen terlebih dahulu: **Active (1) > Inactive (0) > Disposed (2)**, diikuti susunan tarikh pendaftaran terkini (**`created_at DESC`**).
* **Commit**: `2051d81`

### RBM-03: Pengendalian Session Expiration (Senario A, B, C) & Sekatan Laluan Admin
* **Tujuan**: Mengukuhkan keselamatan sesi pengguna mengikut piawaian keselamatan sistem makmal perubatan.
* **Tindakan**:
  * **Senario A**: Memaparkan amaran SweetAlert apabila sesi pengguna tamat semasa melakukan tindakan interaktif.
  * **Senario B**: Auto-logout dan pelencongan lancar ke portal login iStaff apabila sesi tamat.
  * **Senario C**: Menghalang cubaan akses terus melalui bar alamat (*direct URL bypass*) ke halaman dalaman sistem jika tiada sesi aktif.
  * Mengemaskini `app/Filters/RoleFilter.php`, `app/Controllers/SessionController.php`, `app/Views/forms/session.php`.
  * Menambah perlindungan penapis `filter => 'role:3'` pada semua laluan `admin/settings` dalam `app/Config/Routes.php`.
* **Commit**: `6d2c621`, `4e5da59`

### RBM-04: Pembuangan Skrip Usang (`upload.bat`, `pull.bat`) & Konfigurasi Rclone Native
* **Tujuan**: Mengelakkan konflik penyegerakan kod dan menghapuskan skrip batch lapuk yang tidak selamat.
* **Tindakan**:
  * Memadam fail `upload.bat` dan `pull.bat` secara kekal daripada repositori (`git rm`).
  * Memasang dan mendaftar `C:\rclone\rclone.exe` ke dalam User PATH Windows.
  * Menyelaraskan fail konfigurasi `%APPDATA%\rclone\rclone.conf` dengan *remote* `idam_live`.
  * Mewujudkan protokol penyegerakan rasmi menggunakan perintah rclone satu baris yang selamat.
* **Commit**: `6d2c621`

### RBM-05: Tooltip Bahasa Inggeris pada Butang Tindakan Senarai Pelupusan
* **Tujuan**: Memberikan panduan jelas kepada juruteknologi makmal sebelum menekan butang tindakan kritikal pelupusan.
* **Tindakan**:
  * Mengemaskini [disposal_list.php](file:///c:/xampp/htdocs/idam/app/Views/forms/disposal_list.php).
  * Memasang Bootstrap 5 tooltips pada 4 butang utama: *Request Disposal*, *Toggle All Checkboxes*, *Dispose Selected*, dan *Dispose All in Queue*.
* **Commit**: `0f0c678`

### RBM-06: Penggabungan 3 Jadual Pengguna Menjadi 1 Kad Bertab pada `/role`
* **Tujuan**: Mengemaskan susun atur antaramuka pengurusan pengguna daripada 3 kad bertindih kepada 1 kad bersepadu yang moden dan kemas.
* **Tindakan**:
  * Mengemaskini [role.php](file:///c:/xampp/htdocs/idam/app/Views/tables/role.php).
  * Menggabungkan jadual *New Users*, *Active/Inactive Users*, dan *Rejected Users* ke dalam satu kad dengan 3 tab (*card-header-tabs*).
  * Memaparkan lencana nombor dinamik (*badge count*) bagi setiap kategori pengguna pada tab.
  * Menambah fungsi JavaScript `sessionStorage` bagi mengekalkan tab aktif semasa halaman dimuat semula (*refresh*).
  * Menyelesaikan isu kelebaran kolum terhimpit menggunakan `columns.adjust().responsive.recalc()`.
* **Commit**: `dac1757`

### RBM-07: Pemulihan Navigasi Breadcrumbs Dashboard
* **Tujuan**: Memastikan konsistensi penunjuk lokasi halaman (*breadcrumbs*) pada semua menu navigasi utama.
* **Tindakan**:
  * Menambah navigasi breadcrumb `Dashboard / Users` pada [role.php](file:///c:/xampp/htdocs/idam/app/Views/tables/role.php).
  * Menambah navigasi breadcrumb `Dashboard / Reports` pada [combined_report.php](file:///c:/xampp/htdocs/idam/app/Views/tables/combined_report.php).
  * Menambah navigasi breadcrumb `Dashboard / Settings` pada [admin/settings.php](file:///c:/xampp/htdocs/idam/app/Views/admin/settings.php).
* **Commit**: `e3a72fa`, `5263360`

### RBM-08: Penyeragaman Antaramuka Jadual `/maintenance` Mengikut Gaya `/role`
* **Tujuan**: Menyelaraskan paparan pengurusan storan dan kontena mengikut reka bentuk tab `/role` tanpa mengubah sebarang logik bisnes sedia ada.
* **Tindakan**:
  * Mengemaskini [maintenance.php](file:///c:/xampp/htdocs/idam/app/Views/tables/maintenance.php).
  * Menggabungkan 3 jadual berasingan (*Unit*, *Storage*, dan *Container*) ke dalam 1 kad bersepadu dengan 3 tab navigasi.
  * Menetapkan pengekalan tab melalui `sessionStorage` (`active_maintenance_tab`).
  * Mengekalkan semua fungsi CRUD, modal Bootstrap, pemicu AJAX dan logik pangkalan data sedia ada.
* **Commit**: `574b81f`

### RBM-09: Penyeragaman Antaramuka Jadual `/admin/settings` Mengikut Gaya `/role`
* **Tujuan**: Menyeragamkan halaman tetapan pentadbir sistem (*Maintenance Mode*) agar sepadan dengan tema kad moden.
* **Tindakan**:
  * Mengemaskini [admin/settings.php](file:///c:/xampp/htdocs/idam/app/Views/admin/settings.php).
  * Menerapkan reka bentuk kad dengan pengepala tab dan breadcrumb navigasi.
* **Commit**: `5f8e657`

### RBM-10: Penyeragaman Antaramuka Jadual Laporan Terpusat (`/reports`) Mengikut Gaya `/role`
* **Tujuan**: Menggabungkan 3 modul laporan berasingan kepada satu terminal laporan bersepadu.
* **Tindakan**:
  * Mengemaskini [combined_report.php](file:///c:/xampp/htdocs/idam/app/Views/tables/combined_report.php).
  * Menyatukan *Registration Statistics*, *Disposal Audit Logs*, dan *Cancelled Logs* ke dalam 1 kad dengan *card-header-tabs*.
  * Menyelaraskan fungsi eksport ke Excel, PDF rasmi dan cetakan (*print*).
  * Menyokong pengekalan tab melalui parameter URL (`?tab=...`) dan event `shown.bs.tab`.
* **Commit**: `76d7b97`

### RBM-11: Penyeragaman Ikon Sidebar FontAwesome Solid & Lencana Notifikasi Pengguna Baharu
* **Tujuan**: Memperbaiki estetika visual sidebar, menghapuskan ikon bertindih, dan memberikan amaran permohonan pengguna baharu secara masa nyata (*real-time*).
* **Tindakan**:
  * Mengemaskini [page_layout.php](file:///c:/xampp/htdocs/idam/app/Views/layout/page_layout.php).
  * Menyeragamkan semua ikon kepada variasi **`fa-solid`** (tiada lagi campuran `fa-regular`).
  * Menyelaraskan ikon mengikut konteks makmal:
    * Dashboard: `fa-gauge-high`
    * New Entry: `fa-vial-circle-check` (kekal dengan aksen hijau `text-success`)
    * List & Containers: `fa-vials`
    * Disposal Management: `fa-dumpster-fire`
    * Centralized Reports: `fa-chart-pie`
    * Users: `fa-user-gear`
    * Maintenance: `fa-boxes-stacked` (mengelakkan ikon spanar pendua)
    * Maintenance Mode (Admin): `fa-power-off` (ikon suis live)
  * Menambah lencana merah bernombor bulat (*badge count*) secara automatik pada menu **Users** sekiranya terdapat pendaftaran staf baharu yang belum diluluskan (`status = 0`).
  * Mengecualikan pewarnaan aksen ikon mengikut arahan khusus pengguna (kekal gaya neutral monokrom asal).
* **Commit**: `c9d975a`

### RBM-12: Penyelarasan Saiz Fon dan Gaya Pengepala (*Thead*) pada `/reports`
* **Tujuan**: Menghapuskan masalah perbezaan saiz teks yang terlalu kecil pada jadual laporan berbanding menu-menu lain dalam sistem.
* **Tindakan**:
  * Mengemaskini [combined_report.php](file:///c:/xampp/htdocs/idam/app/Views/tables/combined_report.php).
  * Menukar saiz fon jadual daripada `0.82rem` kepada saiz standard Bootstrap yang selesa dibaca (**`0.875rem` / 14px**).
  * Menyelaraskan fon kawalan DataTables (carian, dropdown bilangan rekod, maklumat pagination) dan medan tapisan (*filter*) kepada `0.875rem`.
  * Menyeragamkan pengepala (*thead*) bagi ketiga-tiga tab jadual kepada **`<thead class="table-light text-center">`** (selaras dengan gaya `/role` dan `/maintenance`) serta membuang *inline style* yang bertindih.
  * Melaraskan saiz teks lencana status (*badge*) kepada `0.8rem` supaya tidak kelihatan terlampau halus.
* **Commit**: `312f7c4`

### RBM-13: Penyeragaman Format Tarikh 12-Jam & Pemotongan Domain Email pada `all_list`
* **Tujuan**: Menjadikan paparan data pada jadual All Specimens lebih bersih, ringkas dan mudah dibaca oleh staf makmal.
* **Tindakan**:
  * Mengemaskini kaedah `allSpecimensAjax()` dalam [SpecimenController.php](file:///c:/xampp/htdocs/idam/app/Controllers/SpecimenController.php).
  * **Date Created**: Ditukar daripada format 24-jam tanpa penunjuk masa (`d-m-Y H:i`) kepada format 12-jam dengan indikator AM/PM:
    ```php
    date('d-m-Y h:i A', strtotime($s['created_at']))
    ```
    *Contoh Paparan*: `23-11-2026 11:57 PM`
  * **Created By**: Memotong domain emel (`@unisza.edu.my`) menggunakan pemisah `@` supaya hanya memaparkan ID/nama pengguna sahaja:
    ```php
    esc(explode('@', $s['created_by'])[0])
    ```
    *Contoh Paparan*: `malikmanan` (bukan lagi `malikmanan@unisza.edu.my`).
* **Commit**: `b995212`

---

## 2.3 Peraturan Tetap Operasi Pentadbir (Standing Deployment Rule)
Bagi memastikan integriti kod antara persekitaran tempatan (Localhost) dan pelayan langsung (Live Server), setiap penambahbaikan wajib mematuhi protokol dua langkah berikut:
1. **Langkah 1**: Setiap perubahan kod yang lengkap dan disahkan bebas ralat sintaks (`php -l`) **mesti di-commit dan di-push ke GitHub** terlebih dahulu:
   ```powershell
   git add <fail-terlibat>
   git commit -m "<mesej komit berstruktur>"
   git push origin main
   ```
2. **Langkah 2**: Memaparkan kod perintah **rclone copy** ke Live Server pada penghujung setiap maklum balas bagi memudahkan penyegerakan terus ke pelayan produksi:
   ```powershell
   C:\rclone\rclone.exe copy "c:\xampp\htdocs\idam" "idam_live:/var/www/idam" --exclude ".git/**" --exclude ".env" --exclude "writable/**" --exclude "vendor/**" --exclude "rclone.conf" --update --progress
   ```
