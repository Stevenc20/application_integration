content = open('resources/js/liForm.js', encoding='utf-8').read()
lines = content.split('\n')

# Find loadTemplateByPartNo start and end
start_idx = None
end_idx = None
for i, line in enumerate(lines):
    if 'async loadTemplateByPartNo' in line and start_idx is None:
        start_idx = i
    if start_idx is not None and i > start_idx + 5 and line.strip() == '},' and end_idx is None:
        end_idx = i
        break

print('Replacing lines', start_idx+1, 'to', end_idx+1)

# Build function as a list of lines (no special chars, no escaping issues)
fn_lines = [
    "            async loadTemplateByPartNo(isAutoLoad = false) {",
    "                if (!this.partNo || !this.partNo.trim()) {",
    "                    if (!isAutoLoad) this.showToast('error', 'Isi Part No terlebih dahulu!');",
    "                    return;",
    "                }",
    "                this.loadingData = true;",
    "                try {",
    "                    const res = await apiFetch('/api/li-templates/' + encodeURIComponent(this.partNo) + '?t=' + Date.now());",
    "                    if (res && res.part_no) {",
    "                        this.partName = res.part_name || '';",
    "                        this.partType = res.type || '';",
    "                        this.specMat = res.spec_material || '';",
    "                        this.typePallet = res.type_pallet || '';",
    "                        this.tactTime = parseFloat(res.tact_time) || 0;",
    "                        this.ctDimensi = parseFloat(res.ct_dimensi) || 0;",
    "                        this.ctTanpaDimensi = parseFloat(res.ct_tanpa_dimensi) || 0;",
    "",
    "                        // === SKETCH ===",
    "                        if (res.image_path) {",
    "                            var p = String(res.image_path);",
    "                            // strip leading storage/ prefix if present",
    "                            var idx1 = p.indexOf('storage/');",
    "                            if (idx1 !== -1) p = p.substring(idx1 + 'storage/'.length);",
    "                            this.sketchUrl = '/storage/' + p;",
    "                        } else {",
    "                            this.sketchUrl = null;",
    "                        }",
    "",
    "                        // === DIMENSI: rebuild array from scratch ===",
    "                        var newDimStd = [];",
    "                        for (var di = 1; di <= 7; di++) {",
    "                            var dItem   = res['dimensi' + di + '_item']   || '';",
    "                            var dMethod = res['dimensi' + di + '_method'] || '';",
    "                            var dNom = '', dPlus = '', dMinus = '';",
    "                            var dFull = res['dimensi' + di] || '';",
    "                            if (dFull) {",
    "                                // Keep only: digits, space, dot, +, -, /",
    "                                // This strips the diameter symbol and mm cleanly",
    "                                var dClean = dFull.replace(/[^0-9 .+\\-\\/]/g, ' ').replace(/\\s+/g, ' ').trim();",
    "                                var dParts = dClean.split('+');",
    "                                if (dParts.length > 1) {",
    "                                    dNom = dParts[0].trim();",
    "                                    var dPM = dParts[1].split('/-');",
    "                                    dPlus  = dPM[0] ? dPM[0].trim() : '';",
    "                                    dMinus = dPM[1] ? dPM[1].trim() : '';",
    "                                } else {",
    "                                    dNom = dClean.trim();",
    "                                }",
    "                            }",
    "                            newDimStd.push({ item: dItem, nominal: dNom, plus: dPlus, minus: dMinus, method: dMethod, _step: 0.01 });",
    "                        }",
    "                        this.dimStd = newDimStd;",
    "",
    "                        // === APPEARANCE: rebuild array from scratch ===",
    "                        var newApp = [];",
    "                        for (var ai = 6; ai <= 14; ai++) {",
    "                            newApp.push(res['appearance' + ai] || '');",
    "                        }",
    "                        this.appItems = newApp;",
    "",
    "                        // Update holeStandard",
    "                        for (var hi = 0; hi < this.appItems.length; hi++) {",
    "                            if (this.appItems[hi] && this.appItems[hi].toUpperCase().indexOf('JUMLAH HOLE') !== -1) {",
    "                                var hm = this.appItems[hi].match(/[0-9]+/);",
    "                                if (hm) this.holeStandard = parseInt(hm[0]);",
    "                                break;",
    "                            }",
    "                        }",
    "",
    "                        this.showToast('success', isAutoLoad ? 'Data Master berhasil dimuat!' : 'Template Master berhasil dimuat!');",
    "                    } else {",
    "                        this.showToast('error', 'Template tidak ditemukan untuk Part No ini.');",
    "                    }",
    "                } catch(e) {",
    "                    this.showToast('error', 'Gagal load Template: ' + e.message);",
    "                } finally {",
    "                    this.loadingData = false;",
    "                }",
    "            },",
]

result = lines[:start_idx] + fn_lines + lines[end_idx+1:]
with open('resources/js/liForm.js', 'w', encoding='utf-8') as f:
    f.write('\n'.join(result))

verify = open('resources/js/liForm.js', encoding='utf-8').read().split('\n')
print('Done! Total lines:', len(verify))

# Show key lines
for i, l in enumerate(verify):
    if 'dClean' in l:
        print('dClean line', i+1, ':', repr(l.strip()[:110]))
        break
for i, l in enumerate(verify):
    if 'sketchUrl' in l and '/storage/' in l and 'null' not in l:
        print('sketchUrl line', i+1, ':', l.strip()[:80])
        break
