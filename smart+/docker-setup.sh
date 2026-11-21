#!/bin/bash
set -e

echo "========================================"
echo "  SMART - Docker Setup & Launch Script"
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

echo "[2/4] Melakukan build image Docker..."
docker compose build
echo "   OK - Build selesai"
echo ""

echo "[3/4] Menjalankan seluruh container SMART..."
docker compose up -d
echo "   OK - Container berjalan"
echo ""

echo "[4/4] Sinkronisasi penyimpanan dan optimasi aplikasi..."
docker compose exec smart-app php artisan storage:link --force || true
docker compose exec smart-app php artisan optimize:clear || true
echo "   OK - Aplikasi siap digunakan"
echo ""

echo "========================================"
echo "  DOCKER SMART BERHASIL DIAKTIFKAN!"
echo "========================================"
echo ""
echo "  Aplikasi SMART (Web) : http://localhost:8000"
echo "  Database phpMyAdmin  : http://localhost:8080"
echo "  Monitoring Dozzle    : http://localhost:9999"
echo ""
