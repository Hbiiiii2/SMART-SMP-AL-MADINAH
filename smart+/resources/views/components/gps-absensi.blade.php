<div class="relative mb-6 overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
    <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Absensi Kehadiran (GPS & Kamera)</h3>
    
    @if($isLibur ?? false)
        <div class="rounded-lg bg-warning-50 p-4 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">
            Hari ini adalah hari libur. Anda tidak dapat melakukan absensi.
        </div>
    @elseif($allowedLocations->isEmpty())
        <div class="rounded-lg bg-error-50 p-4 text-error-700 dark:bg-error-500/10 dark:text-error-400">
            Anda belum memiliki izin absensi di lokasi mana pun. Hubungi Super Admin.
        </div>
    @else
        <div id="permission-overlay" class="absolute inset-0 z-50 flex flex-col items-center justify-center rounded-2xl bg-white/80 backdrop-blur-sm dark:bg-gray-900/80 p-6 text-center">
            <div class="mb-4 rounded-full bg-brand-50 p-4 dark:bg-brand-500/10">
                <svg class="h-10 w-10 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                </svg>
            </div>
            <h4 class="mb-2 text-lg font-bold text-gray-800 dark:text-white/90">Izin Akses Lokasi & Kamera</h4>
            <p class="mb-6 text-sm text-gray-500 dark:text-gray-400 max-w-sm">Untuk melakukan absensi zonasi, sistem membutuhkan izin akses lokasi GPS dan Kamera. Klik tombol di bawah untuk memulai.</p>
            <button type="button" id="btn-start-permissions" class="rounded-xl bg-brand-500 px-8 py-3 font-semibold text-white shadow-lg transition hover:bg-brand-600">
                Mulai Absensi
            </button>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 relative z-10">
            <!-- Map Section -->
            <div class="flex flex-col gap-3 relative">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Lokasi Anda</span>
                    <button type="button" id="btn-refresh-location" class="text-sm text-brand-500 hover:text-brand-600">Perbarui Lokasi</button>
                </div>
                
                <!-- Map Container -->
                <div id="map" class="h-64 w-full rounded-xl border border-gray-200 dark:border-gray-700 z-10"></div>
                <p id="location-status" class="text-sm text-gray-500 dark:text-gray-400">Menunggu izin akses...</p>
            </div>

            <!-- Camera Section -->
            <div class="flex flex-col gap-3">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Foto Live (Wajib)</span>
                <div id="camera-error-msg" class="text-sm text-error-600 dark:text-error-400 empty:hidden"></div>
                <div class="relative flex h-64 w-full items-center justify-center overflow-hidden rounded-xl border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50">
                    <video id="webcam" autoplay playsinline class="h-full w-full object-cover"></video>
                    <canvas id="canvas" class="hidden h-full w-full object-cover"></canvas>
                    <div id="timestamp-overlay" class="absolute bottom-2 right-2 rounded bg-black/50 px-2 py-1 text-xs text-white"></div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="btn-capture" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">Ambil Ulang Foto</button>
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            @php
                $sudahCheckin = false;
                $sudahCheckout = false;
                if (isset($absensiStatus) && $absensiStatus) {
                    $sudahCheckin = $absensiStatus['sudah_check_in'] ?? false;
                    $sudahCheckout = $absensiStatus['sudah_check_out'] ?? false;
                }
            @endphp
            
            @if(!$sudahCheckin)
                <button type="button" id="btn-checkin" class="rounded-lg bg-success-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-success-600 focus:ring-4 focus:ring-success-500/20" disabled>Check In</button>
            @endif
            
            @if($sudahCheckin && !$sudahCheckout)
                <button type="button" id="btn-checkout" class="rounded-lg bg-warning-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-warning-600 focus:ring-4 focus:ring-warning-500/20" disabled>Check Out</button>
            @endif
            
            @if($sudahCheckin && $sudahCheckout)
                <div class="rounded-lg bg-success-50 px-6 py-2.5 text-sm font-medium text-success-700 dark:bg-success-500/10 dark:text-success-400">
                    Absensi hari ini selesai
                </div>
            @endif
        </div>
    @endif
</div>

<!-- CSS for Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

<!-- JS for Leaflet & WebRTC -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        try {
            if (!document.getElementById('map')) return;

            const allowedLocations = @json($allowedLocations);
        
        let map = L.map('map').setView([-6.2088, 106.8456], 13); // Default to Jakarta
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        let userMarker = null;
        let userLat = null;
        let userLng = null;
        let photoData = null;

        // Draw allowed locations circles
        allowedLocations.forEach(loc => {
            L.circle([loc.latitude, loc.longitude], {
                color: 'green',
                fillColor: '#22c55e',
                fillOpacity: 0.2,
                radius: loc.radius
            }).addTo(map).bindPopup('Lokasi Diizinkan: ' + loc.nama);
        });

        const locationStatus = document.getElementById('location-status');
        const btnCheckin = document.getElementById('btn-checkin');
        const btnCheckout = document.getElementById('btn-checkout');

        function updateLocation() {
            locationStatus.textContent = 'Mencari lokasi...';
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        userLat = position.coords.latitude;
                        userLng = position.coords.longitude;
                        
                        if (userMarker) {
                            userMarker.setLatLng([userLat, userLng]);
                        } else {
                            userMarker = L.marker([userLat, userLng]).addTo(map).bindPopup("Lokasi Anda").openPopup();
                        }
                        
                        map.setView([userLat, userLng], 16);
                        
                        // Check if within any allowed radius
                        let isWithinRadius = false;
                        allowedLocations.forEach(loc => {
                            let dist = map.distance([userLat, userLng], [loc.latitude, loc.longitude]);
                            if (dist <= loc.radius) isWithinRadius = true;
                        });

                        if (isWithinRadius) {
                            locationStatus.innerHTML = '<span class="text-success-600">Lokasi valid (di dalam zona).</span>';
                            checkEnableButtons();
                        } else {
                            locationStatus.innerHTML = '<span class="text-error-600">Anda berada di luar zona absensi.</span>';
                            btnCheckin.disabled = true;
                            btnCheckout.disabled = true;
                        }
                    },
                    function(error) {
                        let errMsg = 'Gagal mendapatkan lokasi GPS.';
                        if (error.code === 1) {
                            errMsg = 'Izin lokasi ditolak oleh browser. Silakan klik ikon gembok di sebelah URL bar dan izinkan akses Lokasi (Location).';
                            alert(errMsg);
                        } else if (error.code === 2) {
                            errMsg = 'Sinyal GPS tidak ditemukan.';
                        } else if (error.code === 3) {
                            errMsg = 'Waktu permintaan lokasi habis (timeout).';
                        }
                        locationStatus.innerHTML = '<span class="text-error-600">' + errMsg + '</span>';
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                );
            } else {
                locationStatus.textContent = 'Geolocation tidak didukung oleh browser ini.';
            }
        }

        document.getElementById('btn-refresh-location')?.addEventListener('click', updateLocation);

        // Webcam Setup
        const video = document.getElementById('webcam');
        const canvas = document.getElementById('canvas');
        const timestampOverlay = document.getElementById('timestamp-overlay');
        const btnCapture = document.getElementById('btn-capture');
        const btnStartPermissions = document.getElementById('btn-start-permissions');
        const permissionOverlay = document.getElementById('permission-overlay');

        // Update timestamp every second
        setInterval(() => {
            const now = new Date();
            timestampOverlay.textContent = now.toLocaleString('id-ID');
        }, 1000);

        const errorContainer = document.getElementById('camera-error-msg');

        function startWebcam() {
            if (!window.isSecureContext) {
                if (errorContainer) errorContainer.innerHTML = "<b>Akses Ditolak:</b> Fitur Kamera dan GPS mewajibkan penggunaan koneksi HTTPS atau localhost. Silakan akses lewat <code>http://localhost:8000</code>.";
                alert("Peringatan: Kamera dan GPS membutuhkan koneksi aman (HTTPS atau localhost).");
                return;
            }

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                if (errorContainer) errorContainer.innerHTML = "<b>Error:</b> Browser ini tidak mendukung akses kamera (WebRTC tidak tersedia).";
                alert("Browser ini tidak mendukung akses kamera.");
                return;
            }

            // Gunakan constraint yang lebih longgar agar tidak diblokir HP tertentu
            const constraints = { 
                video: { facingMode: { ideal: "user" } }, 
                audio: false 
            };

            navigator.mediaDevices.getUserMedia(constraints)
                .then(stream => {
                    video.srcObject = stream;
                    video.classList.remove('hidden');
                    canvas.classList.add('hidden');
                    photoData = null;
                    if (errorContainer) errorContainer.innerHTML = "";
                    checkEnableButtons();
                    // Trigger GPS after camera allowed
                    updateLocation();
                })
                .catch(err => {
                    console.error("Webcam error:", err);
                    if (errorContainer) {
                        if (err.name === 'NotAllowedError') {
                            errorContainer.innerHTML = "<b>Izin Kamera Ditolak:</b> Silakan klik ikon Gembok di ujung URL bar, ubah Camera menjadi 'Allow', lalu refresh halaman.";
                        } else if (err.name === 'NotFoundError') {
                            errorContainer.innerHTML = "<b>Kamera Tidak Ditemukan:</b> Perangkat Anda tidak memiliki kamera atau kamera sedang digunakan aplikasi lain.";
                        } else {
                            errorContainer.innerHTML = "<b>Gagal Akses Kamera:</b> " + err.message;
                        }
                    }
                    alert("Gagal mengakses kamera. Silakan cek pesan error di layar.");
                    // Trigger GPS even if camera failed
                    updateLocation();
                });
        }

        if (btnStartPermissions) {
            btnStartPermissions.addEventListener('click', function() {
                permissionOverlay.style.display = 'none';
                // Trigger camera first
                startWebcam();
            });
        }

        btnCapture.addEventListener('click', function() {
            if (photoData) {
                // Already captured, retake
                startWebcam();
                btnCapture.textContent = 'Ambil Foto';
            } else {
                // Capture
                const context = canvas.getContext('2d');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                
                // Draw video
                context.drawImage(video, 0, 0, canvas.width, canvas.height);
                
                // Draw timestamp
                context.fillStyle = "rgba(0, 0, 0, 0.5)";
                context.fillRect(canvas.width - 250, canvas.height - 40, 240, 30);
                context.font = "16px Arial";
                context.fillStyle = "white";
                context.fillText(timestampOverlay.textContent, canvas.width - 240, canvas.height - 20);

                photoData = canvas.toDataURL('image/jpeg');
                
                video.classList.add('hidden');
                canvas.classList.remove('hidden');
                btnCapture.textContent = 'Ambil Ulang Foto';
                
                if(video.srcObject) {
                    video.srcObject.getTracks().forEach(track => track.stop());
                }
                checkEnableButtons();
            }
        });

        function checkEnableButtons() {
            let canSubmit = false;
            if (userLat && userLng && photoData && locationStatus.textContent.includes('valid')) {
                canSubmit = true;
            }
            if (btnCheckin) btnCheckin.disabled = !canSubmit;
            if (btnCheckout) btnCheckout.disabled = !canSubmit;
        }

        function submitAttendance(type) {
            if (!userLat || !userLng || !photoData) return;
            
            const btn = type === 'in' ? btnCheckin : btnCheckout;
            const originalText = btn.textContent;
            btn.textContent = 'Memproses...';
            btn.disabled = true;

            fetch(`/api/gps/check-${type}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    latitude: userLat,
                    longitude: userLng,
                    foto: photoData
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                    btn.textContent = originalText;
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menghubungi server.');
                btn.textContent = originalText;
                btn.disabled = false;
            });
        }

        if (btnCheckin) btnCheckin.addEventListener('click', () => submitAttendance('in'));
        if (btnCheckout) btnCheckout.addEventListener('click', () => submitAttendance('out'));

        } catch (e) {
            console.error('GPS Component Error:', e);
            const overlay = document.getElementById('permission-overlay');
            if (overlay) {
                overlay.innerHTML = `<div class="bg-red-100 text-red-700 p-4 rounded-lg w-full max-w-md"><b>Fatal Error:</b> ${e.message}<br>Silakan laporkan masalah ini ke admin.</div>`;
            } else {
                alert("Fatal Error pada komponen GPS: " + e.message);
            }
        }
    });
</script>
