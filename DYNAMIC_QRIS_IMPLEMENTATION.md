# Dynamic QRIS Implementation Guide

## Overview
This document describes the implementation of dynamic QRIS (Quick Response Indonesian Standard) payment feature that generates unique QR codes with embedded transaction amounts.

## Architecture

### File-Based Caching System
Instead of using Laravel's Cache facade, we use a file-based caching mechanism for better control and persistence:

**Cache Directory:** `storage/app/public/qr_cache/`

**Cache File Naming:**
```
qris_dynamic_{payment_id}_{user_id}_{type}_{amount}.png
```

**Parameters:**
- `payment_id`: Payment method ID
- `user_id`: User ID making the payment
- `type`: Billing type (`current_billing` or `previous_billing`)
- `amount`: Transaction amount in Rupiah (including fees)

**Example:**
```
qris_dynamic_5_123_current_billing_150000.png
```

### Cache Lifecycle
1. **Cache Duration:** 1 hour (3600 seconds)
2. **Validation:** Checks file modification time before serving
3. **Regeneration:** Automatically regenerates if cache expired or missing
4. **Cleanup:** Manual cleanup via Artisan command

## QRIS Format (EMV Standard)

### Structure
QRIS follows the EMV QR Code specification with Tag-Length-Value (TLV) format:

```
[Tag][Length][Value]
```

### Key Tags
- **Tag 00:** Payload Format Indicator (value: "01")
- **Tag 01:** Point of Initiation Method
- **Tag 26-51:** Merchant Account Information
- **Tag 52:** Merchant Category Code (MCC)
- **Tag 53:** Transaction Currency (360 for IDR)
- **Tag 54:** Transaction Amount (our modification point)
- **Tag 58:** Country Code ("ID")
- **Tag 62:** Additional Data Field Template
- **Tag 63:** CRC16 Checksum (last 4 hex characters)

### Tag 54 Format (Transaction Amount)
```
54[Length][Amount]

Examples:
- Rp 100,000: 54091000.00
- Rp 1,500,000: 541015000.00
- Rp 25,000: 540825000.00

Length Calculation:
- Amount formatted as "XXXXX.00" (always 2 decimal places)
- Length = number of characters in formatted amount
- Expressed as 2-digit decimal (padded with 0)
```

### CRC16 Calculation
QRIS uses CRC-16-CCITT algorithm:
- **Polynomial:** 0x1021
- **Initial Value:** 0xFFFF
- **Input:** Entire QRIS string excluding CRC value, plus "6304"
- **Output:** 4-character uppercase hexadecimal

## Python Script: `generate_dynamic_qris.py`

### Purpose
Modifies QRIS string with transaction amount and generates QR code image.

### Usage
```bash
/opt/venv/bin/python3 scripts/generate_dynamic_qris.py \
    <qris_text_file> \
    <amount> \
    <output_image_path>
```

### Arguments
1. **qris_text_file:** Path to file containing original QRIS string
2. **amount:** Transaction amount in Rupiah (integer)
3. **output_image_path:** Path to save generated QR code PNG image

### Example
```bash
/opt/venv/bin/python3 scripts/generate_dynamic_qris.py \
    storage/app/public/qr_texts/qris_12345.txt \
    150000 \
    storage/app/public/qr_cache/qris_dynamic_5_123_current_150000.png
```

### Dependencies
Install via pip in virtual environment:
```bash
/opt/venv/bin/pip install -r scripts/requirements.txt
```

Required packages:
- `Pillow>=9.0.0` - Image processing
- `pyzbar>=0.1.9` - QR code reading (for extraction)
- `qrcode[pil]>=7.4.2` - QR code generation

### Algorithm Steps

1. **Read Original QRIS String**
   - Load from text file stored during payment method creation

2. **Remove Existing Tag 54**
   - Pattern: `54\d{2}[\d.]+`
   - Ensures no duplicate amount tags

3. **Format Transaction Amount**
   - Convert integer to decimal: `150000` → `"150000.00"`
   - Calculate length: `len("150000.00")` = 9
   - Build tag: `"54" + "09" + "150000.00"` = `"54091500.00"`

4. **Insert Tag 54**
   - **Preferred:** After Tag 53 (Transaction Currency)
   - **Fallback 1:** Before Tag 58 (Country Code)
   - **Fallback 2:** Before Tag 63 (CRC, last 8 chars)

5. **Recalculate CRC16**
   - Remove old CRC (pattern: `6304[0-9A-F]{4}$`)
   - Calculate new CRC on: `modified_qris + "6304"`
   - Append: `"6304" + calculated_crc`

6. **Generate QR Image**
   - Use `qrcode` library
   - Error correction: Medium (M)
   - Box size: 10 pixels
   - Border: 4 boxes
   - Format: PNG

## Controller: HomeController@serveQrisImage

### Request Parameters
```php
?payment_id=5&amount=150000&type=current_billing
```

### Flow Diagram
```
Request
  ↓
Check if Dynamic QRIS
  ↓
Build cache filename
  ↓
Check cache exists & fresh (<1hr)
  ├─ Yes → Serve cached image
  └─ No  → Generate new QRIS
              ↓
        Run Python script
              ↓
        Cache result
              ↓
        Serve image
```

### Error Handling

**Scenario 1: QRIS Text File Missing**
- **Action:** Abort with 500 error
- **Message:** "QRIS text file not found. Cannot generate dynamic QR."

**Scenario 2: Python Script Fails**
- **Action:** Log error, fallback to static QR image
- **Log:** Command, output, return code

**Scenario 3: Generated File Not Found**
- **Action:** Abort with 500 error
- **Message:** "Generated QRIS file not found"

**Scenario 4: General Exception**
- **Action:** Log error, fallback to static QR image
- **Fallback:** Returns original uploaded QR image

### Cache File Management

**Storage Location:**
```
storage/
  app/
    public/
      qr_cache/
        qris_dynamic_5_123_current_billing_150000.png
        qris_dynamic_5_456_previous_billing_200000.png
```

**Symlink Required:**
```bash
php artisan storage:link
```

**Public Access:**
```
public/storage/qr_cache/qris_dynamic_*.png
```

## Cleanup Command

### Command
```bash
php artisan qris:cleanup-cache
```

### Options
```bash
# Delete files older than 24 hours (default)
php artisan qris:cleanup-cache

# Delete files older than 48 hours
php artisan qris:cleanup-cache --hours=48

# Delete files older than 1 hour
php artisan qris:cleanup-cache --hours=1
```

### Scheduling (Optional)
Add to `app/Console/Kernel.php`:
```php
protected function schedule(Schedule $schedule)
{
    // Clean up QRIS cache daily at 2 AM
    $schedule->command('qris:cleanup-cache --hours=24')
             ->dailyAt('02:00');
}
```

### Output Example
```
Cleaning up QRIS cache files older than 24 hours...
Deleted: qr_cache/qris_dynamic_5_123_current_billing_150000.png (25.4 KB)
Deleted: qr_cache/qris_dynamic_5_124_previous_billing_200000.png (26.1 KB)

Deleted 2 cached file(s), freed 51.5 KB
```

## Frontend Integration

### Blade Template
```blade
<img src="{{ route('qris.image', [
    'payment_id' => $payment->id, 
    'amount' => $totalAmountWithFee, 
    'type' => $type
]) }}" 
     alt="QR Code"
     class="img-fluid">
```

### Route
```php
Route::get('/qris/image', 'HomeController@serveQrisImage')
    ->middleware('auth')
    ->name('qris.image');
```

## Testing Dynamic QRIS

### Manual Test Steps

1. **Create Dynamic QRIS Payment Method (Admin)**
   ```
   - Jenis Pembayaran: QRIS
   - Upload QR image
   - Check "Dynamic QRIS"
   - Fee Type: Percent/Rupiah
   - Fee Value: 2 (for 2%) or 5000 (for Rp 5,000)
   ```

2. **User Payment Flow**
   ```
   - Navigate to Home → Konfirmasi Pembayaran
   - Select QRIS payment method
   - Choose billing type (current/previous)
   - View generated QR with total amount
   ```

3. **Verify Cache**
   ```bash
   ls -lh storage/app/public/qr_cache/
   # Should show: qris_dynamic_*.png files
   ```

4. **Test Cache Refresh**
   - Wait 1+ hours OR manually delete cache file
   - Reload payment page
   - New QR should generate

5. **Scan QR Code**
   - Use mobile QRIS app (GoPay, Dana, etc.)
   - Verify amount matches displayed total
   - Amount should include fees

### Python Script Test
```bash
# Test QRIS generation directly
/opt/venv/bin/python3 scripts/generate_dynamic_qris.py \
    storage/app/public/qr_texts/your_qris.txt \
    150000 \
    test_output.png

# Check output
file test_output.png
# Should show: PNG image data

# Scan to verify amount
```

## Troubleshooting

### Issue: "QRIS text file not found"
**Cause:** QR text extraction failed during payment method creation
**Solution:**
1. Check `storage/app/public/qr_texts/` directory exists
2. Verify Python script permissions: `chmod +x scripts/extract_qr.py`
3. Test extraction manually:
   ```bash
   /opt/venv/bin/python3 scripts/extract_qr.py your_qr_image.png
   ```

### Issue: Python script fails with exit code 1
**Cause:** Missing dependencies or invalid QRIS format
**Solution:**
1. Reinstall dependencies:
   ```bash
   /opt/venv/bin/pip install -r scripts/requirements.txt
   ```
2. Check logs: `storage/logs/laravel.log`
3. Test script manually with verbose output

### Issue: QR code not updating with new amount
**Cause:** Browser/CDN caching
**Solution:**
1. Hard refresh browser (Ctrl+Shift+R)
2. Check cache file timestamp:
   ```bash
   stat storage/app/public/qr_cache/qris_dynamic_*.png
   ```
3. Manually delete cache file and reload

### Issue: Generated QR shows wrong amount
**Cause:** QRIS format variation or CRC calculation error
**Solution:**
1. Extract QRIS string from generated QR:
   ```bash
   /opt/venv/bin/python3 scripts/extract_qr.py generated_qr.png
   ```
2. Check Tag 54 in output: should show `54XX<amount>`
3. Verify CRC (last 8 characters): `6304XXXX`
4. Compare with original QRIS structure

## Security Considerations

1. **File Permissions**
   - Cache directory: `755`
   - Cache files: `644`
   - Ensure web server can write to `storage/app/public/qr_cache/`

2. **Access Control**
   - Route protected by `auth` middleware
   - Organization validation in controller
   - User can only access their own dynamic QR codes

3. **Input Validation**
   - Amount must be positive integer
   - Payment ID must exist and belong to user's organization
   - Type must be `current_billing` or `previous_billing`

4. **Error Handling**
   - Never expose Python script paths in error messages
   - Log detailed errors server-side
   - Fallback to static QR on generation failure

## Performance Optimization

### Disk Space Management
- Average QR image: ~25 KB
- 1000 cached images: ~25 MB
- Recommended: Daily cleanup of files >24 hours old

### Response Time
- **Cache hit:** <50ms (direct file serve)
- **Cache miss:** 200-500ms (Python generation + save)
- **Fallback:** <100ms (static image serve)

### Scalability
- File-based cache suitable for <10K active users
- For larger scale: Consider Redis with binary storage
- Monitor disk I/O if >100 QR generations/minute

## Future Enhancements

1. **Redis Cache Integration**
   - Store generated images in Redis
   - Faster than disk I/O
   - Automatic expiration

2. **CDN Integration**
   - Upload generated QR to CDN
   - Serve via CDN URL
   - Reduce server load

3. **Batch Generation**
   - Pre-generate common amounts
   - Store in cache pool
   - Instant serving

4. **QR Code Versioning**
   - Support multiple QRIS versions
   - Handle format changes
   - Backward compatibility

## References
- [EMV QR Code Specification](https://www.emvco.com/emv-technologies/qrcodes/)
- [QRIS Technical Documentation (Bank Indonesia)](https://www.bi.go.id/qris)
- [CRC-16-CCITT Algorithm](https://en.wikipedia.org/wiki/Cyclic_redundancy_check)
- [Python qrcode Library](https://pypi.org/project/qrcode/)
