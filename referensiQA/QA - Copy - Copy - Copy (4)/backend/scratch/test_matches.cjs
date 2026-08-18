const fs = require('fs');
const data = JSON.parse(fs.readFileSync('test_api.json', 'utf8'));

let matches = data.filter(d => d.part_name === 'EXTENSION, QTR PANEL UPR RH');
console.log(`Found ${matches.length} matches`);
matches.forEach(i => {
    console.log(`ID: ${i.id}, status: ${i.status}, total_produksi: ${i.total_produksi}`);
    console.log(`item_checks length:`, i.item_checks ? i.item_checks.length : 0);
});
