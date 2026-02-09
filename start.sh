#!/bin/bash

# Quick Start Script for Recipe Website (Unified)

echo "🚀 Starting Recipe Website..."
echo ""

# Function to cleanup on exit
cleanup() {
    echo ""
    echo "🛑 Shutting down servers..."
    kill $LARAVEL_PID $VITE_PID 2>/dev/null
    exit 0
}

trap cleanup SIGINT SIGTERM

# Start Laravel backend
echo "📦 Starting Laravel Server..."
php artisan serve > /dev/null 2>&1 &
LARAVEL_PID=$!

# Wait a moment for Laravel to start
sleep 2

# Check if Laravel started successfully
if ! curl -s http://127.0.0.1:8000/api/categories > /dev/null 2>&1; then
    echo "❌ Failed to start Laravel backend"
    # Try one more time with visible output if failed
    php artisan serve &
    LARAVEL_PID=$!
    sleep 3
fi

echo "✅ Laravel running at http://127.0.0.1:8000"

# Start Vite (Frontend)
echo "⚡ Starting Vite Dev Server..."
npm run dev > /dev/null 2>&1 &
VITE_PID=$!

# Wait a moment for Vite to start
sleep 2

echo "✅ Vite running..."
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🎉 Application is running!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📍 App URL:        http://127.0.0.1:8000"
echo "   (Laravel serves the React app via Vite)"
echo ""
echo "🔗 API Endpoints:"
echo "   - Categories:   http://127.0.0.1:8000/api/categories"
echo ""
echo "Press Ctrl+C to stop servers"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Keep script running
wait
