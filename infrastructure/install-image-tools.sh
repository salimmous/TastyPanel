#!/bin/bash
# Install image optimization tools

echo "📦 Installing image optimization tools..."

# Detect OS
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS=$ID
else
    OS=$(uname -s)
fi

case $OS in
    ubuntu|debian)
        echo "🐧 Detected Ubuntu/Debian"
        sudo apt update
        
        # Install libavif for AVIF support
        sudo apt install -y libavif-bin libavif-dev
        
        # Install WebP tools
        sudo apt install -y webp libwebp-dev
        
        # Install ImageMagick with AVIF support
        sudo apt install -y imagemagick libmagickwand-dev
        
        # Install PHP extensions
        sudo apt install -y php-imagick php-gd
        ;;
        
    centos|rhel|fedora)
        echo "🎩 Detected CentOS/RHEL/Fedora"
        sudo yum install -y epel-release
        sudo yum install -y libavif libavif-tools
        sudo yum install -y libwebp libwebp-tools
        sudo yum install -y ImageMagick ImageMagick-devel
        sudo yum install -y php-imagick php-gd
        ;;
        
    Darwin)
        echo "🍎 Detected macOS"
        brew install libavif
        brew install webp
        brew install imagemagick
        pecl install imagick
        ;;
        
    *)
        echo "❌ Unsupported OS: $OS"
        exit 1
        ;;
esac

# Verify installations
echo ""
echo "✅ Verifying installations..."

if command -v avifenc &> /dev/null; then
    echo "  ✓ AVIF encoder installed"
    avifenc --version
else
    echo "  ✗ AVIF encoder not found"
fi

if command -v cwebp &> /dev/null; then
    echo "  ✓ WebP encoder installed"
    cwebp -version
else
    echo "  ✗ WebP encoder not found"
fi

if command -v convert &> /dev/null; then
    echo "  ✓ ImageMagick installed"
    convert -version | head -n 1
else
    echo "  ✗ ImageMagick not found"
fi

# Install Intervention Image (Laravel package)
echo ""
echo "📦 Installing Intervention Image for Laravel..."
cd "$(dirname "$0")/.." || exit
composer require intervention/image

echo ""
echo "✅ Image optimization tools setup complete!"
echo ""
echo "🎨 You can now use:"
echo "   - AVIF conversion (70% smaller than JPG)"
echo "   - WebP conversion (fallback)"
echo "   - Automatic optimization on upload"
