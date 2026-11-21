#!/bin/bash
set -e

echo "========================================"
echo "  SMART-CBT - Docker Setup & Launch"
echo "========================================"
echo ""

echo "[1/4] Memeriksa file konfigurasi (.env)..."
if [ ! -f .env ]; then
    cp .env.docker .env
    echo "   OK - File .env dibuat dari template .env.docker"
else
    echo "   OK - File .env sudah ada"
fi
echo ""

echo "[2/4] Melakukan build image Docker SMART-CBT..."
docker compose build
echo "   OK - Build selesai"
echo ""

echo "[3/4] Menjalankan seluruh container SMART-CBT..."
docker compose up -d
echo "   OK - Container berjalan"
echo ""

echo "[4/4] Sinkronisasi penyimpanan dan optimasi aplikasi..."
docker compose exec smart-cbt-app php artisan storage:link --force || true
docker compose exec smart-cbt-app php artisan optimize:clear || true
echo "   OK - SMART-CBT siap digunakan"
echo ""

echo "========================================"
echo "  DOCKER SMART-CBT BERHASIL DIAKTIFKAN!"
echo "========================================"
echo ""
echo "  Aplikasi SMART-CBT : http://localhost:8001"
echo "  Scheduler Token    : Berjalan otomatis di container smart-cbt-scheduler"
echo ""
