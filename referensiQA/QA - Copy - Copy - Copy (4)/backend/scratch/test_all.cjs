const fs = require('fs');
const data = JSON.parse(fs.readFileSync('test_api.json', 'utf8'));

let matches = data.filter(d => d.part_name === 'EXTENSION, QTR PANEL UPR RH');
console.log(`Found ${matches.length} matches`);
let total = 0;
matches.forEach(i => {
    console.log(`ID: ${i.id}, status: ${i.status}, total_produksi: ${i.total_produksi}, date: ${i.tgl_bulan}`);
    total += Number(i.total_produksi) || 0;
});
console.log(`Sum of total_produksi: ${total}`);
