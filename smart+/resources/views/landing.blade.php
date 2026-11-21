@php
    try {
        $sekolah = \App\Models\PengaturanSekolah::first();
        $namaSekolah = $sekolah->nama_sekolah ?? 'SMP Al-Madinah';
        $radiusAbsensi = $sekolah->radius_absensi_meter ?? 50;
    } catch (\Throwable $e) {
        $namaSekolah = 'SMP Al-Madinah';
        $radiusAbsensi = 50;
    }
    $waNumber = '6285122502293';
    $waText = urlencode('Halo SMART+ / Aybisa Studio, saya ingin menjadwalkan demo dan konsultasi implementasi sistem presensi SMART+ untuk sekolah kami.');
    $waUrl = "https://wa.me/{$waNumber}?text={$waText}";
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- SEO Meta Tags -->
    <title>SMART+ | Real-time Attendance &amp; Academic Management Platform</title>
    <meta name="description" content="SMART+ adalah platform presensi multi-metode (Kiosk RFID & GPS Geofencing), automated reporting, academic scheduling, dan integrasi CBT untuk sekolah modern. Produk resmi Aybisa Studio.">
    <meta name="keywords" content="SMART+, School Attendance System, RFID Card Kiosk, GPS Geofencing, Automated Reporting, E-Rapor, Academic Management, CBT Integration, Aybisa Studio">
    <meta name="author" content="Aybisa Studio">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph / Social Media -->
    <meta property="og:title" content="SMART+ | Real-time Attendance &amp; Academic Management Platform">
    <meta property="og:description" content="Platform presensi digital real-time (Kiosk RFID & GPS Radius), automated reporting, serta monitoring KBM terpadu untuk sekolah modern.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="SMART+ by Aybisa Studio">
    
    <!-- Google Fonts: Plus Jakarta Sans & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Self-Contained Micro-Animations -->
    <style>
        @keyframes floatSlow1 {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-8px) rotate(0.8deg); }
        }
        @keyframes floatSlow2 {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-10px) rotate(-0.8deg); }
        }
        @keyframes floatSlow3 {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(6px); }
        }
        @keyframes laserSweep {
            0% { top: 0%; opacity: 0; }
            15% { opacity: 1; }
            85% { opacity: 1; }
            100% { top: 100%; opacity: 0; }
        }
        @keyframes pulseRing {
            0% { transform: scale(0.95); opacity: 0.8; }
            50% { transform: scale(1.35); opacity: 0; }
            100% { transform: scale(0.95); opacity: 0; }
        }
        @keyframes marqueeScroll {
            0% { transform: translateX(0%); }
            100% { transform: translateX(-50%); }
        }
        @keyframes glowBorder {
            0%, 100% { border-color: rgba(22, 133, 232, 0.25); box-shadow: 0 0 15px rgba(22, 133, 232, 0.08); }
            50% { border-color: rgba(38, 155, 82, 0.45); box-shadow: 0 0 22px rgba(38, 155, 82, 0.16); }
        }

        .animate-float-1 { animation: floatSlow1 4.5s ease-in-out infinite; }
        .animate-float-2 { animation: floatSlow2 5.5s ease-in-out infinite; }
        .animate-float-3 { animation: floatSlow3 6s ease-in-out infinite; }
        .animate-laser-scan { animation: laserSweep 2.2s cubic-bezier(0.4, 0, 0.2, 1) infinite; }
        .animate-pulse-ring { animation: pulseRing 2s cubic-bezier(0.2, 0.8, 0.4, 1) infinite; }
        .animate-glow-border { animation: glowBorder 4s ease-in-out infinite; }
        
        .marquee-track {
            display: flex;
            width: max-content;
            animation: marqueeScroll 32s linear infinite;
        }
        .marquee-track:hover {
            animation-play-state: paused;
        }
    </style>
</head>
<body class="font-jakarta antialiased text-aybisa-navy bg-white selection:bg-aybisa-blue selection:text-white" 
      x-data="{ 
          mobileMenuOpen: false,
          activeTab: 'rfid',
          activeFaq: 1,
          simulatedTaps: 418,
          lastTappedName: 'Muhammad Rizky Pratama',
          lastTappedClass: 'Kelas 8-A',
          lastTappedTime: '06:45:12 WIB',
          currentTimeString: '',
          isScanning: false,
          tapSuccess: false,
          studentsList: [
              { name: 'Aisyah Putri Azzahra', class: 'Kelas 7-B' },
              { name: 'Fatih Al-Ghifari', class: 'Kelas 9-A' },
              { name: 'Zahra Nurul Fadilah', class: 'Kelas 8-C' },
              { name: 'Rayhan Maulana Akbar', class: 'Kelas 7-A' },
              { name: 'Nabila Syakira', class: 'Kelas 8-B' },
              { name: 'Ahmad Fauzi Ridwan', class: 'Kelas 9-B' },
              { name: 'Dewi Sekar Ayu', class: 'Kelas 8-A' }
          ],
          init() {
              this.updateClock();
              setInterval(() => { this.updateClock(); }, 1000);
              // Auto-cycle simulation every 3.8s so the website feels active & alive
              setInterval(() => {
                  this.triggerSimulatedTap();
              }, 3800);
          },
          updateClock() {
              const now = new Date();
              const pad = (n) => String(n).padStart(2, '0');
              this.currentTimeString = `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())} WIB`;
          },
          triggerSimulatedTap() {
              if (this.isScanning) return;
              this.isScanning = true;
              this.tapSuccess = false;
              
              const randomStudent = this.studentsList[Math.floor(Math.random() * this.studentsList.length)];
              
              setTimeout(() => {
                  this.isScanning = false;
                  this.tapSuccess = true;
                  this.simulatedTaps++;
                  this.lastTappedName = randomStudent.name;
                  this.lastTappedClass = randomStudent.class;
                  this.lastTappedTime = this.currentTimeString;
                  
                  setTimeout(() => { 
                      this.tapSuccess = false; 
                  }, 2400);
              }, 500);
          }
      }">

    <!-- Top Identity Bar -->
    <aside class="bg-aybisa-navy text-white text-xs py-2 px-4 border-b border-white/10 hidden sm:block">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-2">
                <!-- 5-Color Rainbow Indicator Dots -->
                <span class="inline-flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#1685E8]"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-[#F32635]"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-[#FFB81C]"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-[#269B52]"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-[#E83F83]"></span>
                </span>
                <span class="text-slate-300">
                    <strong class="text-white font-semibold">SMART+</strong> &bull; School Management Attendance &amp; Reporting Technologies
                </span>
                <span class="text-slate-500">|</span>
                <span class="text-slate-300 text-[11px]">Product of <span class="text-aybisa-blue-light font-semibold">Aybisa Studio</span></span>
            </div>
            <div class="flex items-center gap-4 text-slate-300">
                <span class="inline-flex items-center gap-1.5 text-[11px] bg-emerald-500/15 text-emerald-300 px-2.5 py-0.5 rounded-full border border-emerald-500/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-aybisa-green animate-ping"></span>
                    Live Engine: {{ $namaSekolah }}
                </span>
                <!-- WhatsApp Demo Link -->
                <a href="{{ $waUrl }}" target="_blank" class="text-emerald-400 font-bold hover:text-emerald-300 flex items-center gap-1.5 text-[11px] transition-colors">
                    <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.007c.107.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.173.086.275.072.376-.044.101-.116.433-.506.549-.68.116-.174.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.043.073.043.419-.101.825z"/></svg>
                    Live Demo (+62 851-2250-2293)
                </a>
            </div>
        </div>
    </aside>

    <!-- Header / Navbar -->
    <header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-slate-100 transition-all duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                
                <!-- Brand Logo: SMART+ by Aybisa Studio -->
                <a href="{{ route('landing') }}" class="flex items-center gap-3 group focus:outline-none">
                    <div class="relative flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-tr from-aybisa-blue to-aybisa-blue-light text-white font-black text-xl shadow-md shadow-aybisa-blue/20 group-hover:scale-105 transition-transform">
                        S
                        <span class="absolute -top-1 -right-1 w-3.5 h-3.5 rounded-full bg-aybisa-red border-2 border-white flex items-center justify-center text-[9px] font-bold text-white leading-none">+</span>
                    </div>
                    <div class="flex flex-col">
                        <div class="flex items-center gap-1.5">
                            <span class="text-xl font-extrabold tracking-tight text-aybisa-navy leading-none">SMART<span class="text-aybisa-blue">+</span></span>
                            <!-- 5-Color Mini Accent Strip -->
                            <span class="hidden md:inline-flex items-center gap-0.5 ml-1">
                                <span class="w-1 h-3 rounded-full bg-[#1685E8]"></span>
                                <span class="w-1 h-3 rounded-full bg-[#F32635]"></span>
                                <span class="w-1 h-3 rounded-full bg-[#FFB81C]"></span>
                                <span class="w-1 h-3 rounded-full bg-[#269B52]"></span>
                                <span class="w-1 h-3 rounded-full bg-[#E83F83]"></span>
                            </span>
                        </div>
                        <span class="text-[10px] font-semibold tracking-wider text-aybisa-gray uppercase mt-0.5">Product by Aybisa Studio</span>
                    </div>
                </a>

                <!-- Desktop Navigation Links -->
                <nav class="hidden lg:flex items-center gap-7">
                    <a href="#fitur" class="text-sm font-semibold text-aybisa-gray hover:text-aybisa-blue transition-colors">Core Features</a>
                    <a href="#demo-interaktif" class="text-sm font-semibold text-aybisa-gray hover:text-aybisa-blue transition-colors">Module Explorer</a>
                    <a href="#kiosk-demo" class="text-sm font-semibold text-aybisa-gray hover:text-aybisa-blue transition-colors">Kiosk Mode</a>
                    <a href="#role-portal" class="text-sm font-semibold text-aybisa-gray hover:text-aybisa-blue transition-colors">Multi-Role Portal</a>
                    <a href="#faq" class="text-sm font-semibold text-aybisa-gray hover:text-aybisa-blue transition-colors">FAQ</a>
                </nav>

                <!-- Action CTA Buttons -->
                <div class="hidden sm:flex items-center gap-3">
                    <!-- WhatsApp Demo Button -->
                    <a href="{{ $waUrl }}" target="_blank" class="inline-flex items-center gap-2 rounded-xl border border-emerald-300 bg-emerald-50 px-3.5 py-2.5 text-xs font-bold text-emerald-800 hover:bg-emerald-100 transition-all shadow-2xs group" title="Konsultasi & Live Demo via WhatsApp">
                        <svg class="w-4 h-4 fill-emerald-600 group-hover:scale-110 transition-transform" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.007c.107.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.173.086.275.072.376-.044.101-.116.433-.506.549-.68.116-.174.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.043.073.043.419-.101.825z"/></svg>
                        <span>Request Demo</span>
                    </a>

                    <!-- Kiosk Mode Link -->
                    <a href="{{ route('kiosk.index') }}" target="_blank" class="inline-flex items-center gap-2 rounded-xl border border-blue-200 bg-aybisa-pale px-3.5 py-2.5 text-xs font-bold text-aybisa-blue hover:bg-blue-100 transition-colors shadow-2xs" title="Buka Tampilan Terminal Kiosk Tap Siswa">
                        Kiosk Mode
                    </a>

                    <!-- Portal Login CTA -->
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl bg-aybisa-blue px-5 py-2.5 text-sm font-bold text-white shadow-md shadow-aybisa-blue/25 hover:bg-aybisa-blue-hover hover:shadow-lg hover:-translate-y-0.5 transition-all">
                        Sign In Portal
                        <svg class="ml-1.5 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                </div>

                <!-- Mobile Hamburger Button -->
                <div class="flex items-center gap-2 lg:hidden">
                    <a href="{{ $waUrl }}" target="_blank" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white">
                        Demo
                    </a>
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-lg bg-aybisa-blue px-3.5 py-2 text-xs font-bold text-white">
                        Sign In
                    </a>
                    <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="inline-flex items-center justify-center p-2 rounded-lg text-aybisa-gray hover:text-aybisa-navy hover:bg-slate-100 focus:outline-none" aria-label="Toggle Menu">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path :class="{'hidden': mobileMenuOpen, 'inline-flex': !mobileMenuOpen }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{'hidden': !mobileMenuOpen, 'inline-flex': mobileMenuOpen }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu Dropdown -->
        <div x-show="mobileMenuOpen" x-transition class="lg:hidden border-b border-slate-200 bg-white px-4 pt-3 pb-6 space-y-3">
            <div class="flex flex-col space-y-2">
                <a @click="mobileMenuOpen = false" href="#fitur" class="px-3 py-2 rounded-lg text-sm font-semibold text-aybisa-navy hover:bg-aybisa-pale">Core Features</a>
                <a @click="mobileMenuOpen = false" href="#demo-interaktif" class="px-3 py-2 rounded-lg text-sm font-semibold text-aybisa-navy hover:bg-aybisa-pale">Module Explorer</a>
                <a @click="mobileMenuOpen = false" href="#kiosk-demo" class="px-3 py-2 rounded-lg text-sm font-semibold text-aybisa-navy hover:bg-aybisa-pale">Kiosk Mode</a>
                <a @click="mobileMenuOpen = false" href="#role-portal" class="px-3 py-2 rounded-lg text-sm font-semibold text-aybisa-navy hover:bg-aybisa-pale">Multi-Role Portal</a>
                <a @click="mobileMenuOpen = false" href="#faq" class="px-3 py-2 rounded-lg text-sm font-semibold text-aybisa-navy hover:bg-aybisa-pale">FAQ</a>
            </div>
            <div class="pt-3 border-t border-slate-100 flex flex-col gap-2">
                <!-- WhatsApp Mobile Demo Button -->
                <a href="{{ $waUrl }}" target="_blank" class="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-300 bg-emerald-50 py-2.5 text-xs font-bold text-emerald-800">
                    <svg class="w-4 h-4 fill-emerald-600" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.007c.107.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.173.086.275.072.376-.044.101-.116.433-.506.549-.68.116-.174.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.043.073.043.419-.101.825z"/></svg>
                    Live Demo WhatsApp (+62 851-2250-2293)
                </a>
                <a href="{{ route('kiosk.index') }}" class="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-blue-200 bg-aybisa-pale py-2.5 text-xs font-bold text-aybisa-blue">
                    Buka Kiosk Mode
                </a>
                <a href="{{ route('login') }}" class="w-full inline-flex items-center justify-center rounded-xl bg-aybisa-blue py-2.5 text-xs font-bold text-white shadow-sm">
                    Sign In ke Portal
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        <!-- Hero Section with Micro-Animations -->
        <section class="relative pt-12 pb-16 sm:pt-20 sm:pb-24 lg:pt-24 lg:pb-28 overflow-hidden bg-gradient-to-b from-aybisa-pale/75 via-white to-white">
            
            <!-- Animated Background Glow Blobs -->
            <div class="absolute -top-24 -left-20 w-80 h-80 rounded-full bg-aybisa-blue/20 blur-3xl pointer-events-none animate-float-1"></div>
            <div class="absolute top-1/3 -right-24 w-96 h-96 rounded-full bg-aybisa-pink/15 blur-3xl pointer-events-none animate-float-2"></div>
            <div class="absolute -bottom-24 left-1/4 w-80 h-80 rounded-full bg-aybisa-yellow/15 blur-3xl pointer-events-none animate-float-3"></div>

            <!-- Subtle Grid Pattern -->
            <div class="absolute inset-0 -z-10 bg-[linear-gradient(to_right,#e2e8f025_1px,transparent_1px),linear-gradient(to_bottom,#e2e8f025_1px,transparent_1px)] bg-[size:3.5rem_3.5rem]"></div>
            
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="text-center max-w-4xl mx-auto">
                    
                    <!-- Product Badge Pill with Aybisa Identity Accent -->
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white border border-blue-200/90 shadow-sm text-xs font-bold text-aybisa-navy mb-6 hover:border-aybisa-blue transition-colors">
                        <span class="inline-flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-[#1685E8]"></span>
                            <span class="w-2 h-2 rounded-full bg-[#F32635]"></span>
                            <span class="w-2 h-2 rounded-full bg-[#FFB81C]"></span>
                            <span class="w-2 h-2 rounded-full bg-[#269B52]"></span>
                            <span class="w-2 h-2 rounded-full bg-[#E83F83]"></span>
                        </span>
                        <span class="text-aybisa-blue font-extrabold tracking-wide">SMART+ PLATFORM</span>
                        <span class="text-slate-300">&bull;</span>
                        <span class="text-aybisa-gray font-medium">Real-time Attendance &amp; Academic Management</span>
                    </div>

                    <!-- Main Headline -->
                    <h1 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-aybisa-navy leading-[1.14] mb-6">
                        Presensi Cepat Tanpa Antre, <br class="hidden sm:inline">
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-aybisa-blue via-aybisa-blue to-aybisa-blue-dark">
                            Automated Reporting Sekali Klik
                        </span>
                    </h1>

                    <!-- Aybisa Signature 5-Color Accent Bar Motif -->
                    <div class="flex items-center justify-center gap-1.5 mb-6">
                        <span class="h-1.5 w-10 rounded-full bg-[#1685E8]"></span>
                        <span class="h-1.5 w-5 rounded-full bg-[#F32635]"></span>
                        <span class="h-1.5 w-5 rounded-full bg-[#FFB81C]"></span>
                        <span class="h-1.5 w-5 rounded-full bg-[#269B52]"></span>
                        <span class="h-1.5 w-5 rounded-full bg-[#E83F83]"></span>
                    </div>

                    <!-- Subheadline Elaborating SMART+ Capabilities -->
                    <p class="text-base sm:text-lg lg:text-xl text-aybisa-gray leading-relaxed mb-10 max-w-3xl mx-auto">
                        Tinggalkan presensi manual berbasis lembaran kertas. <strong>SMART+</strong> mengintegrasikan <strong>Terminal Kiosk RFID</strong> di gerbang sekolah, <strong>GPS Geofencing</strong> untuk guru, academic scheduling, dan automated reporting yang siap diekspor ke Excel dan PDF.
                    </p>

                    <!-- CTAs with Direct WhatsApp Demo Button -->
                    <div class="flex flex-col sm:flex-row justify-center items-center gap-4 mb-12">
                        <!-- Primary Login -->
                        <a href="{{ route('login') }}" class="w-full sm:w-auto inline-flex items-center justify-center rounded-2xl bg-aybisa-blue px-8 py-4 text-base font-bold text-white shadow-lg shadow-aybisa-blue/30 hover:bg-aybisa-blue-hover hover:shadow-xl hover:-translate-y-0.5 transition-all">
                            Sign In ke Dashboard
                            <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>

                        <!-- Direct WhatsApp Demo CTA -->
                        <a href="{{ $waUrl }}" target="_blank" class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 rounded-2xl bg-emerald-600 px-7 py-4 text-base font-bold text-white shadow-lg shadow-emerald-600/25 hover:bg-emerald-700 hover:shadow-xl hover:-translate-y-0.5 transition-all">
                            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.007c.107.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.173.086.275.072.376-.044.101-.116.433-.506.549-.68.116-.174.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.043.073.043.419-.101.825z"/></svg>
                            Request Live Demo (WhatsApp)
                        </a>

                        <!-- Kiosk Mode Link -->
                        <a href="{{ route('kiosk.index') }}" target="_blank" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-2xl bg-white border border-slate-200 px-6 py-4 text-base font-bold text-aybisa-navy shadow-sm hover:border-aybisa-blue hover:bg-aybisa-pale/50 transition-all">
                            Buka Kiosk Mode
                        </a>
                    </div>

                    <!-- 4 Key Value Metric Pills -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-6 border-t border-slate-200/80 text-left">
                        <div class="p-3.5 bg-white rounded-2xl border border-slate-200 shadow-2xs hover:border-aybisa-blue transition-colors">
                            <div class="text-xl font-extrabold text-aybisa-blue">&lt; 0.4s Latency</div>
                            <p class="text-xs text-aybisa-navy font-bold mt-0.5">RFID Tap Speed</p>
                            <p class="text-[11px] text-aybisa-gray">Auto-submit instan tanpa bottleneck</p>
                        </div>
                        <div class="p-3.5 bg-white rounded-2xl border border-slate-200 shadow-2xs hover:border-aybisa-green transition-colors">
                            <div class="text-xl font-extrabold text-aybisa-green">&plusmn; {{ $radiusAbsensi }}m Radius</div>
                            <p class="text-xs text-aybisa-navy font-bold mt-0.5">GPS Geofencing</p>
                            <p class="text-[11px] text-aybisa-gray">Presisi verifikasi lokasi guru</p>
                        </div>
                        <div class="p-3.5 bg-white rounded-2xl border border-slate-200 shadow-2xs hover:border-aybisa-yellow transition-colors">
                            <div class="text-xl font-extrabold text-amber-600">Automated</div>
                            <p class="text-xs text-aybisa-navy font-bold mt-0.5">Reporting &amp; E-Rapor</p>
                            <p class="text-[11px] text-aybisa-gray">Instant export Excel &amp; PDF</p>
                        </div>
                        <div class="p-3.5 bg-white rounded-2xl border border-slate-200 shadow-2xs hover:border-aybisa-pink transition-colors">
                            <div class="text-xl font-extrabold text-aybisa-pink">6 Roles</div>
                            <p class="text-xs text-aybisa-navy font-bold mt-0.5">Role-Based Access</p>
                            <p class="text-[11px] text-aybisa-gray">Admin, KS, Guru, Staff, Siswa</p>
                        </div>
                    </div>
                </div>

                <!-- Hero Interactive UI Showcase with Floating Badges -->
                <div class="mt-14 max-w-5xl mx-auto relative">
                    
                    <!-- Floating Micro-Card 1 (Top-Left): Live Real-time Status -->
                    <div class="absolute -top-6 -left-4 sm:-left-8 z-20 bg-white/95 backdrop-blur-md rounded-2xl p-3 border border-emerald-200 shadow-xl hidden md:flex items-center gap-3 animate-float-1">
                        <div class="w-9 h-9 rounded-xl bg-emerald-100 text-aybisa-green flex items-center justify-center font-bold text-sm">
                            <svg class="w-5 h-5 text-aybisa-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-aybisa-green uppercase tracking-wider block">RFID Tap Verified</span>
                            <p class="text-xs font-extrabold text-aybisa-navy" x-text="lastTappedName"></p>
                        </div>
                    </div>

                    <!-- Floating Micro-Card 2 (Top-Right): Geofence Radius Verification -->
                    <div class="absolute -top-8 -right-4 sm:-right-8 z-20 bg-white/95 backdrop-blur-md rounded-2xl p-3 border border-blue-200 shadow-xl hidden md:flex items-center gap-3 animate-float-2">
                        <div class="w-9 h-9 rounded-xl bg-blue-100 text-aybisa-blue flex items-center justify-center font-bold text-sm">
                            <svg class="w-5 h-5 text-aybisa-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-aybisa-blue uppercase tracking-wider block">GPS Geofence Verified</span>
                            <p class="text-xs font-extrabold text-aybisa-navy">Guru: In-Radius (14.2m)</p>
                        </div>
                    </div>

                    <!-- Floating Micro-Card 3 (Bottom-Right): Speed Metric -->
                    <div class="absolute -bottom-6 -right-4 sm:-right-6 z-20 bg-white/95 backdrop-blur-md rounded-2xl p-2.5 px-4 border border-amber-200 shadow-xl hidden md:flex items-center gap-2.5 animate-float-3">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-ping"></span>
                        <p class="text-xs font-bold text-aybisa-navy">Tap Latency: <strong class="text-amber-600 font-extrabold">&lt;0.38s</strong></p>
                    </div>

                    <!-- Main Showcase Window Container -->
                    <div class="rounded-3xl border border-slate-200 bg-white p-4 sm:p-6 shadow-2xl shadow-blue-900/10 relative overflow-hidden animate-glow-border">
                        
                        <!-- Top Chrome Bar -->
                        <div class="flex flex-wrap items-center justify-between pb-4 mb-5 border-b border-slate-100 gap-2">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-rose-400"></span>
                                <span class="w-3 h-3 rounded-full bg-amber-400"></span>
                                <span class="w-3 h-3 rounded-full bg-emerald-400"></span>
                                <span class="ml-2 text-xs font-bold text-aybisa-navy flex items-center gap-1.5">
                                    <span class="text-aybisa-blue font-extrabold">SMART+</span> Engine &bull; {{ $namaSekolah }}
                                </span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-[11px] font-bold text-aybisa-green border border-emerald-100">
                                    <span class="w-2 h-2 rounded-full bg-aybisa-green animate-ping"></span>
                                    Scanner Active: Terminal Kiosk 01
                                </span>
                                <span class="text-xs font-mono font-bold text-slate-600" x-text="currentTimeString"></span>
                            </div>
                        </div>

                        <!-- Inside Interactive Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-5">
                            
                            <!-- Col 1: Living Auto-Cycle RFID Simulator (5 Cols) -->
                            <div class="md:col-span-5 rounded-2xl p-5 bg-gradient-to-br from-slate-900 via-aybisa-navy to-slate-900 text-white flex flex-col justify-between relative overflow-hidden shadow-md">
                                
                                <!-- Scanning Laser Line Animation Sweep -->
                                <div class="absolute left-0 right-0 h-1 bg-gradient-to-r from-transparent via-cyan-400 to-transparent shadow-[0_0_15px_#38bdf8] animate-laser-scan pointer-events-none"></div>

                                <div>
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-cyan-400 animate-pulse"></span>
                                            <span class="text-xs font-bold uppercase tracking-wider text-slate-300">Live Kiosk Scanner</span>
                                        </div>
                                        <span class="text-[10px] font-mono bg-white/10 px-2 py-0.5 rounded text-emerald-300" x-text="lastTappedTime"></span>
                                    </div>

                                    <!-- Interactive RFID Card Widget with Ripple Feedback -->
                                    <div @click="triggerSimulatedTap()" 
                                         class="cursor-pointer select-none rounded-xl bg-white/10 border border-white/15 p-4 text-center hover:bg-white/20 hover:border-cyan-400/50 transition-all duration-200 relative group/card">
                                        
                                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-aybisa-blue to-cyan-400 text-white mx-auto flex items-center justify-center mb-3 shadow-lg group-hover/card:scale-110 transition-transform">
                                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                        </div>

                                        <p class="text-sm font-bold text-white mb-0.5">RFID Scanner Active</p>
                                        <p class="text-[11px] text-slate-300">Auto-cycling simulation &bull; Click to tap manually</p>

                                        <!-- Success Overlay Pop-in -->
                                        <div x-show="tapSuccess" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="absolute inset-0 bg-emerald-600/95 rounded-xl flex flex-col items-center justify-center p-3 text-white">
                                            <svg class="w-8 h-8 text-white mb-1 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            <p class="text-xs font-extrabold">Tap Verified &amp; Recorded</p>
                                            <p class="text-[10px] text-emerald-100 font-semibold" x-text="lastTappedName + ' &bull; ' + lastTappedClass"></p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Recent Tap Record Details -->
                                <div class="mt-4 pt-3 border-t border-white/10 text-xs">
                                    <div class="flex items-center justify-between text-slate-300 mb-1">
                                        <span>Latest Recorded Event:</span>
                                        <span class="text-emerald-400 font-bold">On-Time Attendance</span>
                                    </div>
                                    <div class="p-2.5 rounded-lg bg-white/5 border border-white/10 flex items-center justify-between">
                                        <div class="min-w-0 pr-2">
                                            <p class="font-bold text-white truncate" x-text="lastTappedName"></p>
                                            <p class="text-[10px] text-slate-400" x-text="lastTappedClass + ' &bull; UID: 4A8B2C91'"></p>
                                        </div>
                                        <span class="text-[10px] font-mono text-cyan-300 whitespace-nowrap" x-text="lastTappedTime"></span>
                                    </div>
                                </div>

                            </div>

                            <!-- Col 2: Live Statistics & Visual Metrics (7 Cols) -->
                            <div class="md:col-span-7 flex flex-col justify-between space-y-4">
                                
                                <!-- Live Metrics Card -->
                                <div class="rounded-2xl p-4 bg-aybisa-pale/70 border border-blue-100">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-bold text-aybisa-navy">Today's Attendance Rate</span>
                                        <span class="text-[11px] font-extrabold px-2.5 py-0.5 rounded-full bg-emerald-100 text-aybisa-green">98.6% Recorded</span>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2 text-center my-3">
                                        <div class="p-2.5 bg-white rounded-xl border border-slate-100 shadow-2xs">
                                            <span class="text-xs text-aybisa-gray font-medium block">Present</span>
                                            <strong class="text-lg font-extrabold text-aybisa-green" x-text="simulatedTaps"></strong>
                                        </div>
                                        <div class="p-2.5 bg-white rounded-xl border border-slate-100 shadow-2xs">
                                            <span class="text-xs text-aybisa-gray font-medium block">Late</span>
                                            <strong class="text-lg font-extrabold text-amber-600">6</strong>
                                        </div>
                                        <div class="p-2.5 bg-white rounded-xl border border-slate-100 shadow-2xs">
                                            <span class="text-xs text-aybisa-gray font-medium block">Excused</span>
                                            <strong class="text-lg font-extrabold text-aybisa-blue">2</strong>
                                        </div>
                                    </div>
                                    <div class="w-full bg-slate-200 h-2.5 rounded-full overflow-hidden flex">
                                        <div class="bg-aybisa-green h-full transition-all duration-500" style="width: 93%"></div>
                                        <div class="bg-aybisa-yellow h-full" style="width: 5%"></div>
                                        <div class="bg-aybisa-blue h-full" style="width: 2%"></div>
                                    </div>
                                </div>

                                <!-- GPS Geofence Verification Pill -->
                                <div class="rounded-2xl p-4 bg-white border border-slate-200 flex items-center justify-between gap-3 shadow-2xs">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-aybisa-blue flex items-center justify-center font-bold text-lg flex-shrink-0">
                                            <svg class="w-5 h-5 text-aybisa-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-aybisa-navy">GPS Geofencing Teacher Attendance</p>
                                            <p class="text-[11px] text-aybisa-gray">Validated within radius {{ $radiusAbsensi }}m of school coordinates</p>
                                        </div>
                                    </div>
                                    <span class="text-[11px] font-bold text-aybisa-blue bg-blue-50 px-3 py-1 rounded-full border border-blue-100">
                                        In-Radius
                                    </span>
                                </div>

                                <!-- Quick Report Status with WhatsApp Demo Prompt -->
                                <div class="rounded-2xl p-4 bg-white border border-slate-200 flex items-center justify-between gap-3 shadow-2xs">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-aybisa-green flex items-center justify-center font-bold text-lg flex-shrink-0">
                                            <svg class="w-5 h-5 text-aybisa-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-aybisa-navy">Automated Daily &amp; E-Rapor Recapitulation</p>
                                            <p class="text-[11px] text-aybisa-gray">Instant export ready in Excel &amp; PDF</p>
                                        </div>
                                    </div>
                                    <a href="{{ $waUrl }}" target="_blank" class="text-[11px] font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 px-3 py-1 rounded-full border border-emerald-200 transition-colors">
                                        Sample Report &rarr;
                                    </a>
                                </div>

                            </div>

                        </div>

                        <!-- Footer Pill of Preview -->
                        <div class="mt-4 pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between text-xs text-aybisa-gray">
                            <span class="flex items-center gap-1.5">
                                <span class="font-bold text-aybisa-navy">SMART+</span>
                                <span class="text-slate-300">&bull;</span>
                                <span>Engineered by Aybisa Studio for modern education efficiency</span>
                            </span>
                            <span class="text-[11px] text-slate-500">Zero-Paper Workflow &bull; Kurikulum Merdeka Compliant</span>
                        </div>

                    </div>
                </div>

            </div>
        </section>

        <!-- Continuous Smooth Live Marquee Ticker -->
        <div class="py-3 bg-slate-900 text-white overflow-hidden border-y border-slate-800 select-none">
            <div class="marquee-track flex items-center gap-8 text-xs font-medium">
                <!-- Group 1 -->
                <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-emerald-400"></span> 06:48 WIB: Ahmad Fauzi (8-A) &bull; On-Time (RFID Tap)</span>
                <span class="text-slate-600">&bull;</span>
                <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-blue-400"></span> 06:46 WIB: Ustadzah Fatimah, S.Pd (Matematika) &bull; GPS Valid (Radius 12m)</span>
                <span class="text-slate-600">&bull;</span>
                <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-emerald-400"></span> 06:45 WIB: Nabila Syakira (8-B) &bull; On-Time (RFID Tap)</span>
                <span class="text-slate-600">&bull;</span>
                <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-amber-400"></span> 06:44 WIB: Kiosk Terminal 01: 418 Students Recorded Without Queues</span>
                <span class="text-slate-600">&bull;</span>
                <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-emerald-400"></span> 06:42 WIB: Rayhan Maulana (7-A) &bull; On-Time (RFID Tap)</span>
                <span class="text-slate-600">&bull;</span>
                <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-blue-400"></span> 06:40 WIB: Ustadz Hendra, M.Pd &bull; Verified Teaching Session</span>
                <span class="text-slate-600">&bull;</span>

                <!-- Duplicate for Smooth Infinite Loop -->
                <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-emerald-400"></span> 06:48 WIB: Ahmad Fauzi (8-A) &bull; On-Time (RFID Tap)</span>
                <span class="text-slate-600">&bull;</span>
                <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-blue-400"></span> 06:46 WIB: Ustadzah Fatimah, S.Pd (Matematika) &bull; GPS Valid (Radius 12m)</span>
                <span class="text-slate-600">&bull;</span>
                <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-emerald-400"></span> 06:45 WIB: Nabila Syakira (8-B) &bull; On-Time (RFID Tap)</span>
                <span class="text-slate-600">&bull;</span>
                <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-amber-400"></span> 06:44 WIB: Kiosk Terminal 01: 418 Students Recorded Without Queues</span>
                <span class="text-slate-600">&bull;</span>
                <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-emerald-400"></span> 06:42 WIB: Rayhan Maulana (7-A) &bull; On-Time (RFID Tap)</span>
                <span class="text-slate-600">&bull;</span>
                <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-blue-400"></span> 06:40 WIB: Ustadz Hendra, M.Pd &bull; Verified Teaching Session</span>
                <span class="text-slate-600">&bull;</span>
            </div>
        </div>

        <!-- Section 2: 6 Fitur Utama SMART+ (Elaborated Deep Dive) -->
        <section id="fitur" class="py-20 sm:py-28 bg-white border-t border-slate-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-aybisa-pale text-xs font-bold text-aybisa-blue border border-blue-200 mb-3">
                        CORE ARCHITECTURE
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-aybisa-navy tracking-tight mb-4">
                        Modul Lengkap untuk Seluruh Kebutuhan Sekolah
                    </h2>
                    <p class="text-base sm:text-lg text-aybisa-gray leading-relaxed">
                        Dirancang khusus untuk memecahkan hambatan operasional: antrean jam masuk, perhitungan manual buku absensi, dan lambatnya pembuatan rekap semester.
                    </p>
                </div>

                <!-- 6 Feature Cards with Aybisa Brand Accents -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                    
                    <!-- Feature 1: Presensi Dual-Engine (Blue + Green) -->
                    <article class="rounded-3xl p-7 bg-white border border-slate-200 shadow-sm hover:shadow-xl hover:border-aybisa-blue/50 transition-all duration-300 group flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-5">
                                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-aybisa-blue flex items-center justify-center font-bold text-xl group-hover:scale-110 transition-transform">
                                    <svg class="w-6 h-6 text-aybisa-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                                </div>
                                <span class="text-[11px] font-bold text-aybisa-green bg-emerald-50 px-3 py-1 rounded-full border border-emerald-100 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-aybisa-green animate-pulse"></span>
                                    Dual-Engine
                                </span>
                            </div>
                            <h3 class="text-xl font-bold text-aybisa-navy mb-2.5">RFID Card Kiosk &amp; GPS Geofencing</h3>
                            <p class="text-sm text-aybisa-gray leading-relaxed mb-4">
                                Kombinasi dua metode pencatatan kehadiran presisi: tap kartu RFID cepat di gerbang via Kiosk Mode, serta absensi mandiri berbasis GPS Geofencing dengan batasan radius meter untuk guru dan staf.
                            </p>
                        </div>
                        <ul class="pt-4 border-t border-slate-100 space-y-2 text-xs text-aybisa-navy font-medium">
                            <li class="flex items-center gap-2">
                                <span class="text-aybisa-blue font-bold">&bull;</span>
                                Tap latency &lt;0.4 detik anti bottleneck antrean
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-aybisa-blue font-bold">&bull;</span>
                                Geofence radius validation akurat dari titik sekolah
                            </li>
                        </ul>
                    </article>

                    <!-- Feature 2: Otomasi Rekapitulasi (Red + Yellow) -->
                    <article class="rounded-3xl p-7 bg-white border border-slate-200 shadow-sm hover:shadow-xl hover:border-aybisa-red/50 transition-all duration-300 group flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-5">
                                <div class="w-12 h-12 rounded-2xl bg-red-50 text-aybisa-red flex items-center justify-center font-bold text-xl group-hover:scale-110 transition-transform">
                                    <svg class="w-6 h-6 text-aybisa-red" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <span class="text-[11px] font-bold text-amber-700 bg-amber-50 px-3 py-1 rounded-full border border-amber-100">
                                    Excel &amp; PDF Export
                                </span>
                            </div>
                            <h3 class="text-xl font-bold text-aybisa-navy mb-2.5">Automated Reporting &amp; E-Rapor Sync</h3>
                            <p class="text-sm text-aybisa-gray leading-relaxed mb-4">
                                Zero-paper workflow. Sistem secara otomatis mengkalkulasi akumulasi Hadir, Sakit, Izin, Terlambat, dan Alpa per siswa maupun per kelas tanpa rekapitulasi manual.
                            </p>
                        </div>
                        <ul class="pt-4 border-t border-slate-100 space-y-2 text-xs text-aybisa-navy font-medium">
                            <li class="flex items-center gap-2">
                                <span class="text-aybisa-red font-bold">&bull;</span>
                                Automated monthly &amp; semester reporting
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-aybisa-red font-bold">&bull;</span>
                                Export siap cetak format resmi dinas pendidikan
                            </li>
                        </ul>
                    </article>

                    <!-- Feature 3: Jadwal & Kalender KBM (Pink + Blue) -->
                    <article class="rounded-3xl p-7 bg-white border border-slate-200 shadow-sm hover:shadow-xl hover:border-aybisa-pink/50 transition-all duration-300 group flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-5">
                                <div class="w-12 h-12 rounded-2xl bg-pink-50 text-aybisa-pink flex items-center justify-center font-bold text-xl group-hover:scale-110 transition-transform">
                                    <svg class="w-6 h-6 text-aybisa-pink" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <span class="text-[11px] font-bold text-aybisa-blue bg-blue-50 px-3 py-1 rounded-full border border-blue-100">
                                    Academic Schedule
                                </span>
                            </div>
                            <h3 class="text-xl font-bold text-aybisa-navy mb-2.5">Academic Scheduling &amp; KBM Tracker</h3>
                            <p class="text-sm text-aybisa-gray leading-relaxed mb-4">
                                Pengaturan alokasi jam tatap muka per mata pelajaran, monitoring keterisian jam mengajar guru, kalender kegiatan akademik, serta manajemen hari libur terpusat.
                            </p>
                        </div>
                        <ul class="pt-4 border-t border-slate-100 space-y-2 text-xs text-aybisa-navy font-medium">
                            <li class="flex items-center gap-2">
                                <span class="text-aybisa-pink font-bold">&bull;</span>
                                Real-time teaching presence monitoring
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-aybisa-pink font-bold">&bull;</span>
                                FullCalendar UI interaktif untuk seluruh agenda
                            </li>
                        </ul>
                    </article>

                    <!-- Feature 4: Portal 6 Role Pengguna (Green + Navy) -->
                    <article class="rounded-3xl p-7 bg-white border border-slate-200 shadow-sm hover:shadow-xl hover:border-aybisa-green/50 transition-all duration-300 group flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-5">
                                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-aybisa-green flex items-center justify-center font-bold text-xl group-hover:scale-110 transition-transform">
                                    <svg class="w-6 h-6 text-aybisa-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                </div>
                                <span class="text-[11px] font-bold text-aybisa-navy bg-slate-100 px-3 py-1 rounded-full border border-slate-200">
                                    RBAC Access
                                </span>
                            </div>
                            <h3 class="text-xl font-bold text-aybisa-navy mb-2.5">Role-Based Access Control (6 Roles)</h3>
                            <p class="text-sm text-aybisa-gray leading-relaxed mb-4">
                                Portal dengan hak akses terisolasi untuk Super Admin, Kepala Sekolah, Direktur/Yayasan, Guru Mapel &amp; Wali Kelas, Staf Akademik, hingga Siswa &amp; Orang Tua.
                            </p>
                        </div>
                        <ul class="pt-4 border-t border-slate-100 space-y-2 text-xs text-aybisa-navy font-medium">
                            <li class="flex items-center gap-2">
                                <span class="text-aybisa-green font-bold">&bull;</span>
                                Executive dashboard statistik untuk Kepala Sekolah
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-aybisa-green font-bold">&bull;</span>
                                Self-service attendance portal untuk Guru &amp; Siswa
                            </li>
                        </ul>
                    </article>

                    <!-- Feature 5: Integrasi SMART CBT (Pink + Yellow) -->
                    <article class="rounded-3xl p-7 bg-white border border-slate-200 shadow-sm hover:shadow-xl hover:border-aybisa-pink/50 transition-all duration-300 group flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-5">
                                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-aybisa-yellow flex items-center justify-center font-bold text-xl group-hover:scale-110 transition-transform">
                                    <svg class="w-6 h-6 text-aybisa-yellow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                </div>
                                <span class="text-[11px] font-bold text-aybisa-pink bg-pink-50 px-3 py-1 rounded-full border border-pink-100">
                                    CBT Integration
                                </span>
                            </div>
                            <h3 class="text-xl font-bold text-aybisa-navy mb-2.5">SMART CBT Ecosystem Sync</h3>
                            <p class="text-sm text-aybisa-gray leading-relaxed mb-4">
                                Terhubung langsung dengan platform Computer Based Testing (CBT). Data peserta ujian, kelas, dan mata pelajaran tersinkronisasi tanpa entri data ganda.
                            </p>
                        </div>
                        <ul class="pt-4 border-t border-slate-100 space-y-2 text-xs text-aybisa-navy font-medium">
                            <li class="flex items-center gap-2">
                                <span class="text-aybisa-yellow font-bold">&bull;</span>
                                Single database sync untuk absensi &amp; ujian CBT
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-aybisa-yellow font-bold">&bull;</span>
                                Modul materi pembelajaran &amp; submission tugas
                            </li>
                        </ul>
                    </article>

                    <!-- Feature 6: Audit Trail & Keamanan (Navy + Red) -->
                    <article class="rounded-3xl p-7 bg-white border border-slate-200 shadow-sm hover:shadow-xl hover:border-aybisa-navy/50 transition-all duration-300 group flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-5">
                                <div class="w-12 h-12 rounded-2xl bg-slate-100 text-aybisa-navy flex items-center justify-center font-bold text-xl group-hover:scale-110 transition-transform">
                                    <svg class="w-6 h-6 text-aybisa-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                </div>
                                <span class="text-[11px] font-bold text-aybisa-red bg-red-50 px-3 py-1 rounded-full border border-red-100">
                                    Audit Trail
                                </span>
                            </div>
                            <h3 class="text-xl font-bold text-aybisa-navy mb-2.5">Security Logs &amp; Audit Trail</h3>
                            <p class="text-sm text-aybisa-gray leading-relaxed mb-4">
                                Setiap perubahan data, login akun, input manual, dan riwayat presensi tercatat dalam audit log lengkap demi akuntabilitas, transparansi, serta kepatuhan privasi data sivitas sekolah.
                            </p>
                        </div>
                        <ul class="pt-4 border-t border-slate-100 space-y-2 text-xs text-aybisa-navy font-medium">
                            <li class="flex items-center gap-2">
                                <span class="text-aybisa-navy font-bold">&bull;</span>
                                Rate-limiting protection &amp; IP logging
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-aybisa-navy font-bold">&bull;</span>
                                Secure session token &amp; role-isolated state
                            </li>
                        </ul>
                    </article>

                </div>

            </div>
        </section>

        <!-- Section 3: Interactive Module Explorer (Tabs with Clean Transitions) -->
        <section id="demo-interaktif" class="py-20 sm:py-28 bg-aybisa-pale/50 border-t border-slate-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="text-center max-w-3xl mx-auto mb-12">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-white text-xs font-bold text-aybisa-blue border border-blue-200 mb-3">
                        MODULE EXPLORER
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-aybisa-navy tracking-tight mb-4">
                        Lihat Bagaimana SMART+ Bekerja
                    </h2>
                    <p class="text-base sm:text-lg text-aybisa-gray leading-relaxed">
                        Pilih modul di bawah ini untuk melihat simulasi antarmuka dan alur operasional di sekolah.
                    </p>
                </div>

                <!-- Tab Navigation Buttons with Clean SVG Icons -->
                <div class="flex flex-wrap items-center justify-center gap-2 sm:gap-3 mb-10">
                    <button @click="activeTab = 'rfid'" 
                            :class="activeTab === 'rfid' ? 'bg-aybisa-blue text-white shadow-md' : 'bg-white text-aybisa-gray hover:text-aybisa-navy hover:bg-slate-50'"
                            class="px-5 py-3 rounded-2xl text-xs sm:text-sm font-bold transition-all flex items-center gap-2 border border-slate-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        Kiosk RFID Mode
                    </button>
                    <button @click="activeTab = 'gps'" 
                            :class="activeTab === 'gps' ? 'bg-aybisa-blue text-white shadow-md' : 'bg-white text-aybisa-gray hover:text-aybisa-navy hover:bg-slate-50'"
                            class="px-5 py-3 rounded-2xl text-xs sm:text-sm font-bold transition-all flex items-center gap-2 border border-slate-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        GPS Geofencing
                    </button>
                    <button @click="activeTab = 'rekap'" 
                            :class="activeTab === 'rekap' ? 'bg-aybisa-blue text-white shadow-md' : 'bg-white text-aybisa-gray hover:text-aybisa-navy hover:bg-slate-50'"
                            class="px-5 py-3 rounded-2xl text-xs sm:text-sm font-bold transition-all flex items-center gap-2 border border-slate-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        Automated Reporting
                    </button>
                    <button @click="activeTab = 'kbm'" 
                            :class="activeTab === 'kbm' ? 'bg-aybisa-blue text-white shadow-md' : 'bg-white text-aybisa-gray hover:text-aybisa-navy hover:bg-slate-50'"
                            class="px-5 py-3 rounded-2xl text-xs sm:text-sm font-bold transition-all flex items-center gap-2 border border-slate-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Academic Schedule
                    </button>
                </div>

                <!-- Tab 1: Kiosk RFID -->
                <div x-show="activeTab === 'rfid'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="max-w-4xl mx-auto">
                    <div class="rounded-3xl bg-white border border-slate-200 p-6 sm:p-10 shadow-xl">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-center">
                            <div class="md:col-span-6 space-y-4">
                                <span class="text-xs font-bold text-aybisa-blue uppercase tracking-wider block">Gate Terminal Mode</span>
                                <h3 class="text-2xl font-extrabold text-aybisa-navy">Presensi Kilat 400+ Siswa Tanpa Bottleneck</h3>
                                <p class="text-sm text-aybisa-gray leading-relaxed">
                                    Siswa cukup menempelkan kartu fisik RFID ke pembaca USB reader. Layar langsung memutar notifikasi audio, menampilkan foto siswa, kelas, dan status kehadiran secara seketika.
                                </p>
                                <div class="space-y-2 pt-2">
                                    <div class="flex items-center gap-2 text-xs font-semibold text-aybisa-navy">
                                        <span class="w-5 h-5 rounded-md bg-emerald-100 text-aybisa-green flex items-center justify-center flex-shrink-0">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        </span>
                                        <span>Auto-Submit otomatis tanpa klik tombol apa pun</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-xs font-semibold text-aybisa-navy">
                                        <span class="w-5 h-5 rounded-md bg-emerald-100 text-aybisa-green flex items-center justify-center flex-shrink-0">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        </span>
                                        <span>Toleransi keterlambatan dikonfigurasi dinamis</span>
                                    </div>
                                </div>
                                <div class="pt-4 flex items-center gap-4">
                                    <a href="{{ route('kiosk.index') }}" target="_blank" class="inline-flex items-center gap-2 text-xs font-bold text-aybisa-blue hover:underline">
                                        Buka Kiosk Mode Sekarang &rarr;
                                    </a>
                                    <a href="{{ $waUrl }}" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-600 hover:text-emerald-700">
                                        Konsultasi Hardware
                                    </a>
                                </div>
                            </div>
                            <div class="md:col-span-6">
                                <div class="rounded-2xl bg-slate-900 p-5 text-white shadow-xl relative overflow-hidden">
                                    <div class="flex items-center justify-between pb-3 border-b border-slate-800 text-xs text-slate-400">
                                        <span>Terminal Gerbang Utama</span>
                                        <span class="text-emerald-400 font-bold">&bull; Live</span>
                                    </div>
                                    <div class="py-6 text-center">
                                        <div class="w-16 h-16 rounded-full bg-emerald-500/20 text-emerald-400 mx-auto flex items-center justify-center mb-3 animate-pulse">
                                            <svg class="w-8 h-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                        <h4 class="text-base font-bold text-white">Tap Recorded</h4>
                                        <p class="text-xs text-slate-300 mt-1">Muhammad Rizky Pratama (8-A)</p>
                                        <p class="text-[11px] text-emerald-400 font-bold mt-2">06:45:12 WIB &bull; ON-TIME ATTENDANCE</p>
                                    </div>
                                    <div class="p-2.5 rounded-xl bg-slate-800/70 text-[10px] text-slate-400 text-center font-mono">
                                        Card Protocol: Mifare 13.56MHz &bull; UID: 4A8B2C91
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Presensi GPS Guru -->
                <div x-show="activeTab === 'gps'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="max-w-4xl mx-auto">
                    <div class="rounded-3xl bg-white border border-slate-200 p-6 sm:p-10 shadow-xl">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-center">
                            <div class="md:col-span-6 space-y-4">
                                <span class="text-xs font-bold text-aybisa-green uppercase tracking-wider block">Staff Attendance</span>
                                <h3 class="text-2xl font-extrabold text-aybisa-navy">Presensi GPS Akurat Berbasis Geofencing</h3>
                                <p class="text-sm text-aybisa-gray leading-relaxed">
                                    Guru dan tenaga kependidikan dapat melakukan presensi kehadiran melalui smartphone saat tiba di lingkungan sekolah. Sistem memverifikasi koordinat GPS latitude/longitude secara realtime.
                                </p>
                                <div class="space-y-2 pt-2">
                                    <div class="flex items-center gap-2 text-xs font-semibold text-aybisa-navy">
                                        <span class="w-5 h-5 rounded-md bg-blue-100 text-aybisa-blue flex items-center justify-center flex-shrink-0">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        </span>
                                        <span>Radius validasi aman: {{ $radiusAbsensi }} meter dari titik sekolah</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-xs font-semibold text-aybisa-navy">
                                        <span class="w-5 h-5 rounded-md bg-blue-100 text-aybisa-blue flex items-center justify-center flex-shrink-0">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        </span>
                                        <span>Anti fake GPS &amp; pencatatan otomatis waktu masuk/pulang</span>
                                    </div>
                                </div>
                            </div>
                            <div class="md:col-span-6">
                                <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-lg">
                                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                            <span class="text-xs font-bold text-aybisa-navy">GPS Coordinates Verification</span>
                                        </div>
                                        <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">In-Radius</span>
                                    </div>
                                    <div class="p-4 rounded-xl bg-aybisa-pale/70 border border-blue-100 mb-3 text-center">
                                        <div class="w-10 h-10 rounded-xl bg-blue-100 text-aybisa-blue mx-auto flex items-center justify-center mb-2">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        </div>
                                        <p class="text-xs font-bold text-aybisa-navy">Jarak ke Titik Sekolah: 14.2 Meter</p>
                                        <p class="text-[10px] text-aybisa-gray mt-1">Batas toleransi maksimal: {{ $radiusAbsensi }} Meter</p>
                                    </div>
                                    <button class="w-full py-2.5 rounded-xl bg-aybisa-blue text-white text-xs font-bold shadow-sm">
                                        Catat Presensi Masuk
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 3: Rekapitulasi Otomatis -->
                <div x-show="activeTab === 'rekap'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="max-w-4xl mx-auto">
                    <div class="rounded-3xl bg-white border border-slate-200 p-6 sm:p-10 shadow-xl">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-center">
                            <div class="md:col-span-6 space-y-4">
                                <span class="text-xs font-bold text-aybisa-red uppercase tracking-wider block">Zero-Paper Workflow</span>
                                <h3 class="text-2xl font-extrabold text-aybisa-navy">Laporan Terkalkulasi Siap Ekspor dalam Hitungan Detik</h3>
                                <p class="text-sm text-aybisa-gray leading-relaxed">
                                    Wali kelas dan kepala sekolah tidak perlu lagi menghitung absensi lembar demi lembar. Klik tombol ekspor, laporan langsung tersusun rapi dengan persentase kehadiran lengkap.
                                </p>
                                <div class="flex items-center gap-3 pt-2">
                                    <span class="px-3.5 py-2 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-bold text-emerald-800 flex items-center gap-1.5">
                                        Format Excel (.xlsx)
                                    </span>
                                    <span class="px-3.5 py-2 rounded-xl bg-rose-50 border border-rose-200 text-xs font-bold text-rose-800 flex items-center gap-1.5">
                                        Format Dokumen PDF
                                    </span>
                                </div>
                            </div>
                            <div class="md:col-span-6">
                                <div class="rounded-2xl border border-slate-200 p-4 bg-slate-50 text-xs space-y-2 font-mono">
                                    <div class="bg-white p-3 rounded-xl border border-slate-200">
                                        <div class="font-bold text-aybisa-navy flex justify-between">
                                            <span>REKAP KELAS 8-A (OKTOBER 2026)</span>
                                            <span class="text-emerald-600 font-extrabold">98.2%</span>
                                        </div>
                                        <div class="mt-2 text-[10px] text-slate-500 grid grid-cols-4 gap-1 text-center">
                                            <div class="bg-slate-50 p-1.5 rounded">Hadir: <strong>720</strong></div>
                                            <div class="bg-amber-50 text-amber-700 p-1.5 rounded">Telat: <strong>8</strong></div>
                                            <div class="bg-blue-50 text-blue-700 p-1.5 rounded">Izin: <strong>4</strong></div>
                                            <div class="bg-rose-50 text-rose-700 p-1.5 rounded">Alpa: <strong>1</strong></div>
                                        </div>
                                    </div>
                                    <div class="text-[10px] text-slate-500 text-center pt-1">
                                        Formula baku terhitung otomatis sesuai standar E-Rapor
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 4: Jadwal & KBM -->
                <div x-show="activeTab === 'kbm'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="max-w-4xl mx-auto">
                    <div class="rounded-3xl bg-white border border-slate-200 p-6 sm:p-10 shadow-xl">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-center">
                            <div class="md:col-span-6 space-y-4">
                                <span class="text-xs font-bold text-aybisa-pink uppercase tracking-wider block">Curriculum &amp; Schedule</span>
                                <h3 class="text-2xl font-extrabold text-aybisa-navy">Jadwal KBM Terintegrasi &amp; Kalender Akademik</h3>
                                <p class="text-sm text-aybisa-gray leading-relaxed">
                                    Atur jam tatap muka guru per rombel kelas, pantau keterisian pembelajaran harian, serta jadwalkan hari libur sekolah dan agenda penilaian semester secara terpusat.
                                </p>
                            </div>
                            <div class="md:col-span-6">
                                <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm space-y-2">
                                    <div class="p-2.5 rounded-xl bg-blue-50/80 border border-blue-100 flex items-center justify-between text-xs">
                                        <div>
                                            <p class="font-bold text-aybisa-navy">Matematika &bull; Kelas 8-A</p>
                                            <p class="text-[10px] text-aybisa-gray">07:30 - 09:00 WIB &bull; Ustadz Hendra, M.Pd</p>
                                        </div>
                                        <span class="text-[10px] font-bold text-emerald-600 bg-white px-2 py-0.5 rounded border border-emerald-200">Active Session</span>
                                    </div>
                                    <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between text-xs">
                                        <div>
                                            <p class="font-bold text-aybisa-navy">Bahasa Arab &bull; Kelas 8-A</p>
                                            <p class="text-[10px] text-aybisa-gray">09:15 - 10:45 WIB &bull; Ustadz Farhan, Lc</p>
                                        </div>
                                        <span class="text-[10px] font-bold text-slate-500 bg-white px-2 py-0.5 rounded border border-slate-200">Next Session</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- Section 4: Live Kiosk Mode Spotlight (`#kiosk-demo`) -->
        <section id="kiosk-demo" class="py-20 sm:py-28 bg-white border-t border-slate-100 overflow-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="rounded-3xl bg-gradient-to-br from-aybisa-navy via-aybisa-navy to-slate-900 text-white p-8 sm:p-12 lg:p-16 relative shadow-2xl overflow-hidden">
                    
                    <!-- Decorative Background Radial Glows -->
                    <div class="absolute -right-20 -top-20 w-96 h-96 bg-aybisa-blue/20 rounded-full blur-3xl pointer-events-none animate-float-1"></div>
                    <div class="absolute -left-20 -bottom-20 w-96 h-96 bg-aybisa-pink/15 rounded-full blur-3xl pointer-events-none animate-float-2"></div>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center relative z-10">
                        
                        <div class="lg:col-span-7">
                            <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-white/10 text-xs font-bold text-emerald-400 border border-white/15 mb-4">
                                <span class="w-2 h-2 rounded-full bg-aybisa-green animate-ping"></span>
                                TERMINAL KIOSK GERBANG SEKOLAH
                            </div>
                            <h2 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight leading-tight mb-5">
                                Kiosk Mode Khusus Layar Gerbang: <br class="hidden sm:inline">
                                Cepat, Praktis, &amp; Menyenangkan bagi Siswa
                            </h2>
                            <p class="text-slate-300 text-base leading-relaxed mb-6">
                                SMART+ menyediakan antarmuka khusus <strong>Kiosk Mode</strong> yang dapat dijalankan pada komputer, mini-PC, atau tablet di pos gerbang sekolah. Cukup sambungkan USB RFID Reader standar, sistem langsung siap melayani ratusan siswa setiap pagi.
                            </p>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
                                <div class="flex items-start gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-white/10 text-aybisa-blue-light flex items-center justify-center font-bold text-sm flex-shrink-0">
                                        <svg class="w-5 h-5 text-aybisa-blue-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-bold text-white">Auto-Submit Otomatis</h4>
                                        <p class="text-xs text-slate-400">Tidak perlu menekan keyboard atau mouse, data langsung terkirim dan tercatat.</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-white/10 text-emerald-400 flex items-center justify-center font-bold text-sm flex-shrink-0">
                                        <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-bold text-white">Audio &amp; Visual Feedback</h4>
                                        <p class="text-xs text-slate-400">Menampilkan foto siswa dan ucapan selamat datang yang ramah.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-4">
                                <a href="{{ route('kiosk.index') }}" target="_blank" class="inline-flex items-center justify-center gap-2 rounded-xl bg-aybisa-blue px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-aybisa-blue/40 hover:bg-aybisa-blue-hover transition-all">
                                    Buka Kiosk Mode Sekarang
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                                <!-- WhatsApp Demo Link -->
                                <a href="{{ $waUrl }}" target="_blank" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/10 px-6 py-3.5 text-sm font-bold text-white hover:bg-white/20 transition-all">
                                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.007c.107.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.173.086.275.072.376-.044.101-.116.433-.506.549-.68.116-.174.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.043.073.043.419-.101.825z"/></svg>
                                    Demo Hardware Kiosk
                                </a>
                            </div>
                        </div>

                        <!-- Kiosk Interactive Card Preview -->
                        <div class="lg:col-span-5">
                            <div class="rounded-3xl bg-slate-800/90 border border-slate-700 p-6 shadow-2xl backdrop-blur-sm relative">
                                <div class="flex items-center justify-between pb-4 border-b border-slate-700/80 mb-5">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                        <span class="text-xs font-bold text-slate-200">Terminal Kiosk Gerbang</span>
                                    </div>
                                    <span class="text-xs font-mono text-slate-400">SMART+ RFID</span>
                                </div>

                                <div class="text-center py-6">
                                    <div class="w-20 h-20 rounded-2xl bg-white/5 border border-white/10 mx-auto flex items-center justify-center mb-4 text-3xl shadow-inner">
                                        <svg class="w-10 h-10 text-cyan-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                    </div>
                                    <h4 class="text-lg font-bold text-white mb-1">Silakan Tempelkan Kartu RFID</h4>
                                    <p class="text-xs text-slate-400 max-w-xs mx-auto">
                                        Dekatkan kartu siswa ke sensor pembaca di samping layar
                                    </p>
                                </div>

                                <div class="p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-left">
                                    <div class="flex items-center justify-between text-xs font-bold text-emerald-400 mb-1">
                                        <span>Tap Verified</span>
                                        <span x-text="currentTimeString"></span>
                                    </div>
                                    <p class="text-xs text-white font-medium" x-text="lastTappedName + ' (' + lastTappedClass + ')'"></p>
                                    <p class="text-[10px] text-slate-400 mt-0.5">Status: On-Time Attendance &bull; Selamat Belajar!</p>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

            </div>
        </section>

        <!-- Section 5: Portal 6 Role Pengguna (`#role-portal`) -->
        <section id="role-portal" class="py-20 sm:py-28 bg-white border-t border-slate-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-aybisa-pale text-xs font-bold text-aybisa-blue border border-blue-200 mb-3">
                        ROLE-BASED PORTAL
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-aybisa-navy tracking-tight mb-4">
                        Satu Sistem untuk Seluruh Sivitas Sekolah
                    </h2>
                    <p class="text-base sm:text-lg text-aybisa-gray leading-relaxed">
                        Setiap pemangku kepentingan memiliki portal khusus dengan wewenang yang aman dan terisolasi.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    
                    <!-- Role 1: Super Admin & Admin Sekolah -->
                    <div class="p-6 rounded-2xl border border-slate-200 bg-white hover:border-aybisa-blue transition-all shadow-2xs hover:shadow-md">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-aybisa-blue flex items-center justify-center font-bold mb-4">
                            <svg class="w-5 h-5 text-aybisa-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <h4 class="text-base font-bold text-aybisa-navy mb-1.5">Admin Sekolah &amp; TU</h4>
                        <p class="text-xs text-aybisa-gray leading-relaxed">
                            Master data management siswa, guru, registrasi UID kartu RFID, kalender akademik, dan konfigurasi jam operasional.
                        </p>
                    </div>

                    <!-- Role 2: Kepala Sekolah -->
                    <div class="p-6 rounded-2xl border border-slate-200 bg-white hover:border-aybisa-green transition-all shadow-2xs hover:shadow-md">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-aybisa-green flex items-center justify-center font-bold mb-4">
                            <svg class="w-5 h-5 text-aybisa-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </div>
                        <h4 class="text-base font-bold text-aybisa-navy mb-1.5">Kepala Sekolah</h4>
                        <p class="text-xs text-aybisa-gray leading-relaxed">
                            Executive monitoring kehadiran tingkat sekolah, evaluasi kedisiplinan guru/siswa, dan verifikasi dispensasi tugas luar.
                        </p>
                    </div>

                    <!-- Role 3: Direktur & Yayasan -->
                    <div class="p-6 rounded-2xl border border-slate-200 bg-white hover:border-aybisa-yellow transition-all shadow-2xs hover:shadow-md">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 text-aybisa-yellow flex items-center justify-center font-bold mb-4">
                            <svg class="w-5 h-5 text-aybisa-yellow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <h4 class="text-base font-bold text-aybisa-navy mb-1.5">Direktur &amp; Yayasan</h4>
                        <p class="text-xs text-aybisa-gray leading-relaxed">
                            Laporan performa kehadiran keseluruhan unit sekolah dalam satu yayasan secara terpadu dan transparan.
                        </p>
                    </div>

                    <!-- Role 4: Guru & Wali Kelas -->
                    <div class="p-6 rounded-2xl border border-slate-200 bg-white hover:border-aybisa-pink transition-all shadow-2xs hover:shadow-md">
                        <div class="w-10 h-10 rounded-xl bg-pink-50 text-aybisa-pink flex items-center justify-center font-bold mb-4">
                            <svg class="w-5 h-5 text-aybisa-pink" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        </div>
                        <h4 class="text-base font-bold text-aybisa-navy mb-1.5">Guru &amp; Wali Kelas</h4>
                        <p class="text-xs text-aybisa-gray leading-relaxed">
                            Pencatatan absensi jam KBM, pemantauan ketidakhadiran siswa binaan, self-service presensi GPS, dan unduh rekap kelas.
                        </p>
                    </div>

                    <!-- Role 5: Staf Akademik -->
                    <div class="p-6 rounded-2xl border border-slate-200 bg-white hover:border-aybisa-blue transition-all shadow-2xs hover:shadow-md">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-aybisa-blue flex items-center justify-center font-bold mb-4">
                            <svg class="w-5 h-5 text-aybisa-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                        </div>
                        <h4 class="text-base font-bold text-aybisa-navy mb-1.5">Staf Akademik</h4>
                        <p class="text-xs text-aybisa-gray leading-relaxed">
                            Penyusunan jadwal pelajaran, distribusi ruang kelas, sinkronisasi modul evaluasi belajar, dan jadwal jam mengajar.
                        </p>
                    </div>

                    <!-- Role 6: Siswa & Orang Tua -->
                    <div class="p-6 rounded-2xl border border-slate-200 bg-white hover:border-slate-400 transition-all shadow-2xs hover:shadow-md">
                        <div class="w-10 h-10 rounded-xl bg-slate-100 text-aybisa-navy flex items-center justify-center font-bold mb-4">
                            <svg class="w-5 h-5 text-aybisa-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                        </div>
                        <h4 class="text-base font-bold text-aybisa-navy mb-1.5">Siswa &amp; Wali Murid</h4>
                        <p class="text-xs text-aybisa-gray leading-relaxed">
                            Akses riwayat kehadiran pribadi, jadwal pelajaran harian, agenda kalender akademik, dan materi belajar online.
                        </p>
                    </div>

                </div>

            </div>
        </section>

        <!-- Section 6: FAQ Accordion -->
        <section id="faq" class="py-20 sm:py-28 bg-aybisa-pale/40 border-t border-slate-100">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="text-center mb-16">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-white text-xs font-bold text-aybisa-blue border border-blue-200 mb-3">
                        FAQ
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-aybisa-navy tracking-tight mb-4">
                        Pertanyaan yang Sering Diajukan
                    </h2>
                    <p class="text-base text-aybisa-gray leading-relaxed">
                        Informasi praktis seputar implementasi dan pengoperasian SMART+ di sekolah.
                    </p>
                </div>

                <div class="space-y-4">
                    
                    <!-- FAQ 1 -->
                    <div class="rounded-2xl border border-slate-200 overflow-hidden transition-colors" :class="activeFaq === 1 ? 'border-aybisa-blue bg-white' : 'bg-white/80'">
                        <button @click="activeFaq = (activeFaq === 1 ? null : 1)" class="w-full px-6 py-5 text-left flex justify-between items-center gap-4 focus:outline-none">
                            <span class="text-base font-bold text-aybisa-navy">Apakah SMART+ memerlukan perangkat keras scanner khusus yang mahal?</span>
                            <span class="text-xl font-bold text-aybisa-blue" x-text="activeFaq === 1 ? '-' : '+'"></span>
                        </button>
                        <div x-show="activeFaq === 1" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="px-6 pb-5 text-sm text-aybisa-gray leading-relaxed">
                            Tidak. SMART+ dirancang adaptif. Anda cukup menggunakan komputer atau laptop yang sudah ada di sekolah dan menghubungkan USB RFID Reader standar plug-and-play. Untuk kebutuhan terminal permanen di gerbang, Aybisa Studio juga menyediakan opsi terminal Kiosk siap pakai.
                        </div>
                    </div>

                    <!-- FAQ 2 -->
                    <div class="rounded-2xl border border-slate-200 overflow-hidden transition-colors" :class="activeFaq === 2 ? 'border-aybisa-blue bg-white' : 'bg-white/80'">
                        <button @click="activeFaq = (activeFaq === 2 ? null : 2)" class="w-full px-6 py-5 text-left flex justify-between items-center gap-4 focus:outline-none">
                            <span class="text-base font-bold text-aybisa-navy">Bagaimana jika ada siswa yang lupa membawa kartu RFID fisiknya?</span>
                            <span class="text-xl font-bold text-aybisa-blue" x-text="activeFaq === 2 ? '-' : '+'"></span>
                        </button>
                        <div x-show="activeFaq === 2" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="px-6 pb-5 text-sm text-aybisa-gray leading-relaxed">
                            Guru piket atau admin tata usaha dapat melakukan presensi manual melalui dashboard dengan mencari nama siswa tersebut. Jika kartu hilang, kartu RFID baru dapat didaftarkan kembali dalam waktu kurang dari satu menit.
                        </div>
                    </div>

                    <!-- FAQ 3 -->
                    <div class="rounded-2xl border border-slate-200 overflow-hidden transition-colors" :class="activeFaq === 3 ? 'border-aybisa-blue bg-white' : 'bg-white/80'">
                        <button @click="activeFaq = (activeFaq === 3 ? null : 3)" class="w-full px-6 py-5 text-left flex justify-between items-center gap-4 focus:outline-none">
                            <span class="text-base font-bold text-aybisa-navy">Bagaimana guru melakukan presensi saat tugas luar atau dinas?</span>
                            <span class="text-xl font-bold text-aybisa-blue" x-text="activeFaq === 3 ? '-' : '+'"></span>
                        </button>
                        <div x-show="activeFaq === 3" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="px-6 pb-5 text-sm text-aybisa-gray leading-relaxed">
                            SMART+ menyediakan fitur pengajuan izin tugas luar atau dinas di portal guru. Kepala sekolah dapat memverifikasi dan menyetujui dispensasi tersebut, sehingga kehadiran guru tetap tercatat resmi dan akuntabel.
                        </div>
                    </div>

                    <!-- FAQ 4 -->
                    <div class="rounded-2xl border border-slate-200 overflow-hidden transition-colors" :class="activeFaq === 4 ? 'border-aybisa-blue bg-white' : 'bg-white/80'">
                        <button @click="activeFaq = (activeFaq === 4 ? null : 4)" class="w-full px-6 py-5 text-left flex justify-between items-center gap-4 focus:outline-none">
                            <span class="text-base font-bold text-aybisa-navy">Apakah laporan rekap absensi dapat disesuaikan dengan format Dinas Pendidikan?</span>
                            <span class="text-xl font-bold text-aybisa-blue" x-text="activeFaq === 4 ? '-' : '+'"></span>
                        </button>
                        <div x-show="activeFaq === 4" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="px-6 pb-5 text-sm text-aybisa-gray leading-relaxed">
                            Tentu. Format rekapitulasi kehadiran dapat diekspor langsung ke spreadsheet Excel (.xlsx) dan dokumen cetak PDF resmi dengan data izin, sakit, terlambat, alpa, dan persentase kehadiran baku.
                        </div>
                    </div>

                </div>

            </div>
        </section>

        <!-- Section 7: Call To Action with WhatsApp Live Demo -->
        <section class="py-16 sm:py-24 bg-white border-t border-slate-100">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="rounded-3xl bg-aybisa-navy text-white p-8 sm:p-12 relative overflow-hidden shadow-2xl text-center">
                    
                    <!-- 5-Color Rainbow Top Accent Strip -->
                    <div class="absolute top-0 left-0 right-0 h-2 flex">
                        <span class="w-1/5 bg-[#1685E8]"></span>
                        <span class="w-1/5 bg-[#F32635]"></span>
                        <span class="w-1/5 bg-[#FFB81C]"></span>
                        <span class="w-1/5 bg-[#269B52]"></span>
                        <span class="w-1/5 bg-[#E83F83]"></span>
                    </div>

                    <div class="max-w-2xl mx-auto">
                        <span class="text-xs font-bold uppercase tracking-wider text-aybisa-blue-light block mb-3">SCHEDULE A DEMO</span>
                        <h2 class="text-2xl sm:text-4xl font-extrabold text-white mb-4 leading-tight">
                            Jadwalkan Demo &amp; Uji Coba SMART+ untuk Sekolah Anda
                        </h2>
                        <p class="text-sm sm:text-base text-slate-300 leading-relaxed mb-8">
                            Diskusikan kebutuhan sekolah atau yayasan Anda bersama tim Aybisa Studio. Kami siap memberikan sesi demonstrasi interaktif dan konsultasi implementasi sistem.
                        </p>
                        
                        <div class="flex flex-wrap justify-center items-center gap-4">
                            <!-- WhatsApp CTA -->
                            <a href="{{ $waUrl }}" target="_blank" class="inline-flex items-center justify-center gap-2.5 rounded-xl bg-emerald-600 px-7 py-4 text-base font-bold text-white shadow-lg shadow-emerald-600/30 hover:bg-emerald-700 hover:shadow-xl hover:-translate-y-0.5 transition-all">
                                <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.007c.107.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.173.086.275.072.376-.044.101-.116.433-.506.549-.68.116-.174.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.043.073.043.419-.101.825z"/></svg>
                                WhatsApp Demo (+62 851-2250-2293)
                            </a>
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl bg-aybisa-blue px-6 py-4 text-sm font-bold text-white shadow-md hover:bg-aybisa-blue-hover transition-all">
                                Sign In Portal
                            </a>
                        </div>
                    </div>

                </div>

            </div>
        </section>
    </main>

    <!-- Floating WhatsApp Action Button (FAB) Pin to Bottom Right -->
    <a href="{{ $waUrl }}" target="_blank" 
       class="fixed bottom-6 right-6 z-50 inline-flex items-center gap-2.5 rounded-full bg-emerald-600 text-white px-5 py-3.5 shadow-2xl hover:bg-emerald-700 transition-all hover:scale-105 group border-2 border-white/80" 
       title="Hubungi WhatsApp untuk Demo SMART+">
        <span class="relative flex h-3 w-3">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
            <span class="relative inline-flex rounded-full h-3 w-3 bg-white"></span>
        </span>
        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.007c.107.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.173.086.275.072.376-.044.101-.116.433-.506.549-.68.116-.174.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.043.073.043.419-.101.825z"/></svg>
        <span class="text-xs font-extrabold tracking-wide hidden sm:inline">Request Demo</span>
    </a>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 pt-16 pb-12 text-sm text-aybisa-gray">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="grid grid-cols-1 md:grid-cols-12 gap-10 pb-12 border-b border-slate-200">
                
                <!-- Col 1: Brand Info -->
                <div class="md:col-span-5 space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-aybisa-blue text-white font-black text-lg shadow-sm">
                            S+
                        </div>
                        <div>
                            <span class="text-xl font-extrabold text-aybisa-navy tracking-tight block leading-none">SMART<span class="text-aybisa-blue">+</span></span>
                            <span class="text-[11px] font-semibold text-aybisa-gray">School Management Attendance &amp; Reporting Technologies</span>
                        </div>
                    </div>
                    
                    <p class="text-xs text-aybisa-gray leading-relaxed max-w-sm">
                        Platform presensi digital dan manajemen akademik terpadu untuk efisiensi tata usaha, guru, dan evaluasi pimpinan sekolah di {{ $namaSekolah }}.
                    </p>

                    <!-- Aybisa 5-Color Line -->
                    <div class="flex items-center gap-1.5 pt-1">
                        <span class="h-1.5 w-6 rounded-full bg-[#1685E8]"></span>
                        <span class="h-1.5 w-4 rounded-full bg-[#F32635]"></span>
                        <span class="h-1.5 w-4 rounded-full bg-[#FFB81C]"></span>
                        <span class="h-1.5 w-4 rounded-full bg-[#269B52]"></span>
                        <span class="h-1.5 w-4 rounded-full bg-[#E83F83]"></span>
                    </div>

                    <div class="pt-2 text-xs font-semibold text-aybisa-navy">
                        Product by <strong class="text-aybisa-blue">Aybisa Studio</strong> &bull; <span class="italic text-slate-500">&ldquo;Building the Digital &amp; Creative Future&rdquo;</span>
                    </div>
                </div>

                <!-- Col 2: Core Modules -->
                <div class="md:col-span-3 space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-aybisa-navy">Core Modules</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="#fitur" class="hover:text-aybisa-blue transition-colors">RFID Card Kiosk</a></li>
                        <li><a href="#fitur" class="hover:text-aybisa-blue transition-colors">GPS Geofencing</a></li>
                        <li><a href="#demo-interaktif" class="hover:text-aybisa-blue transition-colors">Automated Reporting</a></li>
                        <li><a href="#demo-interaktif" class="hover:text-aybisa-blue transition-colors">Academic Scheduling</a></li>
                        <li><a href="#role-portal" class="hover:text-aybisa-blue transition-colors">Multi-Role Portal</a></li>
                        <li><a href="#faq" class="hover:text-aybisa-blue transition-colors">FAQ</a></li>
                    </ul>
                </div>

                <!-- Col 3: Direct Access & WhatsApp Demo -->
                <div class="md:col-span-4 space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-aybisa-navy">Demo &amp; Direct Access</h4>
                    <ul class="space-y-2 text-xs">
                        <li>
                            <a href="{{ $waUrl }}" target="_blank" class="text-emerald-600 font-bold hover:underline flex items-center gap-1.5">
                                <svg class="w-4 h-4 fill-emerald-600" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.007c.107.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.173.086.275.072.376-.044.101-.116.433-.506.549-.68.116-.174.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.043.073.043.419-.101.825z"/></svg>
                                WhatsApp: +62 851-2250-2293 &rarr;
                            </a>
                        </li>
                        <li><a href="{{ route('login') }}" class="text-aybisa-blue font-bold hover:underline">Sign In ke Portal Pengguna &rarr;</a></li>
                        <li><a href="{{ route('kiosk.index') }}" target="_blank" class="hover:text-aybisa-blue transition-colors">Buka Terminal Kiosk Mode &rarr;</a></li>
                        <li class="pt-2 text-slate-500 text-[11px] leading-relaxed">
                            Live School Implementation: <strong class="text-aybisa-navy">{{ $namaSekolah }}</strong><br>
                            Kurikulum Merdeka &amp; E-Rapor Compliant.
                        </li>
                    </ul>
                </div>

            </div>

            <!-- Bottom Copyright & Credit -->
            <div class="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
                <div>
                    &copy; {{ date('Y') }} <strong>SMART+</strong>. Hak Cipta Dilindungi Undang-Undang.
                </div>
                <div class="flex items-center gap-2">
                    <span>An Official Product of</span>
                    <strong class="font-bold text-aybisa-navy">Aybisa Studio</strong>
                </div>
            </div>

        </div>
    </footer>

</body>
</html>
