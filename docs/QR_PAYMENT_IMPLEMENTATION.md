# QR Payment (QRIS) Implementation

This document explains the QR Payment feature implementation in the payment system.

## Overview

The QR Payment feature allows administrators to add QRIS (Quick Response Code Indonesian Standard) payment channels. Users can scan the QR code to make payments.

## Features

### 1. Basic QR Payment
- Upload QR code image (JPG, PNG, GIF format, max 2MB)
- Automatic validation to ensure uploaded image is a valid QRIS code
- Store QR image in local storage
- Extract and store QR code text for verification

### 2. Dynamic QRIS (Optional)
- Enable dynamic fee calculation
- Two fee types supported:
  - **Rupiah**: Fixed amount in Indonesian Rupiah
  - **Percent**: Percentage-based fee

## Frontend Changes

### File: `resources/views/admin/master/create_payment.blade.php`

1. **Added QRIS option** to payment type select box
2. **QR Payment Fields Section**:
   - Image upload input for QR code
   - Checkbox for enabling Dynamic QRIS
   - Conditional fields for fee configuration (type and value)
3. **JavaScript validation** to show/hide relevant fields based on selections
4. **Form enctype** updated to support file uploads

## Backend Changes

### File: `app/MasterTable/TablePayment.php`

#### 1. Enhanced Validation
```php
if ($request->payment_type === 'qr_payment') {
    $validate['qr_image'] = 'required|image|mimes:jpeg,png,jpg,gif|max:2048';
    if ($request->has('is_dynamic_qris')) {
        $validate['fee_type'] = 'required|in:rupiah,percent';
        $validate['fee_value'] = 'required|numeric|min:0';
    }
}
```

#### 2. QR Image Processing
- **handleQrPaymentUpload()**: Main method for processing QR uploads
  - Stores image in `storage/app/public/qr_images/`
  - Generates unique filename with timestamp and uniqid
  - Extracts text from QR code
  - Validates QRIS format
  - Stores extracted text in `storage/app/public/qr_texts/`
  - Returns metadata array

#### 3. QR Text Extraction
- **extractQrCodeText()**: Extracts text from QR code image
  - Uses Python script with pyzbar library
  - Throws exception if extraction fails

#### 4. QRIS Validation
- **isValidQris()**: Validates if extracted text is valid QRIS
  - Checks for EMV QR format (starts with "00020101" or "00020201")
  - Validates presence of QRIS identifiers (ID.CO.QRIS, COM.GOJEK, etc.)
  - Verifies tag-length-value structure
  - Common QRIS tags: 26 (Merchant Account), 51 (Merchant Category Code)

#### 5. Metadata Format
```php
$metadata = [
    'qr_image_path' => 'qr_images/qr_1234567890_abc123.png',
    'qr_text_path' => 'qr_texts/qr_1234567890_abc123.txt',
    'is_dynamic_qris' => true/false,
    'fee_type' => 'rupiah' or 'percent',  // if dynamic
    'fee_value' => 1000 or 2.5            // if dynamic
]
```

#### 6. Display Enhancement
- **formatMetadata()**: Enhanced to display QR payment info
  - Shows clickable link to view QR image
  - Displays dynamic QRIS status
  - Shows fee information if applicable

## Database Structure

### payments table
- `metadata` column (JSON): Stores all QR payment related data

Example metadata:
```json
{
    "qr_image_path": "qr_images/qr_1704196800_abc123def.png",
    "qr_text_path": "qr_texts/qr_1704196800_abc123def.txt",
    "is_dynamic_qris": true,
    "fee_type": "percent",
    "fee_value": 2.5
}
```

## Storage Structure

```
storage/
└── app/
    └── public/
        ├── qr_images/          # QR code images
        │   └── qr_*.png/jpg
        └── qr_texts/           # Extracted QR text files
            └── qr_*.txt
```

## QR Extraction Setup

### Prerequisites

**Python with pyzbar:**
```bash
# Install Python dependencies
cd scripts
pip3 install -r requirements.txt

# Install zbar library
# Ubuntu/Debian:
sudo apt-get install libzbar0

# macOS:
brew install zbar

# CentOS/RHEL:
sudo yum install zbar
```

Or use the automated setup script:
```bash
cd scripts
./setup.sh
```

## Usage Flow

### Creating QR Payment Channel

1. Navigate to Admin > Master > Pembayaran
2. Click "Tambah Channel Pembayaran"
3. Fill in payment channel name
4. Select "Qris Code" from payment type dropdown
5. Upload QR code image
6. (Optional) Check "Dynamic QRIS" and configure fee
7. Click "Simpan"

### System Processing

1. **Validation**:
   - Validates image format and size
   - Checks if file is uploaded successfully

2. **Extraction**:
   - Extracts text from QR code using available tool
   - Validates extracted text is valid QRIS format

3. **Storage**:
   - Saves image to storage/app/public/qr_images/
   - Saves extracted text to storage/app/public/qr_texts/
   - Stores all metadata in database

4. **Error Handling**:
   - Invalid QR code → Error message displayed
   - Extraction failure → User-friendly error message
   - Missing tools → Informative error about setup

## Error Messages

- **Invalid QRIS**: "QR Code yang diupload bukan QRIS yang valid. Silakan upload QR Code QRIS yang benar."
- **Extraction Failed**: "Gagal membaca QR Code. Pastikan gambar QR Code jelas dan dapat terbaca."
- **Missing Script**: "QR extraction script not found"
- **Tool Error**: "QR Code extraction tool error. Please ensure Python3 and pyzbar are installed."

## Future Enhancements

1. Real-time QR code generation for dynamic QRIS
2. Integration with payment gateway APIs
3. QR code expiration management
4. Multiple QR code support per payment channel
5. QR code analytics and usage tracking

## Testing

### Manual Testing
1. Create payment channel with static QR code
2. Create payment channel with dynamic QR code
3. Test with various QR code images
4. Verify metadata storage and display
5. Check error handling with invalid images

### QR Extraction Test
```bash
cd scripts
python3 extract_qr.py /path/to/test-qr-code.png
```

## Security Considerations

1. **File Upload Validation**: Only allows image files with specific extensions
2. **File Size Limit**: Maximum 2MB to prevent large file attacks
3. **Unique Filenames**: Uses timestamp + uniqid to prevent file collision
4. **Path Storage**: Only stores relative paths in database, not absolute paths
5. **Access Control**: QR images accessible via public storage link

## Maintenance

### Storage Cleanup
Periodically clean up orphaned QR files if payment channels are deleted:
```php
// Add cleanup logic when deleting payment channels
```

### Logs
All payment channel operations are logged with:
- User who performed the action
- Timestamp
- Description of changes
- Link to the payment channel

## Support

For issues or questions about QR payment implementation:
1. Check if QR extraction tools are properly installed
2. Verify storage permissions (storage/app/public should be writable)
3. Review Laravel logs for detailed error messages
4. Test QR extraction script independently
