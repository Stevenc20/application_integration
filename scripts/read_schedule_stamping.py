#!/usr/bin/env python3
"""
read_schedule_stamping.py
Membaca file Schedule Stamping Excel (.xlsx/.xlsm).
1 file berisi data 4 press (PRESS A, B, C, D) yang disusun berurutan dalam 1 sheet.
Setiap press memiliki blok tersendiri, diawali dengan header "PRESS X" di kolom manapun.

Usage: python read_schedule_stamping.py <excel_file> [original_filename]
"""
import sys
import json
import warnings
import re
import os
from datetime import datetime, time
import openpyxl

warnings.filterwarnings('ignore')


BREAK_KEYWORDS = {
    'ISTIRAHAT SIANG', 'ISTIRAHAT SORE', 'ISTIRAHAT JUMAT',
    'ISTIRAHAT PAGI', 'CINGKORAK', 'BREAKTIME', 'BREAK TIME', 'BREAKTI',
    'ISTIRAHAT SORE RAMADHAN', 'TOTAL FNISH', 'TOTAL FINISH',
    'ISTIRAHAT', 'JUMAT', 'SORE', 'MALAM', 'PAGI', 'SIANG', 'BREAK'
}

MONTHS_ID = {
    'JAN':'01','FEB':'02','MAR':'03','APR':'04','MEI':'05','MAY':'05',
    'JUN':'06','JUL':'07','AGU':'08','AUG':'08','SEP':'09',
    'OKT':'10','OCT':'10','NOV':'11','DES':'12','DEC':'12',
}

# Keywords that mark the start of a PRESS section header
PRESS_HEADER_KEYWORDS = ['PRESS A', 'PRESS B', 'PRESS C', 'PRESS D',
                         'PRESS-A', 'PRESS-B', 'PRESS-C', 'PRESS-D']

def safe_float(v):
    if v is None:
        return None
    if isinstance(v, (int, float)):
        return float(v)
    s = str(v).strip().replace(',', '.')
    if s in ('', '-', '#N/A', '#VALUE!', '#REF!', '0'):
        return 0.0
    try:
        # Remove any non-numeric chars except dot
        s_clean = re.sub(r'[^0-9\.]', '', s)
        return float(s_clean) if s_clean else 0.0
    except:
        return 0.0

def safe_int(v):
    if v in ('\u2014', '—', '-', '', None):
        return None
    f = safe_float(v)
    return int(round(f)) if f is not None else None

def fmt_time(v):
    """Convert time/datetime/float to 'HH:MM' string."""
    if v is None:
        return None
    if isinstance(v, time):
        return v.strftime('%H:%M')
    if isinstance(v, datetime):
        return v.strftime('%H:%M')
    if isinstance(v, float):
        total_min = round(v * 24 * 60)
        h, m = divmod(total_min, 60)
        return f"{h % 24:02d}:{m:02d}"
    return None

def get_cell(row, col_0based):
    """Get value from row by 0-based index."""
    if col_0based is None:
        return None
    if col_0based < 0 or col_0based >= len(row):
        return None
    v = row[col_0based]
    if isinstance(v, str) and v.strip() in ('#N/A', '#VALUE!', '#REF!', ''):
        return None
    return v

def normalize_press_name(s):    
    """Normalize press name: 'PRESS-A' -> 'PRESS A'"""
    if not s:
        return None
    s = s.strip().upper().replace('-', ' ')
    m = re.match(r'(PRESS)\s*([A-D])', s)
    if m:
        return f"PRESS {m.group(2)}"
    return s

def is_press_header(row):
    """Check if row contains a PRESS name in ANY column."""
    for cell in row:
        if cell is not None:
            s = str(cell).strip().upper().replace('-', ' ')
            for pk in PRESS_HEADER_KEYWORDS:
                if pk.replace('-', ' ') in s:
                    return normalize_press_name(pk)
    return False

def is_data_header(row):
    """Check if row is the column header row (NO. | JOB MASTER | ...)"""
    c2 = get_cell(row, 2)
    c3 = get_cell(row, 3)
    if c2 and 'NO' in str(c2).upper():
        return True
    if c3 and 'JOB MASTER' in str(c3).upper():
        return True
    return False

def is_summary_row(row):
    """Check if row marks the end of a data section."""
    keywords = ['PLAN', 'TOTAL STROKE', 'TOTAL TPT', 'TARGET GSPH', 'TOTAL FINISH', 'TOTAL FNISH', 'GSPH', 'TOTAL PCS']
    for cell in row:
        if cell is not None:
            s = str(cell).upper()
            if any(kw in s for kw in keywords):
                return True
    return False

def parse_data_row(row, row_no, col_map):
    """Parse one data row after data header using col_map."""
    job_master_idx = col_map.get('job_master')
    job_no_idx = col_map.get('job_no')
    
    jm_raw = get_cell(row, job_master_idx) if job_master_idx is not None else None
    jn_raw = get_cell(row, job_no_idx) if job_no_idx is not None else None
    
    jm_str = str(jm_raw).strip() if jm_raw is not None else ''
    jn_str = str(jn_raw).strip() if jn_raw is not None else ''

    # 1. Structural Detection
    row_no_raw = get_cell(row, 0)
    is_dash_row = (str(row_no_raw).strip() in ("\u2014", "#N/A"))

    # SIMPLE BREAK DETECTION (AS REQUESTED)
    break_text = f"{jm_str} {jn_str}".upper()
    is_break = any(k in break_text for k in ['ISTIRAHAT', 'BREAK', 'CINGKORAK', 'FINISH']) or (is_dash_row and not jm_str)
    
    # FORCE detect text from ANY cell before generic logic
    for cell in row:
        if cell is not None:
            cell_upper = str(cell).strip().upper()
            if any(kw in cell_upper for kw in BREAK_KEYWORDS):
                jm_str = cell_upper
                jn_str = cell_upper
                is_break = True
                break

    if is_break:
        break_text = f"{jm_str} {jn_str}".upper()
        clean_label = None

        if 'ISTIRAHAT JUMAT' in break_text:
            clean_label = 'ISTIRAHAT JUMAT'
        elif 'ISTIRAHAT SIANG' in break_text:
            clean_label = 'ISTIRAHAT SIANG'
        elif 'ISTIRAHAT SORE' in break_text:
            clean_label = 'ISTIRAHAT SORE'
        elif 'ISTIRAHAT MALAM' in break_text:
            clean_label = 'ISTIRAHAT MALAM'
        elif 'BREAKTIME' in break_text or 'BREAK TIME' in break_text:
            clean_label = 'BREAKTIME'
        elif 'CINGKORAK' in break_text:
            clean_label = 'CINGKORAK'
        elif 'TOTAL FINISH' in break_text or 'FINISH' in break_text:
            clean_label = 'TOTAL FINISH'
        
        if clean_label:
            jm_str = clean_label
            jn_str = clean_label

    if not is_break and not jm_str and not jn_str:
        return None
    
    # Final cleanup: Ensure 'TOTAL FINISH' is pretty
    if 'FINISH' in jm_str.upper() or 'FNISH' in jm_str.upper():
        jm_str = 'TOTAL FINISH'
        jn_str = 'TOTAL FINISH'

    def get_val(key, default=None, is_int=False, is_time=False):
        idx = col_map.get(key)
        val = get_cell(row, idx) if idx is not None else None
        
        # FUZZY SEARCH for summary/break rows if the direct column is empty
        if is_break and (val is None or val == 0 or val == ''):
            if key in ('ok', 'plan', 'qty_plt', 'dt_menit', 'tpt', 'gsph_item', 'total_pcs'):
                row_nums = []
                for cell in row:
                    f = safe_float(cell)
                    if f and f > 0: row_nums.append(f)
                
                if not row_nums: return default

                if key == 'dt_menit':
                    for n in row_nums:
                        if n in (15, 30, 40, 45, 60): return int(n)
                
                # Logic Fix: Plan is usually the Largest, OK is usually the Second Largest
                if key == 'plan':
                    return max(row_nums)
                if key == 'ok':
                    sorted_nums = sorted(row_nums, reverse=True)
                    return sorted_nums[1] if len(sorted_nums) > 1 else sorted_nums[0]
                
                return max(row_nums)
                    
        if is_time: return fmt_time(val)
        if is_int: return safe_int(val)
        return safe_float(val)

    ok = get_val('ok') or 0
    repair = get_val('repair') or 0
    reject = get_val('reject') or 0
    total_pcs = ok + repair + reject

    type_plt_idx = col_map.get('type_plt')
    type_plt = str(get_cell(row, type_plt_idx) or '').strip() if type_plt_idx is not None else None
    
    each_part_idx = col_map.get('each_part')
    each_part = get_cell(row, each_part_idx)
    each_part_str = str(each_part).strip() if each_part and str(each_part).strip() not in ('None','') else None

    keterangan_idx = col_map.get('keterangan')
    keterangan = str(get_cell(row, keterangan_idx) or '').strip() if keterangan_idx is not None else None

    job_no_str = jn_str
    if is_break:
        job_no_str = jm_str

    # SHIFT MALAM FINAL RE-CHECK: Ensure labels force is_break
    break_text = f"{jm_str} {jn_str}".upper()
    if any(k in break_text for k in BREAK_KEYWORDS):
        is_break = True
        if not jm_str or jm_str in ('0', 'None', ''): jm_str = break_text
        if not job_no_str or job_no_str in ('0', 'None', ''): job_no_str = break_text

    return {
        'row_no':       row_no if not is_break else None,
        'row_type':     'break' if is_break else 'job',
        'job_master':   jm_str or None,
        'job_no':       job_no_str or jn_str or None,
        'type_plt':     type_plt if not is_break else None,
        'qty_plt':      get_val('qty_plt'),
        'keb_mtl' :     get_val('keb_mtl'),
        'total_plt':    get_val('total_plt'),
        'each_part':    each_part_str,
        'plan':         get_val('plan'),
        'ok':           ok,
        'repair':       repair,
        'reject':       reject,
        'total_mesin':  get_val('total_mesin', is_int=True),
        'ct_detik':     get_val('ct_detik'),
        'process_time': get_val('process_time') if not is_break else 0,
        'reg_active':   get_val('reg_active') if not is_break else 0,
        'dct':          get_val('dct'),
        'mct':          get_val('mct'),
        'plan_dct':     get_val('plan_dct'),
        'tpt':          get_val('tpt'),
        'gsph_item':    get_val('gsph_item'),
        'start_time':   get_val('start_time', is_time=True),
        'finish_time':  get_val('finish_time', is_time=True),
        'act_start':    fmt_time(get_cell(row, col_map.get('act_start'))),
        'act_finish':   fmt_time(get_cell(row, col_map.get('act_finish'))),
        'keterangan':   keterangan,
        'a1':           get_val('a1', is_int=True) if not is_break else 0,
        'a2':           get_val('a2', is_int=True) if not is_break else 0,
        'a3':           get_val('a3', is_int=True) if not is_break else 0,
        'a4':           get_val('a4', is_int=True) if not is_break else 0,
        'dt_menit':     get_val('dt_menit', is_int=True),
        'total_pcs':    total_pcs,
        'tpt_total':    get_val('tpt_total'),
    }

def get_merged_cell_value(ws, row_idx):
    """Retrieve value from merged range by scanning nearby rows for keywords."""
    # scan nearby rows (current row +/- 3)
    for search_row in range(max(1, row_idx - 3), row_idx + 4):
        for merged_range in ws.merged_cells.ranges:
            min_col, min_row, max_col, max_row = merged_range.bounds
            if min_row <= search_row <= max_row:
                value = ws.cell(min_row, min_col).value
                if value:
                    text = str(value).strip()
                    # important filter: only pick if it contains our keywords
                    if any(k in text.upper() for k in BREAK_KEYWORDS):
                        return text
    return None

def parse_sheet(ws, sheet_name):
    """Parse a single sheet."""
    all_rows = list(ws.iter_rows(min_row=1, max_row=500, max_col=50, values_only=True))

    global_meta = {'hari': None, 'tgl': None, 'jam': None, 'revisi': None}
    # Scan first 35 rows for metadata anywhere
    for ri in range(0, 35):
        if ri >= len(all_rows): break
        row = all_rows[ri]
        row_str = " ".join([str(c) for c in row if c is not None]).upper()
        
        # Look for labels and extract what's after ':'
        for cell_val in row:
            if cell_val is None: continue
            s = str(cell_val).strip()
            if ':' in s:
                parts = s.split(':', 1)
                label = parts[0].upper()
                val = parts[1].strip()
                if 'HARI' in label: global_meta['hari'] = val
                elif 'TGL' in label: global_meta['tgl'] = val
                elif 'JAM' in label: global_meta['jam'] = val
            
            if 'REVISI' in s.upper():
                global_meta['revisi'] = s

    press_sections = {p: {
        'hari': global_meta['hari'],
        'tgl': global_meta['tgl'],
        'jam': global_meta['jam'],
        'revisi': global_meta['revisi'],
        'rows': []
    } for p in ['PRESS A', 'PRESS B', 'PRESS C', 'PRESS D']}
    current_press   = "PRESS A"
    in_data_section = False
    row_no          = 1
    current_meta    = dict(global_meta)
    col_map         = {}

    KEYWORD_MAP = {
        'JOBMASTER': 'job_master',
        'TYPE': 'type_plt',
        'QTY': 'qty_plt',
        'TOTALPLT': 'total_plt',
        'JOBNO': 'job_no',
        'PLAN': 'plan',
        'OK': 'ok',
        'MESIN': 'total_mesin',
        'CT': 'ct_detik',
        'PROC': 'process_time',
        'REG': 'reg_active',
        'DCT': 'dct',
        'MCT': 'mct',
        'TPT': 'tpt',
        'GSPH': 'gsph_item',
        'START': 'start_time',
        'FINISH': 'finish_time',
        'KETERANGAN': 'keterangan',
        'A1': 'a1', 'A2': 'a2', 'A3': 'a3', 'A4': 'a4',
        'B1': 'a1', 'B2': 'a2', 'B3': 'a3', 'B4': 'a4',
        'C1': 'a1', 'C2': 'a2', 'C3': 'a3', 'C4': 'a4',
        'D1': 'a1', 'D2': 'a2', 'D3': 'a3', 'D4': 'a4',
        'DT': 'dt_menit',
        'TOTALPCS': 'total_pcs',
    }

    last_seen = {
        'job_master': None,
        'job_no':     None,
        'type_plt':   None,
        'qty_plt':    None,
        'press_name': current_press
    }
    row_count = 0
    for ri, row in enumerate(all_rows):
        row_count += 1
        press_label = is_press_header(row)
        if press_label:
            current_press   = press_label
            in_data_section = False
            row_no          = 1
            job_seq         = 0
            current_meta    = dict(global_meta)
            
            # Reset persistence on new press section
            for k in last_seen: last_seen[k] = None
            last_seen['press_name'] = current_press

            for offset in range(1, 10):
                ni = ri + offset
                if ni >= len(all_rows):
                    break
                nrow = all_rows[ni]
                col2 = get_cell(nrow, 2)
                col3 = get_cell(nrow, 3)
                if col2 and str(col2).strip().upper().startswith('H') and col3 and ':' in str(col3):
                    current_meta['hari'] = str(col3).split(':', 1)[1].strip()
                elif col2 and str(col2).strip().upper().startswith('T') and col3 and ':' in str(col3):
                    current_meta['tgl'] = str(col3).split(':', 1)[1].strip()
                elif col2 and str(col2).strip().upper().startswith('J') and col3 and ':' in str(col3):
                    current_meta['jam'] = str(col3).split(':', 1)[1].strip()

            press_sections[current_press] = {
                'hari':  current_meta['hari'],
                'tgl':   current_meta['tgl'],
                'jam':   current_meta['jam'],
                'revisi': current_meta['revisi'],
                'rows':  press_sections.get(current_press, {}).get('rows', []),
            }
            continue

        if current_press is None:
            continue

        if is_data_header(row):
            in_data_section = True
            col_map = {}
            for cidx, cell_val in enumerate(row):
                if cell_val is None or cidx > 40:
                    continue
                # Industrial-grade normalization: upper + remove non-alphanumeric
                raw_s = str(cell_val).upper().strip()
                s = re.sub(r'[^A-Z0-9]', '', raw_s)
                
                for kw, field in KEYWORD_MAP.items():
                    if kw in s:
                        if field not in col_map:
                            col_map[field] = cidx
            continue

        if not in_data_section:
            continue
            
        # Use Excel row index (ri + 1) as the absolute source of truth for sequence
        excel_row_idx = ri + 1
        merged_label = get_merged_cell_value(ws, excel_row_idx)

        if is_summary_row(row):
            parsed = parse_data_row(row, excel_row_idx, col_map)
            if parsed:
                # Reset persistence on summary
                for k in last_seen: 
                    if k != 'press_name': last_seen[k] = None

                # FORCE overwrite if merged label found
                if merged_label:
                    parsed['job_master'] = merged_label
                    parsed['job_no'] = merged_label
                
                row_text = (str(parsed.get('job_master', '')) + ' ' + str(parsed.get('job_no', ''))).upper()
                if 'TOTAL FINISH' in row_text or 'TOTAL FNISH' in row_text or 'FINISH' in row_text:
                    parsed['row_type'] = 'total_finish'
                else:
                    parsed['row_type'] = 'break'
                press_sections[current_press]['rows'].append(parsed)
            continue

        parsed = parse_data_row(row, excel_row_idx, col_map)
        if parsed:
            # STRICT VALIDATION: Skip if no identifiable job data
            jm = str(parsed.get('job_master') or '').strip()
            jn = str(parsed.get('job_no') or '').strip()
            
            # If both are empty or just placeholder chars, skip this row
            if not jm and not jn:
                continue
            if jm in ('—', '-', '0') and jn in ('—', '-', '0'):
                continue
                
            job_seq += 1
            parsed['row_no'] = job_seq
            
            # FORWARD FILL (Persistence Logic for Merged Cells)
            if not parsed.get('job_master') and last_seen['job_master']:
                parsed['job_master'] = last_seen['job_master']
            else:
                last_seen['job_master'] = parsed.get('job_master')

            if not parsed.get('job_no') and last_seen['job_no']:
                parsed['job_no'] = last_seen['job_no']
            else:
                last_seen['job_no'] = parsed.get('job_no')

            if not parsed.get('type_plt') and last_seen['type_plt']:
                parsed['type_plt'] = last_seen['type_plt']
            else:
                last_seen['type_plt'] = parsed.get('type_plt')

            # Leniency: Only skip if it's COMPLETELY empty or clearly junk
            jm = str(parsed.get('job_master') or '').upper()
            jn = str(parsed.get('job_no') or '').upper()
            
            # Skip only if it's a known error string and NOT a break
            if '#N/A' in jm and parsed['row_type'] != 'break':
                job_seq -= 1 # Rollback
                continue

            # Check for merged label even in standard rows
            if parsed['row_type'] == 'break' or (not parsed.get('job_master') and merged_label):
                if merged_label:
                    parsed['job_master'] = merged_label
                    parsed['job_no'] = merged_label
                    parsed['row_type'] = 'break'
                    # Reset persistence on break
                    for k in last_seen: 
                        if k != 'press_name': last_seen[k] = None

            # Final check: Must have at least a Job Master OR Job No OR Plan OR Start Time
            if not parsed.get('job_master') and not parsed.get('job_no') and not parsed.get('plan') and not parsed.get('start_time'):
                job_seq -= 1 # Rollback
                continue

            press_sections[current_press]['rows'].append(parsed)

    return press_sections

def extract_upload_date(filepath, original_name):
    """Extract date from filename."""
    name = os.path.basename(original_name or filepath).upper()
    for month_key, month_num in MONTHS_ID.items():
        pattern = rf'(\d{{1,2}})[-_\s]*{month_key}[-_\s]*(\d{{4}})'
        m = re.search(pattern, name)
        if m:
            day  = m.group(1).zfill(2)
            year = m.group(2)
            month_names = {
                '01':'JANUARI','02':'FEBRUARI','03':'MARET','04':'APRIL',
                '05':'MEI','06':'JUNI','07':'JULI','08':'AGUSTUS',
                '09':'SEPTEMBER','10':'OKTOBER','11':'NOVEMBER','12':'DESEMBER',
            }
            return f"{day} {month_names.get(month_num, month_key)} {year}"
    now = datetime.now()
    month_names = {1:'JANUARI',2:'FEBRUARI',3:'MARET',4:'APRIL',5:'MEI',6:'JUNI',
                   7:'JULI',8:'AGUSTUS',9:'SEPTEMBER',10:'OKTOBER',11:'NOVEMBER',12:'DESEMBER'}
    return f"{str(now.day).zfill(2)} {month_names[now.month]} {now.year}"

def main():
    if len(sys.argv) < 2:
        print(json.dumps({'error': 'No file path provided'}, default=str))
        sys.exit(1)

    filepath  = sys.argv[1]
    orig_name = sys.argv[2] if len(sys.argv) >= 3 else filepath
    upload_date = extract_upload_date(filepath, orig_name)

    try:
        wb = openpyxl.load_workbook(filepath, data_only=True)
        sheets = wb.sheetnames

        all_candidate_sheets = []
        for s in sheets:
            sl = s.lower().strip()
            if ('shift' in sl or 'press' in sl) and not any(k in sl for k in ['master', 'format', 'resume', 'template']):
                all_candidate_sheets.append(s)

        best_sheets = {}
        for s in all_candidate_sheets:
            # Smart cleaning: remove REV, REVISI, numbers, and brackets
            # e.g. "SHIFT PAGI REV-001" -> "SHIFT PAGI"
            clean = re.sub(r'[\(\s]REV.*|[\(\s]REVISI.*|\d+', '', s, flags=re.I).strip()
            
            is_rev = bool(re.search(r'REV|REVISI', s, re.I))
            if clean not in best_sheets:
                best_sheets[clean] = s
            else:
                # If we find a revision, or a longer name (likely more specific), prefer it
                if is_rev or len(s) > len(best_sheets[clean]):
                    best_sheets[clean] = s
        
        sheets_to_parse = list(best_sheets.values())
        parsed_result = {}

        for sname in sheets_to_parse:
            ws = wb[sname]
            press_sections = parse_sheet(ws, sname)
            for press_name, section_data in press_sections.items():
                if not section_data['rows']:
                    continue
                key = f"{sname}|||{press_name}"
                parsed_result[key] = {
                    'shift_name': sname,
                    'press_name': press_name,
                    'hari':   section_data['hari'],
                    'tgl':    section_data['tgl'],
                    'jam':    section_data['jam'],
                    'revisi': section_data['revisi'],
                    'rows':   section_data['rows'],
                }

        wb.close()

        if not parsed_result:
            print(json.dumps({'error': 'Tidak ada data schedule ditemukan.'}, default=str))
            sys.exit(1)

        print(json.dumps({
            'success': True,
            'upload_date': upload_date,
            'total_sheets': len(parsed_result),
            'sheets': parsed_result,
        }, default=str))

    except Exception as e:
        import traceback
        print(json.dumps({'error': str(e), 'trace': traceback.format_exc()}, default=str))
        sys.exit(1)

if __name__ == '__main__':
    main()
