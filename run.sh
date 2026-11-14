#!/usr/bin/env bash
# Quick start script for macOS and Linux users

echo "========================================================"
echo "  Starting Authentic Cuisine Class Web Application"
echo "========================================================"
echo ""

if ! command -v php &> /dev/null; then
    echo "[ERROR] PHP is not installed or not in PATH."
    echo "Please install PHP (e.g., sudo apt install php php-mysql, or brew install php)."
    exit 1
fi

echo "[INFO] Starting PHP development server..."
echo "[INFO] App URL: http://127.0.0.1:8000"
echo "Press Ctrl+C to stop the server."
echo ""

if command -v xdg-open &> /dev/null; then
    xdg-open "http://127.0.0.1:8000" > /dev/null 2>&1 &
elif command -v open &> /dev/null; then
    open "http://127.0.0.1:8000" > /dev/null 2>&1 &
fi

php -S 127.0.0.1:8000
