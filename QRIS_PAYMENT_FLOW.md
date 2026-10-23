# QRIS Payment Flow Documentation

## Overview
This document describes the complete QRIS payment flow implementation in the RT Billing application.

## Payment Flow Comparison

### Bank Transfer Flow
1. User selects payment method → Views bank details
2. User performs manual transfer
3. User fills confirmation form with:
   - Bank name
   - Account number
   - Account holder name
   - Amount paid
   - Payment proof (optional)
4. System validates and stores confirmation

### QRIS Payment Flow
1. User selects QRIS method → Views QR code with total amount (including fees)
2. User scans QR code and pays via mobile app
3. User uploads payment proof (required)
4. System validates and stores confirmation

## Key Differences

### Form Fields
**Bank Transfer:**
- Requires: from_bank, from_account_number, from_account_name, amount_paid
- Payment proof: Optional

**QRIS:**
- No bank details required
- Payment proof: **Required** (mandatory validation)
- Amount is auto-calculated (base amount + fees)

### Fee Calculation
```php
// In HomeController@confirmPaymentForm
$metadata = json_decode($payment->metadata, true);

if (isset($metadata['dynamic_qris']) && $metadata['dynamic_qris']) {
    // Dynamic QRIS with fees
    if (isset($metadata['fee_type'])) {
        if ($metadata['fee_type'] === 'percent' && isset($metadata['fee_percent'])) {
            $feeAmount = ceil(($amountToPay * $metadata['fee_percent']) / 100);
        } elseif ($metadata['fee_type'] === 'rupiah' && isset($metadata['fee_rupiah'])) {
            $feeAmount = $metadata['fee_rupiah'];
        }
    }
}

$totalAmountWithFee = $amountToPay + $feeAmount;
```

**Fee Types:**
- `percent`: Calculated as `ceil((amount * percent) / 100)` - rounded up
- `rupiah`: Fixed amount

### QR Code Display

**Static QRIS:**
- Displays uploaded QR image from storage
- Fixed for all transactions

**Dynamic QRIS:**
- Generates new QR code based on amount
- Cached for 1 hour (3600 seconds)
- TODO: Implement actual QRIS string manipulation

```php
// In HomeController@serveQrisImage
Cache::remember("qris_dynamic_{$payment->id}_{$amount}", 3600, function() use ($payment) {
    // TODO: Implement actual dynamic QRIS generation when needed
    // For now, return the static QR image
    return Storage::get($payment->qr_image_path);
});
```

## File Structure

### Backend Files
1. **app/Http/Controllers/HomeController.php**
   - `confirmPaymentForm()`: Calculates fees, prepares data
   - `submitPaymentConfirmation()`: Handles form submission with QRIS validation
   - `serveQrisImage()`: Serves QR images with caching

2. **app/MasterTable/TablePayment.php**
   - Validates QRIS format
   - Extracts QR text using Python/pyzbar
   - Stores QR images and text files

### Frontend Files
1. **resources/views/home.blade.php**
   - Different button styles for QRIS (green) vs Bank (blue)
   - Displays fee information for dynamic QRIS

2. **resources/views/confirm_payment.blade.php**
   - Shows QR payment info card instead of bank details
   - Displays fee breakdown

3. **resources/views/confirm_payment_form.blade.php**
   - Displays QR code image for QRIS payments
   - Shows total amount including fees
   - Simplified form (no bank fields)
   - Payment proof marked as required

### Routes
```php
// Admin - serve QR images
Route::get('/admin/payment/{paymentId}/qr-image', 'Admin\PaymentController@serveQrImage')
    ->middleware('auth')
    ->name('payment.qr.image');

// User - serve dynamic/static QRIS for payments
Route::get('/qris/image', 'HomeController@serveQrisImage')
    ->middleware('auth')
    ->name('qris.image');
```

## Validation Rules

### submitPaymentConfirmation Validation

**QRIS Payment:**
```php
[
    'payment_id' => 'required|exists:payments,id',
    'type' => 'required|in:current_billing,previous_billing',
    'from_bank' => 'nullable|string|max:255',
    'from_account_number' => 'nullable|string|max:255',
    'from_account_name' => 'nullable|string|max:255',
    'amount_paid' => 'nullable|numeric|min:0',
    'amount_to_pay' => 'required|numeric|min:0',
    'payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', // Required!
    'notes' => 'nullable|string|max:1000',
]
```

**Bank Transfer:**
```php
[
    'payment_id' => 'required|exists:payments,id',
    'type' => 'required|in:current_billing,previous_billing',
    'from_bank' => 'required|string|max:255',
    'from_account_number' => 'required|string|max:255',
    'from_account_name' => 'required|string|max:255',
    'amount_paid' => 'required|numeric|min:0',
    'amount_to_pay' => 'required|numeric|min:0',
    'payment_proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
    'notes' => 'nullable|string|max:1000',
]
```

## Payment Metadata Storage

### QRIS Payment
```php
$paymentInfoMetadata = [
    'payment_method' => 'qr_payment',
    'qr_payment_type' => $payment->name, // e.g., "Dana", "GoPay", "QRIS"
];
```

### Bank Transfer
```php
$paymentInfoMetadata = [
    'from_bank' => $validated['from_bank'],
    'from_account_number' => $validated['from_account_number'],
    'from_account_name' => $validated['from_account_name'],
];
```

## Security Considerations

1. **No Direct Storage Access**: QR images served via controller with organization validation
2. **File Upload Validation**: Only jpg, jpeg, png, pdf; max 5MB
3. **Organization Check**: Ensures users can only access their org's payment methods
4. **Cache Control**: QR images cached with proper headers to prevent unnecessary regeneration

## Future Enhancements

### Dynamic QRIS Generation (TODO)
Currently returns static QR image. Future implementation should:
1. Parse existing QRIS string from `qr_text_path`
2. Inject transaction amount into EMV QR format
3. Regenerate QR code image
4. Cache result

### QRIS Format Reference
- EMV QR Code standard
- Pattern: `00020101...`
- Merchant ID: `ID.CO.QRIS.WWW...`
- Amount field needs to be updated dynamically

## Testing Checklist

- [ ] Static QRIS payment confirmation
- [ ] Dynamic QRIS with percent fee calculation
- [ ] Dynamic QRIS with rupiah fee calculation
- [ ] Payment proof validation (required for QRIS)
- [ ] QR image serving and caching
- [ ] Bank transfer flow still works correctly
- [ ] Organization isolation (users can't access other org's QR codes)
- [ ] File upload to GCS for payment proofs
- [ ] Admin verification of QRIS payments

## Error Handling

1. **Missing Payment Proof**: Returns validation error
2. **Invalid Payment ID**: Returns 404
3. **Duplicate Pending Payment**: Shows error message
4. **File Upload Failure**: Should handle GCS upload errors
5. **Invalid QR Format**: Validated during payment method creation

## Related Documentation
- See `QR_PAYMENT_IMPLEMENTATION.md` for admin-side setup
- See `DOCKER_QR_SETUP.md` for development environment setup
