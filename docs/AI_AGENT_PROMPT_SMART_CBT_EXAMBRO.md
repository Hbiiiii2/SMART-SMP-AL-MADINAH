# AI Agent Master Prompt — SMART-CBT Mobile Exambro

Dokumen ini berisi **Prompt siap pakai** untuk diberikan ke AI Coding Agent (Antigravity / sesi baru) agar langsung memahami konteks repositori `smp-almadinah`, arsitektur backend Laravel `smart-cbt`, dan spesifikasi lengkap pembuatan aplikasi **SMART-CBT** (Tahap 1: Android, Tahap 2: iOS).

---

## Prompt Tahap 1 — Build Aplikasi Android `SMART-CBT` (Copy-Paste Langsung)

```markdown
Kamu adalah Senior Android & Mobile Security Engineer yang bertugas membangun **SMART-CBT (Tahap 1: Android Native Exambro)** untuk proyek monorepo `c:\laragon\www\smp-almadinah`.

### 1. Baca Dokumen PRD & Konteks Proyek Terlebih Dahulu
Sebelum menulis kode apa pun, baca dokumen berikut secara menyeluruh:
1. `docs/PRD_SMART_CBT_MOBILE_EXAMBRO.md` (spesifikasi lengkap fitur, keamanan, UI/UX, dan kontrak JS Bridge).
2. `smart-cbt/routes/web.php` (rute siswa: `/login`, `/siswa/dashboard`, `/siswa/ujian/{ujian}/kerjakan`, `/siswa/ujian/{ujian}/hasil`, `/siswa/ujian/{ujian}/soal/{question}/lampiran`).
3. `smart-cbt/resources/views/siswa/exams/work.blade.php` (halaman pengerjaan ujian, fungsi `openEmbeddedCamera`, `handlePhotoFilesSelected`, `renderPhotoGrid`).
4. `smart-cbt/resources/views/siswa/exams/result.blade.php` (halaman bukti selesai ujian yang menampilkan Nama Peserta, NISN/NIS, Kelas, Status Pengumpulan, Waktu Mulai, dan Waktu Selesai).
5. `smart-cbt/app/Http/Controllers/SiswaAnswerAttachmentController.php` & `smart-cbt/config/cbt.php` (validasi upload foto essay: `photo` JPEG/PNG/WEBP, max 5120 KB, max 4096x4096, max 3 foto per soal).

### 2. Kebutuhan Wajib (Non-Negotiable Requirements)
- **Nama Aplikasi**: `SMART-CBT`
- **Lokasi Kode**: Buat proyek Android Native (Kotlin, Gradle Kotlin DSL, AndroidX, CameraX, Material 3) di `smart-cbt-mobile/android/`.
- **Target Android**: minSdk 26 (Android 8.0), targetSdk 35 (Android 15).
- **Kunci Domain + Opsi Multi-Sekolah (White-Label)**:
  - Default terkunci mati pada domain `https://ujian.smpaickkmb.sch.id` (strict host whitelist, blokir semua navigasi ke domain luar & external intent).
  - Sediakan file konfigurasi build-time `school.properties` (untuk `APP_NAME`, `APPLICATION_ID`, `DEFAULT_BASE_URL`, `ALLOWED_HOSTS`, `EXIT_PIN_HASH` / `DEFAULT_EXIT_PIN`, `ADMIN_PIN_HASH` / `DEFAULT_ADMIN_PIN`, `ENABLE_RUNTIME_DOMAIN_SWITCH`) agar mudah dipakai ulang di sekolah lain.
  - Sediakan menu tersembunyi **Operator / Admin Settings** (dipicu tap 7x pada versi/logo + wajib memasukkan **Admin PIN** terpisah) untuk mengganti URL domain server dan Kode Keluar Guru secara runtime (simpan terenkripsi dengan `EncryptedSharedPreferences`).
- **Kunci Keluar Wajib Kode Akses Guru**:
  - Blokir tombol Back, Home, Recent Apps dengan `startLockTask()` (Screen Pinning) dan `OnBackPressedCallback`.
  - Siswa TIDAK BISA keluar aplikasi dari halaman mana pun (termasuk halaman `/hasil`) kecuali menekan tombol **Keluar** pada toolbar atas dan memasukkan **Kode Akses Keluar** yang diberikan guru/pengawas.
  - Terapkan rate-limit / cooldown 5 detik setelah 3x percobaan kode salah.
- **Kontrol Screenshot Pintar (Route-Aware `FLAG_SECURE`)**:
  - Aktifkan `WindowManager.LayoutParams.FLAG_SECURE` di seluruh halaman ujian (terutama `/siswa/ujian/{id}/kerjakan` dan `/token`) agar tidak bisa di-screenshot maupun direkam layarnya.
  - Otomatis **nonaktifkan `FLAG_SECURE`** hanya ketika URL WebView cocok dengan halaman bukti selesai `^/siswa/ujian/\d+/hasil` agar siswa dapat mengambil screenshot bukti selesai yang menampilkan nama & waktu pengerjaan.
  - Tampilkan indikator status di top bar (`Mode Terkunci` vs `Screenshot Diizinkan`).
- **Sistem Kamera Dual-Path (100% Tembus di Dalam Exambro — HTTP & HTTPS)**:
  1. **Native Internal Camera Bridge (`window.SmartCbtNative`)**:
     - Ekspos `@JavascriptInterface` `SmartCbtNative` ke WebView dengan method `openEssayCamera(questionId: String, replaceAttachmentId: String)` dan `getAppVersion(): String`.
     - Buka viewfinder kamera internal berbasis **CameraX** langsung di dalam Activity/Fragment yang sama (JANGAN meluncurkan intent app kamera eksternal agar tetap berada di dalam `LockTask`).
     - Sertakan fitur: kamera belakang default, toggle flash, tap-to-focus, preview foto (Ulangi / Gunakan Foto), kompresi JPEG otomatis (max sisi terpanjang 1600px, quality 80%).
     - Kirim hasil foto kembali ke WebView melalui callback JS `window.__onSmartCbtNativePhoto(questionId, base64Jpeg, fileName, replaceAttachmentId)`.
  2. **WebRTC `getUserMedia` Auto-Grant**:
     - Override `WebChromeClient.onPermissionRequest` untuk otomatis `grant` `RESOURCE_VIDEO_CAPTURE` khusus origin whitelist domain sekolah, sehingga Scan QR (`html5-qrcode` di `/siswa/dashboard`) dan modal kamera web juga berjalan mulus.
  3. **Integrasi Ringan Web (`smart-cbt/resources/views/siswa/exams/work.blade.php`)**:
     - Perbarui `openEmbeddedCamera()` agar memprioritaskan `window.SmartCbtNative.openEssayCamera(...)` bila tersedia, dan daftarkan handler `window.__onSmartCbtNativePhoto` serta `window.__onSmartCbtNativeCameraError` yang langsung meneruskan file JPEG ke `handlePhotoFilesSelected()` tanpa memecahkan kompatibilitas browser biasa.
- **Anti-Cheat Tambahan**:
  - `android:resizeableActivity="false"` & `android:supportsPictureInPicture="false"` + deteksi runtime `isInMultiWindowMode`.
  - Deteksi `onWindowFocusChanged` / `onUserLeaveHint` (overlay peringatan jika fokus hilang / floating app muncul).
  - Nonaktifkan long-click text selection / copy-paste di halaman ujian dan bersihkan clipboard saat masuk aplikasi.
  - Sertakan User-Agent suffix `SmartCBT-Exambro/1.0 (Android)` dan header `X-Smart-CBT-Client: android-exambro-1.0`.

### 3. Aturan Kerja & Batasan
- **JANGAN** menyentuh container database Docker (`smart-cbt-db`, `smart-redis`) ataupun volume Docker yang sedang berjalan di mesin lokal.
- **JANGAN** merusak fitur atau test Pest yang sudah ada di `smart-cbt/tests/`.
- Pastikan proyek Android benar-benar **terkompilasi sukses** (`./gradlew assembleDebug` dan `./gradlew assembleRelease` jika keystore/signing siap) menggunakan Android SDK & JDK yang tersedia di host.
- Setelah menyentuh `work.blade.php`, jalankan Pest test `EssayPhotoUploadTest.php` dan `ResultAccessTest.php` untuk memastikan tidak ada regresi.
- Buat juga dokumentasi singkat `smart-cbt-mobile/android/README.md` berisi cara mengganti domain untuk sekolah lain, cara mengganti Kode Akses Keluar / Admin PIN, dan cara build APK.

### 4. Output yang Diharapkan
1. Source code lengkap `smart-cbt-mobile/android/` yang terstruktur rapi (Clean Architecture ringan: `config/`, `security/`, `webview/`, `camera/`, `ui/`).
2. Integrasi JS Bridge di `smart-cbt/resources/views/siswa/exams/work.blade.php`.
3. File APK hasil build (`app-debug.apk` / `app-release.apk`) yang terverifikasi sukses dibuild tanpa error + panduan instalasi & uji coba di HP Android fisik.
```

---

## Prompt Tahap 2 — Build Aplikasi iOS `SMART-CBT` (Untuk Sesi Berikutnya Setelah Android Selesai)

```markdown
Kamu adalah Senior iOS & Mobile Security Engineer yang bertugas membangun **SMART-CBT (Tahap 2: iOS Native Exambro)** di `smart-cbt-mobile/ios/`, mengikuti standar yang sudah dibangun pada Tahap 1 Android.

### 1. Baca Konteks Terlebih Dahulu
1. `docs/PRD_SMART_CBT_MOBILE_EXAMBRO.md`
2. Implementasi Tahap 1 Android di `smart-cbt-mobile/android/` (untuk menyamakan UX, logika domain whitelist, dan kontrak JS Bridge).
3. `smart-cbt/resources/views/siswa/exams/work.blade.php` (kontrak `window.SmartCbtNative` dan callback `window.__onSmartCbtNativePhoto`).

### 2. Spesifikasi Wajib iOS (Swift / UIKit + WKWebView)
- **Nama Aplikasi**: `SMART-CBT`, min iOS/iPadOS 15.0.
- **White-Label Config**: `SchoolConfig.xcconfig` (default `https://ujian.smpaickkmb.sch.id`) + Hidden Admin Settings (Keychain storage) yang terkunci Admin PIN.
- **Assessment Lockdown**:
  - Implementasikan Apple **Automatic Assessment Configuration (`AEAssessmentSession`)** untuk mengunci perangkat selama ujian (dengan fallback deteksi `UIAccessibility.isGuidedAccessEnabled` jika entitlement Apple Assessment belum aktif saat development).
  - Exit aplikasi / akhiri sesi assessment **wajib** memasukkan **Kode Akses Keluar Guru**.
- **Kontrol Screenshot & Screen Recording Pintar**:
  - Pantau `UIScreen.capturedDidChangeNotification` dan `UIApplication.userDidTakeScreenshotNotification`.
  - Tutup layar WebView dengan shield overlay saat screen recording / AirPlay aktif pada halaman selain `/siswa/ujian/{id}/hasil`.
  - Pada `/siswa/ujian/{id}/hasil`, tampilkan badge `Screenshot Diizinkan` agar siswa dapat menyimpan bukti selesai.
- **Kamera Dual-Path**:
  - Implementasikan `WKScriptMessageHandler` + injected user script yang mengekspos `window.SmartCbtNative.openEssayCamera(questionId, replaceAttachmentId)` (100% kompatibel dengan `work.blade.php` tanpa perlu ubah web!).
  - Implementasikan internal `AVFoundation` camera view controller (kompresi JPEG max 1600px quality 80%) yang memanggil `window.__onSmartCbtNativePhoto(...)`.
  - Implementasikan `WKUIDelegate` (`requestMediaCapturePermissionFor`) auto-grant kamera untuk domain whitelist agar Scan QR (`html5-qrcode`) berjalan mulus.
- **Output**: Proyek Xcode (`smart-cbt-mobile/ios/`) yang rapi, panduan konfigurasi Apple Assessment Entitlement & signing TestFlight/App Store.
```
