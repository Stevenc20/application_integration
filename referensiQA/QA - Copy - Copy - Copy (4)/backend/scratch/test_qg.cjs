const fs = require('fs');
const data = JSON.parse(fs.readFileSync('test_api.json', 'utf8'));
let i = data.find(d => d.id === 161);
console.log(i.qg_judgement);
