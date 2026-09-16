const fs = require('fs');
const data = JSON.parse(fs.readFileSync('test_api.json', 'utf8'));

// Find the item with ID 161 (or whatever is the latest one with itemChecks)
let i = data.find(d => d.item_checks && d.item_checks.length > 0);

if (!i) {
    console.log("No LembarInspeksi with item_checks found!");
    process.exit(1);
}

console.log("Found LI ID:", i.id);
let docCheckedSamples = 0;

const latestCheck = i.item_checks[0];
const samplesMap = {};
try {
    let visualData = latestCheck.hasil_visual;
    console.log("typeof visualData:", typeof visualData);
    if (typeof visualData === 'string') visualData = JSON.parse(visualData);
    if (visualData && typeof visualData === 'object') {
        for (let key in visualData) {
            const match = key.match(/_(\d+)$/);
            if (match) {
                const col = match[1];
                const val = String(visualData[key]).trim();
                if (val !== '') samplesMap[col] = true;
            }
        }
    }
} catch(e) {
    console.error("Error parsing visual:", e);
}

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
console.log('docCheckedSamples:', docCheckedSamples);
console.log('samplesMap:', samplesMap);
