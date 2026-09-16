const fs = require('fs');

function replaceBytes(buf, findHex, replaceStr) {
    const find = Buffer.from(findHex, 'hex');
    const replace = Buffer.from(replaceStr, 'utf8');
    const parts = [];
    let lastIdx = 0;
    let count = 0;
    while (true) {
        const idx = buf.indexOf(find, lastIdx);
        if (idx === -1) { parts.push(buf.slice(lastIdx)); break; }
        parts.push(buf.slice(lastIdx, idx));
        parts.push(replace);
        lastIdx = idx + find.length;
        count++;
    }
    if (count > 0) console.log(`Replaced ${count}x "${findHex}" with "${replaceStr}"`);
    return Buffer.concat(parts);
}

let buf = fs.readFileSync('resources/js/liForm.js');

// c383c2a2c385e2809cc3a2e282acc593 = the broken ✓ (checkmark)
buf = replaceBytes(buf, 'c383c2a2c385e2809cc3a2e282acc593', '✓');
// c383c2a2c385e2809cc3a2e282acc2a2 = the broken ✗ (cross)
buf = replaceBytes(buf, 'c383c2a2c385e2809cc3a2e282acc2a2', '✗');
// c383c2a2c3a2e2809ac2acc382c2a2 = the broken • (bullet)
buf = replaceBytes(buf, 'c383c2a2c3a2e2809ac2acc382c2a2', '•');
// c383c2a2c3a2e282acc2acc385e2809c = broken checkmark variant
buf = replaceBytes(buf, 'c383c2a2c385e2809cc3a2e282acc5a1', '✅');

fs.writeFileSync('resources/js/liForm.js', buf);
console.log('Done, size:', buf.length);
