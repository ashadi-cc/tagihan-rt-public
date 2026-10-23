# QR Code Extraction Setup

This directory contains scripts for QR code extraction from uploaded images using Python and pyzbar.

## Requirements

### Python with pyzbar

1. Install Python 3.7 or higher
2. Install required Python packages:
   ```bash
   pip3 install -r requirements.txt
   ```

3. Install zbar library (required by pyzbar):
   
   **Ubuntu/Debian:**
   ```bash
   sudo apt-get install libzbar0
   ```
   
   **macOS:**
   ```bash
   brew install zbar
   ```
   
   **CentOS/RHEL:**
   ```bash
   sudo yum install zbar
   ```

## Quick Setup

Run the automated setup script:
```bash
./setup.sh
```

## Usage

The Laravel application will automatically use Python with pyzbar when processing QR payment uploads.

## Testing

Test the extraction script manually:
```bash
python3 extract_qr.py /path/to/qr-code-image.png
```

This should output the decoded text from the QR code.
