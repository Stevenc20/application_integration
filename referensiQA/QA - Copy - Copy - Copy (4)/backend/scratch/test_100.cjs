const fs = require('fs');
const data = JSON.parse(fs.readFileSync('test_api.json', 'utf8'));

let matches = data.filter(d => Number(d.total_produksi) === 100);
console.log(`Found ${matches.length} matches with total_produksi=100`);
matches.forEach(i => {
    console.log(`ID: ${i.id}, part: ${i.part_name}, status: ${i.status}, date: ${i.tgl_bulan}`);
    console.log(`item_checks length:`, i.item_checks ? i.item_checks.length : 0);
});
