# DOKUMEN PERMINTAAN PERUBAHAN SISTEM (CHANGE REQUEST BY USER)
**Sistem:** iDAM (Integrated Disposal & Archive Management) - HoSZA UniSZA  
**Tarikh Dicatat:** 22 November 2026  
**Status:** DOKUMENTASI / TERTANGGUH (Pending Confirmation Before Execution)  

---

## 1. RINGKASAN PERMINTAAN PERUBAHAN (CR OVERVIEW)

| No. | Tajuk Permintaan | Modul Terlibat | Tahap Impak |
| :--- | :--- | :--- | :--- |
| **CR-01** | Penambahan Kategori Storan: **Chiller (14 days)** | Maintenance, Dashboard, Specimen, Disposal, Reports | Sederhana (Backend & Frontend) |
| **CR-02** | Butang Pilihan Saiz Grid Slot: **100, 70, atau 50 Slots** pada New Entry | New Entry (`bulk_registration`), Controller (`saveBulk`), All List | Ringan / Mudah Diasingkan |

---

## 2. PERINCIAN CR-01: PENAMBAHAN KATEGORI STORAN "CHILLER (14 DAYS)"

### A. Latar Belakang & Masalah Semasa
Pada masa ini, sistem iDAM mengikat (*hardcode*) kolum `duration_id` kepada 3 profil storan sahaja:
* `duration_id = 1` ➔ **Chiller (7 days)**
* `duration_id = 2` ➔ **Freezer (14 days)**
* `duration_id = 3` ➔ **Freezer (21 days)**

Sekiranya pengguna ingin menambah storan jenis **Chiller** dengan tempoh **14 hari**, sistem menolaknya (*validation error*) kerana:
1. `MaintenanceController` menyekat bahawa tempoh 14 hari wajib berjenis `Freezer`.
2. Antara muka pengguna (UI) mengunci jenis storan kepada `Freezer` secara automatik apabila memilih 14 hari.

### B. Pendekatan Teknikal Yang Disyorkan
Wujudkan nilai baharu iaitu **`duration_id = 4`** khusus untuk **Chiller (14 days)**.
* **Kelebihan**: Mengelakkan konflik dengan `duration_id = 2` (Freezer 14D) pada label carta dashboard, warna tiub makmal, dan senarai antrian pelupusan.

### C. Senarai Fail & Bahagian Kod Yang Perlu Diubah
1. **`app/Controllers/MaintenanceController.php`**
   * Baris 188–202 (`createStorage`):
     * Kemaskini `if (!in_array($duration_id, [1, 2, 3, 4]))`.
     * Benarkan `duration_id == 4` hanya untuk `Chiller`.
   * Baris 261–275 (`updateStorage`): Selaraskan validasi yang sama.
   * Baris 341 (`updateContainer`): Benarkan `duration_id = 4`.
2. **`app/Controllers/DashboardController.php`**
   * Baris 50: Tambah `$durationMap = [1 => 0, 2 => 0, 3 => 0, 4 => 0];`.
   * Baris 119–129: Tambah syarat tarikh luput 14 hari dalam query SQL `readyDispose`:
     `OR (c.duration_id = 4 AND specimens.created_at <= '... -13 days')`.
   * Tambah pemula array `$readyDispose[4] = 0`.
3. **`app/Controllers/SpecimenController.php`**
   * Baris 430 & 566: Tambah kod warna HEX bagi `duration_id = 4` dalam `$tubeColors` (contoh: `#00bcd4` / Cyan).
   * Baris 1084 (`getReadyToDispose`): Tambah `$thresholds = [1 => 6, 2 => 13, 3 => 20, 4 => 13]`.
4. **`app/Views/tables/maintenance.php`**
   * Baris 565–609 (Modal Tambah Storan): Tambah `<option value="4">Chiller (14 days)</option>`.
   * Baris 594–609 (JavaScript): Kemaskini fungsi `sync` agar `d === 1 || d === 4` menetapkan peranti kepada `Chiller`.
   * Baris 665–710 (Modal Edit Storan): Tambah option Chiller 14D dan selaraskan fungsi `syncType()`.
   * Baris 182 & 377 (Jadual & Edit Kontena): Tambah teks `4 => 'Chiller 14 days'`.
5. **`app/Views/dashboard.php`**
   * Baris 146–168 & 240–255 (Highcharts): Tambah indeks ke-4 bagi data siri dan kategori paksi X.
   * Baris 398–402: Tambah pemetaan popup modal: `4: '14-Day Retention Chiller'`.
6. **`app/Views/tables/all_list.php`**
   * Baris 6–10 & 97: Tambah pemetaan warna tiub dan badge legenda untuk Chiller 14D.
7. **`create_db.php`**
   * Tiada ubah skema SQL (kolum `TINYINT` sedia ada menyokong nilai 1-255). Tambah benih data (*seed data*) storan Chiller 14D jika perlu.

---

## 3. PERINCIAN CR-02: PILIHAN SAIZ GRID (100, 70, 50 SLOTS) PADA NEW ENTRY

### A. Latar Belakang & Masalah Semasa
* Pada menu **New Entry** (`specimen` ➔ `forms/bulk_registration.php`), bahagian **Specimen Matrix Slot Placement Grid (Auto-Populating)** telah dikunci secara kekal pada saiz **100 slot** (`$maxSlots = 100`).
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

### D. Kesan Terhadap `https://idam.unisza.edu.my/specimen/all_list`
* **Integriti & Kestabilan**: **TIDAK AKAN CRASH / ERROR.**
* **Tingkah Laku Paparan**:
  * Pada fail [all_list.php](file:///c:/xampp/htdocs/idam/app/Views/tables/all_list.php), kod sedia ada melukis 100 slot SVG bagi setiap kotak kontena.
  * Sekiranya kotak tersebut didaftarkan menggunakan grid 50, slot 1 hingga 50 yang ada sampel akan berwarna mengikut tiub spesimen, manakala slot 51 hingga 100 akan kekal kosong berwarna kelabu muda (`#e9ecef`).
  * Jika mahu paparan di `all_list` turut mengecil mengikut kotak (hanya lukis 5 baris bagi kotak 50), maklumat kapasiti kotak boleh disimpan ke dalam jadual `containers` pada masa hadapan.

### E. Senarai Fail Yang Perlu Diubah
1. **`app/Views/forms/bulk_registration.php`**
   * Tambah butang pilihan saiz grid (100 / 70 / 50) dan hidden input `grid_size`.
   * Kemaskini JavaScript untuk tapis `availableSlotsList` dan jalankan semula fungsi `processBulkAutoPopulationMapping()`.
2. **`app/Controllers/SpecimenController.php`**
   * Di dalam fungsi `saveBulk()`, tangkap `grid_size` (50, 70, atau 100) dan hadkan loop pencarian slot kosong `for ($slot = 1; $slot <= $gridSize; $slot++)`.

---

## 4. LANGKAH TINDAKAN APABILA PENGGUNA BERSEDIA (EXECUTION PLAN)

Apabila anda telah bersedia untuk melaksanakan perubahan ini:
1. Nyatakan kepada pembantu AI: *"Sila laksanakan perubahan mengikut dokumen ChangeRequestByUser"*.
2. Pembantu AI akan:
   - Mengemaskini kod mengikut matriks di atas.
   - Menguji aliran pendaftaran, pemetaan grid dan pengiraan tarikh lupus.
   - Melakukan semakan `git status`, `git commit` dan `git push` ke repositori rasmi.
