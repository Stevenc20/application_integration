const fs = require('fs');
const data = JSON.parse(fs.readFileSync('out.json', 'utf8'));

let i = data;
let docCheckedSamples = 0;
const samplesMap = {};

// Fallback to LembarInspeksi's own results array
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
for (let r = 1; r <= 7; r++) {
    let res = i[`dimensi${r}_results`];
    if (res) {
        if (typeof res === 'string') { try { res = JSON.parse(res); } catch(e) { res = {}; } }
        if (typeof res === 'object') {
            for (let col in res) {
                if (String(res[col]).trim() !== '') samplesMap[col] = true;
            }
        }
    }
}
docCheckedSamples = Object.keys(samplesMap).length;
console.log('docCheckedSamples:', docCheckedSamples);
console.log('samplesMap:', samplesMap);
