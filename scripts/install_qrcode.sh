#!/bin/bash

# Install qrcode library in Docker container
# Run this script to add qrcode support for dynamic QRIS generation

echo "Installing qrcode library in Python virtual environment..."

# Check if running in Docker
if [ -f "/.dockerenv" ]; then
    # Install qrcode in venv
    /opt/venv/bin/pip install qrcode[pil]
    
    echo "✓ qrcode library installed successfully"
    echo ""
    echo "Testing installation..."
    /opt/venv/bin/python3 -c "import qrcode; qr = qrcode.QRCode(); print('✓ qrcode module working correctly')"
else
    echo "This script should be run inside the Docker container."
    echo "Run: docker exec -it <container-name> bash -c 'bash /var/www/html/scripts/install_qrcode.sh'"
fi
