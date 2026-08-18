const fs = require('fs');
const data = JSON.parse(fs.readFileSync('test_api.json', 'utf8'));

let matches = data.filter(d => d.job_no === 'RCS-011');
console.log(`Found ${matches.length} matches for RCS-011`);
matches.forEach(i => {
    console.log(`ID: ${i.id}, part: ${i.part_name}, status: ${i.status}, total_produksi: ${i.total_produksi}, date: ${i.tgl_bulan}`);
});
