import openpyxl
import json
import sys

BREAK_KEYWORDS = {
    'ISTIRAHAT SIANG', 'ISTIRAHAT SORE', 'ISTIRAHAT JUMAT',
    'ISTIRAHAT PAGI', 'CINGKORAK', 'BREAKTIME', 'BREAK TIME',
    'ISTIRAHAT SORE RAMADHAN', 'TOTAL FNISH', 'TOTAL FINISH',
    'ISTIRAHAT', 'JUMAT', 'SORE', 'MALAM', 'PAGI', 'SIANG', 'BREAK'
}

def get_cell(row, idx):
    if idx is None or idx < 0 or idx >= len(row): return None
    return row[idx].value

def debug_file(path):
    wb = openpyxl.load_workbook(path, data_only=True)
    report = []
    
    for sheet_name in wb.sheetnames:
        if 'REV' not in sheet_name.upper() and 'PAGI' not in sheet_name.upper(): continue
        
        sheet = wb[sheet_name]
        report.append(f"\n=== ANALYZING SHEET: {sheet_name} ===")
        
        rows = list(sheet.rows)
        for ri, row in enumerate(rows):
            # Try to detect if it's a break row
            row_no_raw = get_cell(row, 0)
            is_dash_row = (str(row_no_raw).strip() == "—")
            
            full_row_text = " ".join([str(c.value).strip().upper() for c in row if c.value is not None])
            has_break_kw = any(kw in full_row_text for kw in BREAK_KEYWORDS)
            
            is_break = is_dash_row or has_break_kw
            
            if is_break:
                all_texts = []
                for cell in row:
                    v = cell.value
                    if v is not None:
                        s_val = str(v).strip()
                        if s_val and s_val not in ("0", "—") and not s_val.replace('.','',1).isdigit():
                            all_texts.append(s_val)
                
                report.append(f"Row {ri+1}: [BREAK DETECTED] | Dash={is_dash_row} | KW={has_break_kw} | Found Texts={all_texts}")

    with open('scratch/excel_report.txt', 'w') as f:
        f.write("\n".join(report))
    print("Report generated in scratch/excel_report.txt")

if __name__ == "__main__":
    # Path to the file the user is importing
    path = "c:/MAMP/htdocs/application_integration/07. Schedule Stamping 08 Mei 2026.xlsx"
    try:
        debug_file(path)
    except Exception as e:
        print(f"Error: {e}")
