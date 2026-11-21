#!/bin/bash
set -e

echo "========================================================"
echo "  SMP AL-MADINAH - Menjalankan Semua Sistem (Docker)"
echo "  [ SMART+ & SMART-CBT ]"
echo "========================================================"
echo ""

# 1. Pastikan .env pada smart+ tersedia
if [ ! -f "smart+/.env" ]; then
    echo "[*] Menyiapkan smart+/.env dari .env.docker..."
    cp "smart+/.env.docker" "smart+/.env"
fi

# 2. Pastikan .env pada smart-cbt tersedia
if [ ! -f "smart-cbt/.env" ]; then
    echo "[*] Menyiapkan smart-cbt/.env dari .env.docker..."
    cp "smart-cbt/.env.docker" "smart-cbt/.env"
fi

echo "[*] Menjalankan build & starting seluruh container..."
docker compose up -d --build

echo ""
echo "[*] Melakukan optimasi & storage link..."
docker compose exec smart-app php artisan storage:link --force || true
docker compose exec smart-cbt-app php artisan storage:link --force || true

echo ""
echo "========================================================"
echo "  SEMUA CONTAINER BERHASIL DIAKTIFKAN!"
echo "========================================================"
echo ""
echo "  1. SMART+ (Aplikasi Sekolah & E-Rapor) : http://localhost:8000"
echo "  2. SMART-CBT (Sistem Ujian Online)      : http://localhost:8001"
echo "  3. Database phpMyAdmin (MySQL)          : http://localhost:8080"
echo "  4. Log Monitor (Dozzle)                 : http://localhost:9999"
echo "========================================================"
echo ""
