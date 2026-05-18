import openpyxl

def is_summary_row(row):
    keywords = ['PLAN', 'TOTAL STROKE', 'TOTAL TPT', 'TARGET GSPH', 'TOTAL FINISH', 'TOTAL FNISH', 'GSPH', 'TOTAL PCS']
    for cell in row:
        if cell is not None:
            s = str(cell).upper()
            if any(kw in s for kw in keywords):
                return True
    return False

wb = openpyxl.load_workbook('04. Schedule Stamping 05 Mei 2026.xlsx', data_only=True)
ws = wb['Shift Pagi (Rev)']
all_rows = list(ws.iter_rows(values_only=True))

for i, row in enumerate(all_rows):
    if is_summary_row(row):
        print(f"Row {i+1} is summary: {row}")
