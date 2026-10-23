# Docker Setup for QR Payment Feature

## What's Installed in Docker

The Dockerfile now includes:
- ✅ Python 3 
- ✅ pip (Python package manager)
- ✅ python3-venv (virtual environment)
- ✅ libzbar0 (QR/barcode reading library)
- ✅ Pillow (Python imaging library)
- ✅ pyzbar (Python wrapper for zbar)

## Virtual Environment Location

Python packages are installed in: `/opt/venv`

This avoids the "externally-managed-environment" error in Ubuntu 22.04+.

## Rebuild Docker Container

After updating the Dockerfile, rebuild your container:

```bash
# Stop running containers
docker-compose down

# Rebuild the container
docker-compose build --no-cache

# Start containers
docker-compose up -d
```

Or if using Makefile:
```bash
make down
make build
make up
```

## Test QR Extraction Inside Container

```bash
# Enter the container
docker-compose exec app bash

# Test Python is available
python3 --version

# Test pyzbar is installed
python3 -c "from pyzbar.pyzbar import decode; print('pyzbar OK')"

# Test the extraction script
python3 scripts/extract_qr.py /path/to/test-qr.png
```

## How It Works

When Laravel runs `exec()` command to extract QR codes:
1. It calls `python3 scripts/extract_qr.py <image_path>`
2. The shebang `#!/opt/venv/bin/python3` ensures it uses the venv
3. The virtual environment has Pillow and pyzbar installed
4. Script extracts and returns QR code text

## Storage Directories

Make sure these directories exist and are writable:
```bash
storage/app/public/qr_images/
storage/app/public/qr_texts/
```

Run inside container:
```bash
mkdir -p storage/app/public/qr_images
mkdir -p storage/app/public/qr_texts
chown -R www-data:www-data storage/app/public/qr_images
chown -R www-data:www-data storage/app/public/qr_texts
chmod -R 775 storage/app/public/qr_images
chmod -R 775 storage/app/public/qr_texts
```

## Troubleshooting

### If Python command not found:
```bash
# Check if venv is in PATH
echo $PATH | grep venv

# Manually use venv python
/opt/venv/bin/python3 scripts/extract_qr.py image.png
```

### If pyzbar not found:
```bash
# Install in venv
/opt/venv/bin/pip install Pillow pyzbar
```

### Test QR extraction manually:
```bash
# Inside container
cd /var/www
python3 scripts/extract_qr.py /path/to/qr-image.png
```

## Production Deployment

For production VPS (without Docker):
1. Install system packages: `sudo apt-get install python3 python3-venv libzbar0`
2. Create venv: `python3 -m venv /opt/venv`
3. Install packages: `/opt/venv/bin/pip install Pillow pyzbar`
4. Update PATH or use full path in extract_qr.py shebang
