# Deposit QRIS Implementation Update

## Overview
Updated the deposit functionality to support QRIS payments with simplified user input requirements.

## Changes Made

### 1. Controller (`app/Http/Controllers/HomeController.php`)
The `submitDeposit()` method was already updated with:

- **Conditional Validation**:
  - **QRIS payments**: Only require `amount_paid`, `payment_proof` (required), and `notes` (optional)
  - **Bank transfers**: Require bank details (`from_bank`, `from_account_number`, `from_account_name`), amount, and optional proof

- **Payment Info Metadata**:
  - **QRIS**: Stores `payment_method: 'qr_payment'` and `qr_payment_type`
  - **Bank**: Stores full bank account details

### 2. View (`resources/views/deposit_form.blade.php`)

#### QR Code Display
- Added static QR code display for QRIS payment types
- Shows QR image from `$payment->metadata['qr_image']`
- Centered display with proper styling (max-width: 300px)
- Fallback message if QR code is not available

#### Conditional Form Fields
- **Bank Transfer Fields** (hidden for QRIS):
  - Bank Pengirim
  - No. Rekening Pengirim
  - Nama Pemilik Rekening
  
- **Common Fields** (shown for both):
  - Jumlah Deposit (required)
  - Bukti Transfer (required for QRIS, optional for bank)
  - Catatan (optional)

#### Payment Proof
- Made **required** for QRIS payments
- Remains optional for bank transfers
- Added visual indicator: red asterisk (*) for QRIS
- Updated help text to indicate requirement for QRIS

#### JavaScript Validation
- Added `isQris` detection from backend
- Client-side validation: Prevents submission if QRIS and no proof uploaded
- Shows alert: "Bukti transfer wajib diunggah untuk pembayaran QRIS"

## User Experience

### QRIS Deposit Flow
1. User selects QRIS payment method
2. Form displays:
   - Static QR code (scan to pay)
   - Amount input field
   - **Required** proof upload
   - Optional notes
3. User scans QR, makes payment, uploads proof, and submits

### Bank Transfer Deposit Flow
1. User selects bank transfer payment method
2. Form displays:
   - Bank account details (read-only)
   - Sender bank information fields
   - Amount input field
   - Optional proof upload
   - Optional notes
3. User fills bank details, makes transfer, optionally uploads proof, and submits

## Technical Details

### QRIS Detection
```php
@if($payment->jenis_pembayaran === 'qr_payment')
    // Show QR code and simplified form
@else
    // Show bank fields
@endif
```

### Static QR Code Path
```php
$payment->metadata['qr_image'] // e.g., 'qr_codes/qris_static.png'
```

### Validation Rules (Controller)
```php
// QRIS
['payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120']

// Bank Transfer
['payment_proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120']
```

## Testing Checklist

- [ ] QRIS deposit form displays QR code correctly
- [ ] QRIS deposit requires proof upload (frontend validation)
- [ ] QRIS deposit requires proof upload (backend validation)
- [ ] QRIS deposit does not show bank fields
- [ ] Bank transfer deposit shows all bank fields
- [ ] Bank transfer deposit allows submission without proof
- [ ] Amount validation works (minimum Rp 1)
- [ ] File validation works (type and size)
- [ ] Database stores correct metadata for both payment types
- [ ] Success/error messages display correctly

## Notes

- **Dynamic QRIS** is NOT used for deposits (only static QR codes)
- This matches the requirement: "display static qris no matter the dynamic qris metadata is enabled or not"
- Simplified UX for QRIS: Only amount and proof needed
- Maintains backward compatibility with bank transfers
