# SMART-CBT — iOS Exambro (Tahap 2)

Aplikasi Mobile Exam Browser native iOS/iPadOS (**Swift / UIKit / WKWebView / AVFoundation**) untuk platform **SMART-CBT**, dengan keamanan, kontrol screenshot, dan kontrak JavaScript Bridge (`window.SmartCbtNative`) yang 100% selaras dengan versi Android ([smart-cbt-mobile/android](../android)).

> **Catatan Build di Windows**: Apple mewajibkan **macOS + Xcode** untuk meng-compile binary iOS (`.app` / `.ipa`). Karena itu, proyek ini sudah dilengkapi:
> 1. **`SmartCBT.xcodeproj`** & **`project.yml`** untuk dibuka/build langsung di Mac/Xcode,
> 2. **GitHub Actions workflow** ([build-mobile.yml](../../.github/workflows/build-mobile.yml)) agar kamu bisa mendapatkan file **`.ipa`** otomatis di tab **Actions** GitHub tanpa harus punya Mac!

---

## Fitur Utama

1. **Terkunci ke Domain `https://ujian.smpaickkmb.sch.id`**:
   - Navigasi ke domain eksternal, tautan telepon/WhatsApp/browser luar, dan subresource tak dikenal diblokir di `WKNavigationDelegate`.
   - `UIRequiresFullScreen = YES` memaksa mode layar penuh (menonaktifkan Split View / Slide Over di iPad).
2. **Kunci Keluar Wajib Kode Akses Guru**:
   - Terintegrasi dengan Apple **Automatic Assessment Configuration (`AEAssessmentSession`)** (jika entitlement diaktifkan pada akun Apple Developer sekolah) + deteksi `willResignActive` saat berada di halaman ujian + tombol **Keluar** wajib **Kode Akses Guru** (jeda 5 detik setelah 3x salah).
3. **Kontrol Screenshot & Screen Recording Pintar**:
   - Memakai layer aman `UITextField.isSecureTextEntry` + observer `UIScreen.capturedDidChangeNotification` (Screen Recording / AirPlay Mirroring):
     - Di halaman soal (`/siswa/ujian/{id}/kerjakan`), token, dan portal: **screenshot & rekam layar diblokir**.
     - Di halaman **Bukti Selesai** (`/siswa/ujian/{id}/hasil`): **screenshot otomatis diizinkan** (badge hijau `Screenshot OK`) agar siswa bisa screenshot bukti selesai yang ada namanya.
4. **Kamera Internal `AVFoundation` + WebRTC Scan QR**:
   - Tombol **Kamera Langsung** di [work.blade.php](../../smart-cbt/resources/views/siswa/exams/work.blade.php) memanggil `window.SmartCbtNative.openEssayCamera(...)` -> membuka [`InAppCameraViewController`](SmartCBT/Camera/InAppCameraViewController.swift) langsung di dalam aplikasi, kompres JPEG maks `1600 px` quality `80%`, lalu mengirim kembali via `window.__onSmartCbtNativePhoto(...)`.
   - `WKUIDelegate` (`requestMediaCapturePermissionFor`) otomatis `grant` kamera untuk domain whitelist agar fitur **Scan QR Ujian** di dashboard siswa langsung jalan.
5. **White-Label Multi-Sekolah**:
   - Ubah langsung di perangkat via **Menu Operator Tersembunyi** (ketuk tulisan **`SMART-CBT`** **7x berturut-turut**, masukkan **Admin Setup PIN**, tersimpan aman di **iOS Keychain**), atau ubah build-time di [`Config/SchoolConfig.xcconfig`](Config/SchoolConfig.xcconfig).

---

## Konfigurasi Default

| Pengaturan | Nilai Default | Keterangan |
| :--- | :--- | :--- |
| **Nama Aplikasi** | `SMART-CBT` | Nama di home screen iPhone/iPad |
| **Bundle Identifier** | `id.sch.smpaickkmb.smartcbt` | Diubah di `Config/SchoolConfig.xcconfig` |
| **Domain Utama** | `https://ujian.smpaickkmb.sch.id` | Default terkunci |
| **Kode Akses Keluar Guru** | `123456` | Hash SHA-256 di `SchoolConfig.xcconfig` |
| **Admin Setup PIN** | `998877` | Ketuk 7x judul `SMART-CBT` untuk ganti domain |

---

## Cara Build & Install di iPhone / iPad

### Opsi 1 — Otomatis via GitHub Actions (Tanpa Perlu Punya Mac)
1. Push folder `smart-cbt-mobile/ios` & `.github/workflows/build-mobile.yml` ke GitHub.
2. Buka repo GitHub kamu -> tab **Actions** -> pilih workflow **Build SMART-CBT Mobile (iOS & Android)**.
3. Download artifact **`SMART-CBT-iOS`** yang berisi `SMART-CBT-iOS-v1.0-unsigned.ipa`.
4. Untuk memasang di iPhone/iPad testing:
   - Gunakan **Sideloadly** (Windows/Mac) atau **AltStore** dengan Apple ID gratis/berbayar untuk menandatangani & menginstall `.ipa` tersebut ke iPhone/iPad lewat kabel USB.
   - *(Opsional saat ujian)* Aktifkan **Guided Access / Akses Terpandu** di *Settings -> Accessibility -> Guided Access* iPhone/iPad (tekan tombol samping 3x sebelum ujian) untuk mengunci perangkat setara kiosk kalau belum memakai MDM / entitlement Assessment resmi Apple.

### Opsi 2 — Resmi untuk Seluruh Siswa via Mac & Apple Developer (TestFlight / MDM / Assessment Mode)
1. Buka [`SmartCBT.xcodeproj`](SmartCBT.xcodeproj) di **Xcode** pada macOS (atau jalankan `xcodegen generate` jika memodifikasi `project.yml`).
2. Pilih **Team** Apple Developer sekolah di tab *Signing & Capabilities*.
3. Untuk lockdown otomatis tanpa Guided Access manual: ajukan entitlement **`com.apple.developer.automatic-assessment-configuration`** di portal Apple Developer (program *Education / Assessment*) lalu aktifkan baris tersebut di [`SmartCBT/SmartCBT.entitlements`](SmartCBT/SmartCBT.entitlements).
4. Archive -> Distribute ke **TestFlight**, **App Store (Unlisted App untuk sekolah)**, atau deploy massal ke iPad sekolah lewat **MDM (Jamf / Mosyle / Intune)**.
