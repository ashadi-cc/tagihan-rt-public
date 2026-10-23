#!/opt/venv/bin/python3
"""
Dynamic QRIS Generator Script
This script modifies a QRIS string with transaction amount and generates a new QR code image.

QRIS Format (EMV QR Code Standard):
- Tag 54 (Transaction Amount): Format "54XX<amount>" where XX is length
- Amount format: integer without decimals (e.g., "100000")

Usage: python3 generate_dynamic_qris.py <qris_text_file> <amount> <output_image_path>
"""

import sys
import re
from PIL import Image
import qrcode

def modify_qris_amount(qris_string, amount):
    """
    Modify QRIS string to include transaction amount
    
    Args:
        qris_string: Original QRIS string (EMV format)
        amount: Transaction amount in Rupiah (integer)
        
    Returns:
        Modified QRIS string with amount
    """
    # Format amount as integer string (e.g., 100000 -> "100000")
    amount_str = str(int(amount))
    amount_length = len(amount_str)
    
    # Tag 54 format: 54 + length (2 digits) + amount
    new_tag_54 = f"54{amount_length:02d}{amount_str}"
    
    # Update Tag 01 (Point of Initiation Method) from 11 (static) to 12 (dynamic)
    # Tag 01 format: 010211 (static) -> 010212 (dynamic with fixed amount)
    qris_string = qris_string.replace('010211', '010212')
    
    # Remove existing Tag 54 if present (tag 54 is transaction amount)
    # Pattern: 54XX<amount_value> where XX is 2-digit length
    qris_without_54 = re.sub(r'54\d{2}[\d.]+', '', qris_string)
    
    # Also remove Tag 55 (Tip/Convenience) if present as it's related to amount
    qris_without_54 = re.sub(r'55\d{2}[\d.]+', '', qris_without_54)
    
    # Remove existing CRC (Tag 63) - it's always the last 8 characters (6304 + 4 hex digits)
    # Check if it ends with proper CRC format to avoid false matches
    if len(qris_without_54) >= 8 and qris_without_54[-8:-4] == '6304':
        qris_without_crc = qris_without_54[:-8]
    else:
        qris_without_crc = qris_without_54
    
    # Find position to insert Tag 54
    # We need to insert it as a complete tag AFTER Tag 53 but BEFORE Tag 58
    # Tag format: [Tag(2 digits)][Length(2 digits)][Value(length bytes)]
    
    # Find Tag 58 (Country Code) - it's a 2-character value, so format is: 58 02 XX
    tag_58_match = re.search(r'58\d{2}.{2}', qris_without_crc)
    
    if tag_58_match:
        # Insert Tag 54 BEFORE the entire Tag 58
        pos = tag_58_match.start()
        qris_modified = qris_without_crc[:pos] + new_tag_54 + qris_without_crc[pos:]
    else:
        # Fallback: try to find Tag 59 (Merchant Name)
        tag_59_match = re.search(r'59\d{2}', qris_without_crc)
        if tag_59_match:
            pos = tag_59_match.start()
            qris_modified = qris_without_crc[:pos] + new_tag_54 + qris_without_crc[pos:]
        else:
            # Last resort: append at the end before CRC
            qris_modified = qris_without_crc + new_tag_54
    
    # Calculate and append new CRC16
    crc = calculate_crc16(qris_modified + '6304')
    final_qris = qris_modified + f"6304{crc}"
    
    return final_qris

def calculate_crc16(data):
    """
    Calculate CRC-16-CCITT checksum for QRIS
    Polynomial: 0x1021 (x^16 + x^12 + x^5 + 1)
    Initial value: 0xFFFF
    
    Args:
        data: String data to calculate CRC for
        
    Returns:
        4-character hex string (uppercase)
    """
    crc = 0xFFFF
    
    for char in data:
        byte = ord(char)
        crc ^= (byte << 8)
        
        for _ in range(8):
            if crc & 0x8000:
                crc = (crc << 1) ^ 0x1021
            else:
                crc <<= 1
            crc &= 0xFFFF
    
    return f"{crc:04X}"

def generate_qr_image(qris_string, output_path):
    """
    Generate QR code image from QRIS string
    
    Args:
        qris_string: QRIS string (EMV format)
        output_path: Path to save the generated QR code image
    """
    # Create QR code instance
    qr = qrcode.QRCode(
        version=None,  # Auto-detect version
        error_correction=qrcode.constants.ERROR_CORRECT_M,  # Medium error correction
        box_size=10,
        border=4,
    )
    
    # Add data
    qr.add_data(qris_string)
    qr.make(fit=True)
    
    # Create image
    img = qr.make_image(fill_color="black", back_color="white")
    
    # Save image
    img.save(output_path)

def main():
    if len(sys.argv) != 4:
        print("Usage: python3 generate_dynamic_qris.py <qris_text_file> <amount> <output_image_path>", file=sys.stderr)
        sys.exit(1)
    
    qris_text_file = sys.argv[1]
    amount = sys.argv[2]
    output_image_path = sys.argv[3]
    
    try:
        # Read original QRIS string
        with open(qris_text_file, 'r', encoding='utf-8') as f:
            original_qris = f.read().strip()
        
        # Validate QRIS format
        if not original_qris.startswith('00020'):
            print("ERROR: Invalid QRIS format (should start with 00020)", file=sys.stderr)
            sys.exit(1)
        
        # Check if QRIS has CRC, if not add a dummy one that will be recalculated
        if not re.search(r'6304[0-9A-F]{4}$', original_qris):
            print("WARNING: QRIS missing CRC, adding placeholder...", file=sys.stderr)
            original_qris = original_qris + '63040000'
        
        # Validate amount
        try:
            amount_int = int(amount)
            if amount_int <= 0:
                raise ValueError("Amount must be positive")
        except ValueError as e:
            print(f"ERROR: Invalid amount: {e}", file=sys.stderr)
            sys.exit(1)
        
        # Modify QRIS with amount
        modified_qris = modify_qris_amount(original_qris, amount_int)
        
        # Debug output
        print(f"Original QRIS length: {len(original_qris)}", file=sys.stderr)
        print(f"Modified QRIS length: {len(modified_qris)}", file=sys.stderr)
        print(f"Amount tag: 54{len(str(amount_int)):02d}{amount_int}", file=sys.stderr)
        
        # Generate QR code image
        generate_qr_image(modified_qris, output_image_path)
        
        print(f"SUCCESS: Generated dynamic QRIS image at {output_image_path}")
        sys.exit(0)
        
    except FileNotFoundError:
        print(f"ERROR: QRIS text file not found: {qris_text_file}", file=sys.stderr)
        sys.exit(1)
    except Exception as e:
        print(f"ERROR: {str(e)}", file=sys.stderr)
        import traceback
        traceback.print_exc(file=sys.stderr)
        sys.exit(1)

if __name__ == "__main__":
    main()
