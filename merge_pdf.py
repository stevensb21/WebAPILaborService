#!/usr/bin/env python3
import sys
import os
from PyPDF2 import PdfReader, PdfWriter

def merge_pdfs(input_files, output_file):
    """Объединяет несколько PDF файлов в один"""
    try:
        writer = PdfWriter()
        
        for input_file in input_files:
            if os.path.exists(input_file):
                reader = PdfReader(input_file)
                for page in reader.pages:
                    writer.add_page(page)
        
        with open(output_file, 'wb') as output:
            writer.write(output)
        
        print(f"Successfully merged {len(input_files)} files into {output_file}")
        return True
        
    except Exception as e:
        print(f"Error merging PDFs: {e}", file=sys.stderr)
        return False

if __name__ == "__main__":
    if len(sys.argv) < 4:
        print("Usage: python3 merge_pdf.py <output_file> <input_file1> <input_file2> ...", file=sys.stderr)
        sys.exit(1)
    
    output_file = sys.argv[1]
    input_files = sys.argv[2:]
    
    if merge_pdfs(input_files, output_file):
        sys.exit(0)
    else:
        sys.exit(1)
