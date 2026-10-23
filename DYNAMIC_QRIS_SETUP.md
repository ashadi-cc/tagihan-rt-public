# Dynamic QRIS Setup Instructions

## Quick Start

### 1. Install Python Dependencies (In Docker Container)

```bash
# Enter your Docker container
docker exec -it <your-container-name> bash

# Run installation script
bash /var/www/html/scripts/install_qrcode.sh

# OR install manually
/opt/venv/bin/pip install qrcode[pil]
```

### 2. Create Cache Directory

```bash
# Inside Docker container
mkdir -p /var/www/html/storage/app/public/qr_cache
chmod 755 /var/www/html/storage/app/public/qr_cache
```

### 3. Test Dynamic QRIS Generation

```bash
# Find a QRIS text file
ls /var/www/html/storage/app/public/qr_texts/

# Test generation (replace with your actual file)
/opt/venv/bin/python3 /var/www/html/scripts/generate_dynamic_qris.py \
    /var/www/html/storage/app/public/qr_texts/qris_xxxxx.txt \
    150000 \
    /tmp/test_qris.png

# Check output
file /tmp/test_qris.png
# Should show: PNG image data
```

### 4. Register Cleanup Command

The command is already created. You can run it manually:

```bash
php artisan qris:cleanup-cache
```

To schedule it, add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('qris:cleanup-cache --hours=24')
             ->dailyAt('02:00');
}
```

## What's New

### Files Created
1. **scripts/generate_dynamic_qris.py** - Python script to generate dynamic QRIS with amount
2. **scripts/install_qrcode.sh** - Installation helper script
3. **app/Console/Commands/CleanupQrisCacheCommand.php** - Cache cleanup command
4. **DYNAMIC_QRIS_IMPLEMENTATION.md** - Complete technical documentation

### Files Modified
1. **scripts/requirements.txt** - Added `qrcode[pil]>=7.4.2`
2. **app/Http/Controllers/HomeController.php** - Implemented file-based dynamic QRIS generation
3. **resources/views/confirm_payment_form.blade.php** - Added `type` parameter to QR image URL

## How It Works

### Flow
```
User requests payment
    ↓
HomeController checks for cached QR
    ↓
If cached & fresh (<1hr) → Serve cache
    ↓
If expired/missing → Generate new QR
    ↓
Python script modifies QRIS string with amount
    ↓
QR code image generated & cached
    ↓
Image served to user
```

### Cache Naming
```
qris_dynamic_{payment_id}_{user_id}_{type}_{amount}.png

Example:
qris_dynamic_5_123_current_billing_150000.png
         │    │   │               │
         │    │   │               └─ Amount (Rp 150,000)
         │    │   └───────────────── Type (current_billing/previous_billing)
         │    └───────────────────── User ID
         └────────────────────────── Payment Method ID
```

### Benefits Over Laravel Cache
- ✅ Persistent across container restarts
- ✅ Visual inspection (can open PNG files)
- ✅ Easy debugging (check file timestamps)
- ✅ No cache driver configuration needed
- ✅ Disk space visible and manageable

## Testing

### 1. Test Static QRIS (existing functionality)
1. Login as user
2. Go to payment confirmation
3. Select a non-dynamic QRIS payment
4. Should show uploaded QR image

### 2. Test Dynamic QRIS
1. Create payment method with dynamic QRIS enabled
2. Set fee (e.g., 2% or Rp 5,000)
3. Login as user
4. Confirm payment for current billing
5. Should see QR with total amount = billing + fee
6. Scan QR with mobile app - amount should match

### 3. Test Caching
1. View dynamic QR (note timestamp)
2. Refresh page immediately - should serve cached (fast)
3. Check cache file:
   ```bash
   ls -lh storage/app/public/qr_cache/
   ```
4. Delete cache file, refresh - should regenerate
5. Wait 1+ hour, refresh - should regenerate

### 4. Test Cleanup
```bash
# Create some test cache files
touch storage/app/public/qr_cache/qris_dynamic_test_old.png

# Make it old (48 hours ago)
touch -t 202401010000 storage/app/public/qr_cache/qris_dynamic_test_old.png

# Run cleanup
php artisan qris:cleanup-cache --hours=24

# Should delete old file
```

## Troubleshooting

### "qrcode module not found"
```bash
# Install in Docker container
docker exec -it <container> bash
/opt/venv/bin/pip install qrcode[pil]
```

### "Permission denied" on cache directory
```bash
chmod 755 storage/app/public/qr_cache
chown www-data:www-data storage/app/public/qr_cache
```

### QR generation fails
```bash
# Check Python script
/opt/venv/bin/python3 scripts/generate_dynamic_qris.py --help

# Check logs
tail -f storage/logs/laravel.log
```

### Wrong amount in QR
- Verify fee calculation in `confirmPaymentForm`
- Check Tag 54 in generated QRIS:
  ```bash
  /opt/venv/bin/python3 scripts/extract_qr.py cached_qr.png | grep "54"
  ```

## Next Steps

1. ✅ Install qrcode library in Docker
2. ✅ Create cache directory
3. ✅ Test dynamic QRIS generation
4. [ ] Schedule cleanup command (optional)
5. [ ] Monitor disk usage
6. [ ] Test with real QRIS scanners

## Support

For detailed technical documentation, see:
- `DYNAMIC_QRIS_IMPLEMENTATION.md` - Complete technical guide
- `QR_PAYMENT_IMPLEMENTATION.md` - Admin setup guide
- `QRIS_PAYMENT_FLOW.md` - User flow documentation
