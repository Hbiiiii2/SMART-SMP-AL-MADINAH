# SMART-CBT — Android Exambro (Tahap 1)

Aplikasi Mobile Exam Browser native Android untuk platform **SMART-CBT** (`smart-cbt/`), dirancang khusus agar mode kunci ujian tetap aman sementara fitur **Kamera Jawaban Essay** dan **Scan QR Ujian** 100% tembus dan berjalan di dalam aplikasi.

---

## Ringkasan Fitur

1. **Terkunci ke Domain Ujian (`https://ujian.smpaickkmb.sch.id`)**:
   - Navigasi ke domain eksternal, pop-up browser luar, dan intent aplikasi lain diblokir.
2. **Kunci Keluar Wajib Kode Akses Guru**:
   - Mendukung Android *Screen Pinning / LockTask Mode*, memblokir tombol Back, serta mencegah mode *Split-Screen* / *Multi-Window*.
   - Siswa tidak dapat keluar dari aplikasi di halaman mana pun (termasuk halaman selesai ujian) sebelum memasukkan **Kode Akses Keluar Guru** (dilengkapi jeda keamanan 5 detik setelah 3x percobaan salah).
3. **Kontrol Screenshot Pintar (Route-Aware `FLAG_SECURE`)**:
   - Di halaman login, dashboard, token, maupun pengerjaan soal (`/siswa/ujian/{id}/kerjakan`), screenshot dan rekam layar **diblokir**.
   - Begitu siswa masuk ke halaman **Bukti Selesai Ujian** (`/siswa/ujian/{id}/hasil`), screenshot **otomatis diizinkan** (badge hijau `Screenshot OK` di toolbar atas) agar siswa dapat mengambil screenshot bukti selesai (Nama Peserta, NISN/NIS, Kelas, Waktu Mulai, Waktu Selesai).
4. **Kamera Internal In-App (`Camera2 + TextureView`) & WebRTC Auto-Grant**:
   - Pada soal essay di `work.blade.php`, tombol **Kamera Langsung** memanggil JS Bridge `window.SmartCbtNative.openEssayCamera(...)` untuk membuka kamera internal **langsung di dalam SMART-CBT** (tanpa membuka aplikasi kamera luar, tanpa keluar dari Kiosk/LockTask, dan berjalan mulus baik di **HTTPS** maupun **HTTP IP lokal**).
   - Otomatis mengompresi foto jawaban menjadi JPEG (sisi terpanjang maks `1600 px`, kualitas `80%`) dan mengembalikannya via callback `window.__onSmartCbtNativePhoto(...)` ke antrean upload SMART-CBT.
   - Otomatis menyetujui izin WebRTC kamera (`RESOURCE_VIDEO_CAPTURE`) untuk domain whitelist agar fitur **Scan QR Ujian** (`html5-qrcode`) berjalan lancar.
5. **White-Label Multi-Sekolah**:
   - Dapat dipakai untuk sekolah lain baik lewat **Menu Operator Tersembunyi** langsung di HP maupun rebuild dari file `school.properties`.

---

## Konfigurasi Default Build Ini

| Pengaturan | Nilai Default | Keterangan |
| :--- | :--- | :--- |
| **Nama Aplikasi** | `SMART-CBT` | Terlihat di launcher Android & toolbar |
| **Application ID** | `id.sch.smpaickkmbs.smartcbt` | Package ID unik di Android |
| **Domain Utama** | `https://ujian.smpaickkmb.sch.id` | Terkunci secara default |
| **Kode Akses Keluar Guru** | `123456` | Masukkan saat klik tombol **Keluar** (sebaiknya segera diganti sebelum ujian) |
| **Admin Setup PIN** | `998877` | PIN tersembunyi untuk mengubah domain/kode keluar dari dalam aplikasi |

---

## File APK Siap Install

- Signed Release APK: [`dist/SMART-CBT-v1.0.apk`](dist/SMART-CBT-v1.0.apk)

Cara memasang di HP Android siswa:
1. Kirim file `SMART-CBT-v1.0.apk` ke perangkat Android (Android 8.0 / API 26 ke atas).
2. Aktifkan izin **Install dari sumber tidak dikenal (Install unknown apps)** jika diminta.
3. Buka aplikasi **SMART-CBT**, izinkan akses **Kamera**, dan setujui dialog **Screen Pinning (Sematkan Aplikasi)** jika muncul di perangkat siswa.

---

## Cara Mengganti Domain / Kode Akses (Untuk Sekolah Lain atau Testing Lokal)

### Cara A — Langsung di Perangkat (Runtime Admin Settings)
1. Buka aplikasi **SMART-CBT**.
2. Ketuk teks judul **`SMART-CBT`** di pojok kiri atas toolbar **7 kali berturut-turut** dalam 3 detik.
3. Masukkan **Admin Setup PIN** (default: `998877`).
4. Ubah **URL Domain Server Ujian** (misalnya `http://192.168.1.100:8001` untuk testing lokal atau `https://ujian.sekolahbaru.sch.id`) serta **Kode Keluar Guru baru** (opsional), lalu klik **Simpan & Muat**.
5. Nilai tersimpan terenkripsi menggunakan **Android Keystore (`AES/GCM/NoPadding`)**. Klik **Reset Default** kapan saja untuk kembali ke `https://ujian.smpaickkmb.sch.id`.

### Cara B — Build APK Khusus Sekolah Baru (`school.properties`)
1. Edit file [`school.properties`](school.properties):
   ```properties
   APP_NAME=SMART-CBT Sekolah Baru
   APPLICATION_ID=id.sch.sekolahbaru.smartcbt
   DEFAULT_BASE_URL=https://ujian.sekolahbaru.sch.id
   ALLOWED_HOSTS=ujian.sekolahbaru.sch.id
   DEFAULT_EXIT_PIN=654321
   DEFAULT_ADMIN_PIN=112233
   ```
2. Jika aplikasi akan dipublikasikan resmi atau dikunci permanen, buat keystore rilis sendiri lalu isi `keystore.properties` (jangan gunakan kunci testing).
3. Jalankan build di folder `smart-cbt-mobile/android`:
   ```powershell
   $env:JAVA_HOME = "C:\Program Files\Android\Android Studio\jbr"
   .\gradlew.bat assembleRelease
   ```
4. Hasil APK berada di `app\build\outputs\apk\release\app-release.apk`.
