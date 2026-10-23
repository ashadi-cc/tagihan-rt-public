#!/bin/bash

# QR Code Extraction Setup Script
# This script helps set up the QR code extraction environment

echo "=========================================="
echo "QR Code Extraction Setup"
echo "=========================================="
echo ""

# Check Python version
echo "Checking Python installation..."
if command -v python3 &> /dev/null; then
    PYTHON_VERSION=$(python3 --version)
    echo "✓ Found: $PYTHON_VERSION"
else
    echo "✗ Python 3 not found. Please install Python 3.7 or higher."
    exit 1
fi

# Check pip
echo "Checking pip installation..."
if command -v pip3 &> /dev/null; then
    echo "✓ pip3 is installed"
else
    echo "✗ pip3 not found. Installing..."
    sudo apt-get update
    sudo apt-get install -y python3-pip
fi

# Install zbar library
echo ""
echo "Installing zbar library..."
if [[ "$OSTYPE" == "linux-gnu"* ]]; then
    echo "Detected Linux system"
    if command -v apt-get &> /dev/null; then
        sudo apt-get update
        sudo apt-get install -y libzbar0
        echo "✓ zbar installed via apt-get"
    elif command -v yum &> /dev/null; then
        sudo yum install -y zbar
        echo "✓ zbar installed via yum"
    else
        echo "⚠ Please install zbar manually for your Linux distribution"
    fi
elif [[ "$OSTYPE" == "darwin"* ]]; then
    echo "Detected macOS system"
    if command -v brew &> /dev/null; then
        brew install zbar
        echo "✓ zbar installed via homebrew"
    else
        echo "⚠ Please install Homebrew first, then run: brew install zbar"
    fi
else
    echo "⚠ Unknown OS. Please install zbar manually."
fi

# Install Python dependencies
echo ""
echo "Installing Python dependencies..."
cd "$(dirname "$0")"
pip3 install -r requirements.txt
if [ $? -eq 0 ]; then
    echo "✓ Python dependencies installed successfully"
else
    echo "✗ Failed to install Python dependencies"
    exit 1
fi

# Test the extraction script
echo ""
echo "=========================================="
echo "Setup completed!"
echo "=========================================="
echo ""
echo "You can test the QR extraction with:"
echo "  python3 extract_qr.py /path/to/your/qr-code.png"
echo ""
