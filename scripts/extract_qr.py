#!/opt/venv/bin/python3
"""
QR Code Extraction Script
This script extracts text from QR code images using pyzbar library.
Usage: python3 extract_qr.py <image_path>
"""

import sys
from PIL import Image
from pyzbar.pyzbar import decode

def extract_qr_code(image_path):
    """
    Extract QR code text from image
    
    Args:
        image_path: Path to the image file
        
    Returns:
        Decoded QR code text or None if no QR code found
    """
    try:
        # Open image
        img = Image.open(image_path)
        
        # Decode QR codes in the image
        decoded_objects = decode(img)
        
        if not decoded_objects:
            print("ERROR: No QR code found in image", file=sys.stderr)
            sys.exit(1)
        
        # Get the first QR code's data
        qr_data = decoded_objects[0].data.decode('utf-8')
        
        # Print the decoded text
        print(qr_data)
        sys.exit(0)
        
    except Exception as e:
        print(f"ERROR: {str(e)}", file=sys.stderr)
        sys.exit(1)

if __name__ == "__main__":
    if len(sys.argv) != 2:
        print("Usage: python3 extract_qr.py <image_path>", file=sys.stderr)
        sys.exit(1)
    
    image_path = sys.argv[1]
    extract_qr_code(image_path)
