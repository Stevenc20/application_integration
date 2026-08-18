const fs = require('fs');
const data = JSON.parse(fs.readFileSync('out.json', 'utf8'));

// out.json is the array of LembarInspeksi or a single LembarInspeksi?
// wait, check_json.php did `find(161)`, so out.json is a SINGLE LembarInspeksi object.
let i = data;

let docCheckedSamples = 0;
let totalSamples = 0;
if (i.sampling_cols && i.sampling_cols.length > 0) {
    totalSamples = i.sampling_cols.length;
} else {
    totalSamples = Number(i.max_sample) || 0;
}

if (i.item_checks && i.item_checks.length > 0) {
    const latestCheck = i.item_checks[0];
    const samplesMap = {};
    try {
        let data = latestCheck.hasil_visual;
        if (typeof data === 'string') data = JSON.parse(data);
        if (data && typeof data === 'object') {
            for (let key in data) {
                const match = key.match(/_(\d+)$/);
                if (match) {
                    const col = match[1];
                    const val = String(data[key]).trim();
                    if (val !== '') samplesMap[col] = true;
                }
            }
        }
    } catch(e) {}
    
    try {
        let dataDim = latestCheck.hasil_dimensi;
        if (typeof dataDim === 'string') dataDim = JSON.parse(dataDim);
        if (dataDim && typeof dataDim === 'object') {
            for (let key in dataDim) {
                const match = key.match(/_(\d+)$/);
                if (match) {
                    const col = match[1];
                    const val = String(dataDim[key]).trim();
                    if (val !== '') samplesMap[col] = true;
                }
            }
        }
    } catch(e) {}
    
    docCheckedSamples = Object.keys(samplesMap).length;
} else {
    const samplesMap = {};
    for (let r = 6; r <= 14; r++) {
        let res = i[`appearance${r}_results`];
        if (res) {
            if (typeof res === 'string') { try { res = JSON.parse(res); } catch(e) { res = {}; } }
            if (typeof res === 'object') {
                for (let col in res) {
                    if (col === 'all') continue;
                    if (String(res[col]).trim() !== '') samplesMap[col] = true;
                }
            }
        }
    }
    docCheckedSamples = Object.keys(samplesMap).length;
}

if (i.status === 'finished' || i.status === 'approved' || i.status === 'locked') {
    docCheckedSamples = Math.max(docCheckedSamples, totalSamples);
}

let j = null; // simulate getJudgement returning null
let sampleNgCount = 0;
if (j === 'NG') {
    let rejectQty = Number(i.reject) || 0;
    if (rejectQty > 0 && rejectQty <= totalSamples) {
        sampleNgCount = rejectQty;
    } else {
        sampleNgCount = 1; 
    }
}

let sampleOkCount = Math.max(0, docCheckedSamples - sampleNgCount);

console.log('docCheckedSamples:', docCheckedSamples);
console.log('sampleOkCount:', sampleOkCount);
console.log('sampleNgCount:', sampleNgCount);
