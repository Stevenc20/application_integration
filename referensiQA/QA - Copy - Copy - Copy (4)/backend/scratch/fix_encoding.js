const fs = require('fs');
let buf = fs.readFileSync('resources/js/liForm.js');

function replaceBytes(buf, findHex, replaceHex) {
    const find = Buffer.from(findHex, 'hex');
    const replace = Buffer.from(replaceHex, 'hex');
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
    if (count > 0) console.log(`Replaced ${count}x: ${findHex} -> ${replaceHex}`);
    return Buffer.concat(parts);
}

// c383c2a2c385e2809cc3a2e282acc593 = Ã¢Å"â€œ (✓ checkmark)
buf = replaceBytes(buf, 'c383c2a2c385e2809cc3a2e282acc593', 'e29c93'); // ✓
// c383c2a2c385e2809cc3a2e282acc2a2 = Ã¢Å"â€¢ (✗ cross)  
buf = replaceBytes(buf, 'c383c2a2c385e2809cc3a2e282acc2a2', 'e29c97'); // ✗
// c383c2a2c3a2e2809ac2acc382c2a2 = Ã¢â‚¬Â¢ (• bullet)
buf = replaceBytes(buf, 'c383c2a2c3a2e2809ac2acc382c2a2', 'c2b7'); // ·
// c383c2a2e2809ac2acc2a2e28094 = Ã¢â€"â€  (em dash —)
buf = replaceBytes(buf, 'c383c2a2e282acc2a2e28094', 'e28094'); // —
// c383c2a2c3a2e282acc2acc382e2809c = Ã¢â‚¬â€ (em dash)
buf = replaceBytes(buf, 'c383c2a2c3a2e282acc2acc382e2809c', 'e28094'); // —

fs.writeFileSync('resources/js/liForm.js', buf);
console.log('Done, size:', buf.length);
