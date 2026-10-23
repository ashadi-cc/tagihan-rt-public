#!/opt/venv/bin/python3
"""
QRIS QR Code Image Generator
This script generates a QR code image from QRIS text string.

Usage: python3 create_qris.py <qris_text> <output_image_path>
"""

import sys
from PIL import Image
import qrcode

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
    if len(sys.argv) != 3:
        print("Usage: python3 create_qris.py <qris_text> <output_image_path>", file=sys.stderr)
        sys.exit(1)
    
    qris_text = sys.argv[1]
    output_image_path = sys.argv[2]
    
    try:
        # Validate QRIS format
        if not qris_text.startswith('00020'):
            print("ERROR: Invalid QRIS format (should start with 00020)", file=sys.stderr)
            sys.exit(1)
        
        # Generate QR code image
        generate_qr_image(qris_text, output_image_path)
        
        print(f"SUCCESS: Generated QRIS image at {output_image_path}")
        sys.exit(0)
        
    except Exception as e:
        print(f"ERROR: {str(e)}", file=sys.stderr)
        import traceback
        traceback.print_exc(file=sys.stderr)
        sys.exit(1)

if __name__ == "__main__":
    main()
