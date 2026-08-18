const fs = require('fs');
const data = JSON.parse(fs.readFileSync('test_api.json', 'utf8'));

let matches = data.filter(d => d.tgl_bulan === '2026-07-08' || d.tgl_bulan && d.tgl_bulan.startsWith('2026-07-08'));
console.log(`Found ${matches.length} matches for today`);
matches.forEach(i => {
    console.log(`ID: ${i.id}, part: ${i.part_name}, total: ${i.total_produksi}`);
});
