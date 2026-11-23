# DOKUMEN PERMINTAAN PERUBAHAN SISTEM (CHANGE REQUEST BY USER)
**Sistem:** iDAM (Integrated Disposal & Archive Management) - HoSZA UniSZA  
**Tarikh Dikemaskini:** 23 November 2026  
**Status:** DOKUMENTASI / TERTANGGUH (Pending Confirmation Before Execution)  

---

## 1. RINGKASAN PERMINTAAN PERUBAHAN AKTIF (ACTIVE CR OVERVIEW)

| No. | Tajuk Permintaan | Modul Terlibat | Status / Tindakan |
| :--- | :--- | :--- | :--- |
| **CR-02** | Butang Pilihan Saiz Grid Slot: **100, 70, atau 50 Slots** pada New Entry | New Entry (`bulk_registration`), Controller (`saveBulk`), All List | Belum Dilaksanakan / Tertangguh |
| **CR-05** | Profil Tempoh Custom (**Custom Days Retention**) | DB Schema, Maintenance, Specimen, Disposal Queue, Dashboard, Colors | Dalam Perancangan / Disimpan untuk Semakan |
| **CR-06** | Pilihan Tarikh Simpanan (**Backdated Sample Registration**) | New Entry (`bulk_registration.php`), Controller (`SpecimenController`) | Dalam Perancangan / Disimpan untuk Semakan |

> *Nota: CR-01 (Chiller 14 Days), CR-03 (Dispose All in Queue), dan CR-04 (Penapisan Kontena/Storan Inactive) telah berjaya diselesaikan dan dipindahkan ke dalam rekod commit versi produksi.*

---

## 2. PERINCIAN CR-02: PILIHAN SAIZ GRID (100, 70, 50 SLOTS) PADA NEW ENTRY

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

## 3. PERINCIAN CR-05: PROFIL TEMPOH CUSTOM (CUSTOM DAYS RETENTION)

### A. Latar Belakang & Masalah Semasa
* Pada masa ini, sistem iDAM mengikat (*hardcode*) kitaran hayat spesimen kepada 4 kategori piawai:
  * `duration_id = 1`: Chiller (7 days)
  * `duration_id = 4`: Chiller (14 days)
  * `duration_id = 2`: Freezer (14 days)
  * `duration_id = 3`: Freezer (21 days)
* Namun, terdapat sampel makmal khas (seperti sampel penyelidikan, rujukan, atau pemantauan klinikal berpanjangan) yang tidak mengikut kitaran 7, 14, atau 21 hari. Tempohnya boleh berubah mengikut keperluan pengguna (cth: 2 hari, 45 hari, 98 hari, atau tiada tarikh tamat automatik).

### B. Prinsip Reka Bentuk
1. **Kekalkan Kategori Sedia Ada (7, 14, 21 Hari):**
   * Aliran 90%–95% sampel rutin harian tidak diusik bagi mengekalkan kestabilan sistem.
2. **Tambah Kategori Ke-5 (`duration_id = 5: Custom Days`):**
   * Storan dan kontena khas dicipta di modul Maintenance dengan profil ini.
   * Kotak Custom Days hanya menampung sampel yang mempunyai tempoh khas.
3. **Penetapan Tarikh Luput di Peringkat Pendaftaran (New Entry) Menggunakan Tarikh Kalendar (Calendar Date Picker):**
   * Kaedah yang dipilih dan ditetapkan untuk CR-05 adalah **Pilihan 2: Tarikh Kalendar (Calendar Date Picker)**.
   * Apabila pengguna memilih kotak berprofil **Custom Days**:
     * Satu medan input kalendar (**Target Disposal Date / Tarikh Luput Simpanan**) akan muncul secara automatik.
     * Juruteknologi makmal memilih tarikh sasaran pelupusan secara terus pada kalendar (*datepicker*).
     * **Validasi Kalendar**: Tarikh luput mestilah bermula dari hari semasa ke atas (`min="<?= date('Y-m-d') ?>"`). Sekiranya digabungkan dengan pendaftaran *backdated*, tarikh simpanan asal adalah terhad kepada **maksimum 3 hari kebelakang sahaja** (`-3 days`) untuk mengelakkan ralat kesilapan pengguna.
     * **Pengiraan Hari Automatik**: Di bawah kalendar, sistem secara pintar mengira dan memaparkan teks dinamik:  
       *Contoh: "Tarikh dipilih: 31/12/2026 (Bersamaan 38 hari tempoh simpanan)"*.
     * Nilai tarikh ini disimpan terus ke dalam lajur `expiry_date` jadual `specimens`.

### C. Aliran Kerja di Modul Pelupusan (Disposal List)
* Diwujudkan **Tab Ke-4: Custom Queue** (`datatableCustom`) di `app/Views/forms/disposal_list.php`.
* Sampel automatik disenaraikan dalam giliran apabila `Tarikh Semasa >= expiry_date`.
* Paparan lajur khas: *Tarikh Daftar*, *Tempoh Sasaran (cth: 98 Hari)*, *Umur Semasa (Days Old)*, dan *Status Kematangan (Matang / Belum Matang)*.

### D. Perubahan Modul & Fail Terlibat
1. **Pangkalan Data (`specimens`):**
   * Tambah lajur: `expiry_date DATETIME NULL` atau `custom_days INT NULL`.
2. **`app/Controllers/MaintenanceController.php` & `app/Views/tables/maintenance.php`:**
   * Benarkan `duration_id = 5` (Custom Days) untuk storan/kontena (boleh berjenis Chiller, Freezer, atau Room Temp).
3. **`app/Controllers/SpecimenController.php` & `app/Views/forms/bulk_registration.php`:**
   * Tambah input pilihan hari/tarikh luput apabila kotak berprofil `duration_id = 5` (Custom Days).
   * Simpan `expiry_date` semasa transaksi `saveBulk()`.
4. **`app/Views/forms/disposal_list.php` & `app/Controllers/SpecimenController.php`:**
   * Tambah Tab 4 (*Custom Queue*) berserta kaedah AJAX untuk memuatkan sampel custom days yang matang.
5. **`app/Controllers/DashboardController.php` & `app/Views/dashboard.php`:**
   * Tambah bar `Custom` dalam carta Highcharts *Ready to Dispose*.
6. **`app/Views/tables/all_list.php`:**
   * Tambah warna tiub SVG khas (contoh: Ungu `#8b5cf6`).

---

## 4. PERINCIAN CR-06: PILIHAN TARIKH SIMPANAN BAGI SAMPEL BACKDATED

### A. Latar Belakang & Masalah Semasa
* Dalam operasi harian, sampel makmal yang diambil/disimpan pada hari Jumaat atau syif malam hujung minggu kadangkala hanya sempat diimbas ke dalam sistem pada hari Isnin.
* Pada masa ini, iDAM mempunyai kekangan berikut:
  1. Pilihan kotak dikunci ketat kepada hari semasa (`container_day === date('w')`). Pengguna pada hari Isnin tidak boleh memilih kotak hari Jumaat.
  2. Medan `created_at` dijana automatik mengikut tarikh semasa komputer (`NOW()`). Jika sampel Jumaat didaftarkan pada hari Isnin, umur sampel bermula dari 0 hari pada hari Isnin, menyebabkan jadual pelupusan lewat 3 hari daripada usia sebenar spesimen.

### B. Prinsip Reka Bentuk
1. **Medan Pilihan Tarikh di Borang Pendaftaran (`bulk_registration.php`):**
   * Tambah medan: **"Tarikh Simpanan (Storage Date)"** (Nilai lalai: *Hari Ini*).
   * **Had Ketat 3 Hari Kebelakang**: Pengguna hanya dibenarkan memilih tarikh antara **hari ini sehingga maksimum 3 hari lepas sahaja** bagi mengelakkan kesilapan staf:
     * `min="<?= date('Y-m-d', strtotime('-3 days')) ?>"`
     * `max="<?= date('Y-m-d') ?>"`
   * Sebarang tarikh melebihi 3 hari kebelakang atau tarikh masa hadapan akan dikunci pada kalendar dan ditolak oleh validasi backend.
2. **Penyelarasan Kotak Dinamik:**
   * Apabila tarikh lampau (dalam had 3 hari) dipilih, sistem mengira hari dalam minggu bagi tarikh tersebut (`w = date('w', strtotime($date))`).
   * Sistem memuatkan senarai kotak yang sepadan dengan hari tarikh simpanan tersebut (cth: memuatkan kotak Jumaat jika memilih 3 hari lepas pada hari Isnin).
3. **Pengiraan Umur & Pelupusan Yang Tepat:**
   * Nilai `created_at` spesimen disimpan mengikut tarikh simpanan pilihan pengguna (`$storageDate . ' ' . date('H:i:s')`).
   * Umur spesimen (`age_days`) dan ambang pelupusan automatik dikira dari tarikh simpanan sebenar.

### C. Kawalan Keselamatan & Audit (Safeguards)
1. **Had Tempoh Backdate (Maksimum 3 Hari Kebelakang Sahaja):**
   * Dihadkan secara ketat kepada **maksimum 3 hari ke belakang sahaja** (`strtotime('-3 days')`). Ini mencukupi untuk menampung jurang kelewatan hujung minggu (Jumaat ke Isnin) di samping menghalang ralat kesilapan pengguna memilih tarikh yang terlalu lampau.
2. **Audit Trail:**
   * Catat dalam lajur `remark` spesimen:  
     `"Backdated entry: Stored on DD-MM-YYYY, registered by [user] on DD-MM-YYYY"`.

### D. Perubahan Modul & Fail Terlibat
1. **`app/Views/forms/bulk_registration.php`:**
   * Tambah input `storage_date` jenis datepicker dengan atribut `max`.
   * Kemaskini pemicu AJAX pemilihan kotak untuk menghantar parameter `storage_date`.
2. **`app/Controllers/SpecimenController.php`:**
   * Fungsi `getContainerNumbers()` & `getTodayContainers()`: Terima parameter tarikh pilihan bagi menggantikan `date('w')` semasa.
   * Fungsi `saveBulk()`: Sahkan `container_day` sepadan dengan tarikh pilihan pengguna dan simpan `created_at` mengikut tarikh berkenaan.

---

## 5. LANGKAH TINDAKAN APABILA PENGGUNA BERSEDIA (EXECUTION PLAN)

Apabila anda telah bersedia untuk melaksanakan mana-mana perubahan ini:
1. Nyatakan kepada pembantu AI: *"Sila laksanakan perubahan mengikut dokumen ChangeRequestByUser (nyatakan no. CR)"*.
2. Pembantu AI akan:
   - Mengemaskini kod mengikut matriks di atas.
   - Menguji aliran pendaftaran, pemetaan grid dan pengiraan tarikh lupus.
   - Melakukan semakan `git status`, `git commit` dan `git push` ke repositori rasmi.
