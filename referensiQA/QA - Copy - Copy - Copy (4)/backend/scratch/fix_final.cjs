const fs = require('fs');

// Emoji via Unicode escapes - completely bypass any encoding issues in this script
const PHONE   = '\uD83D\uDCDE'; // 📞
const SEND    = '\uD83D\uDCE4'; // 📤
const CHECK   = '\u2705';       // ✅
const DISK    = '\uD83D\uDCBE'; // 💾
const CLIP    = '\uD83D\uDCCB'; // 📋
const BOX     = '\uD83D\uDCE6'; // 📦
const DIAM    = '\u00D8';       // Ø

// Regex to match ANY sequence of 1-4 replacement chars (U+FFFD) or literal '??' as emoji placeholders
// We match them by CONTEXT (the surrounding text never changes)
const BLADE_FIXES = [
    // Interkom buttons  
    [/\uFFFD+\s*Interkom GL/g,       PHONE + ' Interkom GL'],
    [/\?\?\s*Interkom GL/g,          PHONE + ' Interkom GL'],
    [/\uFFFD+\s*Interkom Foreman/g,  PHONE + ' Interkom Foreman'],
    [/\?\?\s*Interkom Foreman/g,     PHONE + ' Interkom Foreman'],

    // Tabs
    [/'main','\uFFFD+\s*Standard'\]/g,       "'main','" + CLIP + " Standard']"],
    [/"main","\uFFFD+\s*Standard"\]/g,       '"main","' + CLIP + ' Standard"]'],
    [/\['main','\?\?\s*Standard'\]/g,        "['" + 'main' + "','" + CLIP + " Standard']"],
    [/\['main','[^']*Standard'\]/g,          "['" + 'main' + "','" + CLIP + " Standard']"],
    [/\['bundle','[^']*Bundle Check'\]/g,    "['" + 'bundle' + "','" + BOX + " Bundle Check']"],

    // Simpan Draft button
    [/'[^']*\s*Simpan Draft'/g,              "'" + DISK + " Simpan Draft'"],

    // WA/Foreman kirim text
    [/'[^']*\s*Darurat Foreman!'\s*:/g,      "'" + SEND + " Darurat Foreman!' :"],
    [/:\s*'[^']*\s*Kirim ke Foreman'/g,      ": '" + SEND + " Kirim ke Foreman'"],

    // Diameter symbol
    [/\uFFFD+\s*(\d)/g,                      DIAM + '$1'],
];

const JS_FIXES = [
    // actionBarLabel
    [/return\s*'[^']*\s*Kirim ke Foreman'/g,            "return '" + SEND + " Kirim ke Foreman'"],
    [/return\s*'[^']*\s*Konfirmasi Checked'/g,          "return '" + CHECK + " Konfirmasi Checked'"],
    [/return\s*'[^']*\s*Approve Final'/g,               "return '" + CHECK + " Approve Final'"],
    [/return\s*'[^']*\s*Selesai & Kunci Dokumen'/g,     "return '" + CHECK + " Selesai & Kunci Dokumen'"],
    [/return\s*'[^']*\s*Selesai & Ajukan Verifikasi'/g, "return '" + SEND + " Selesai & Ajukan Verifikasi'"],
    [/return\s*'[^']*\s*Verifikasi GL'/g,               "return '" + CHECK + " Verifikasi GL'"],
    [/return\s*'[^']*\s*Verifikasi Foreman \(Selesai\)'/g, "return '" + CHECK + " Verifikasi Foreman (Selesai)'"],
    [/return\s*'[^']*\s*Simpan'/g,                      "return '" + DISK + " Simpan'"],

    // Diameter cleanup in dimStd item
    [/(dbItem\.replace\()[^)]+\)/g, "(dbItem || '').replace(/\u00C3[\u007E\u02DC\u0192\u00CB\u0153]/g, '\u00D8')"],
];

function applyFixes(content, fixes) {
    for (const [pattern, replacement] of fixes) {
        content = content.replace(pattern, replacement);
    }
    return content;
}

// --- Fix form.blade.php ---
let blade = fs.readFileSync('resources/views/li/form.blade.php', 'utf8');
blade = applyFixes(blade, BLADE_FIXES);
fs.writeFileSync('resources/views/li/form.blade.php', blade, 'utf8');
console.log('Done: form.blade.php');

// --- Fix liForm.js ---
let js = fs.readFileSync('resources/js/liForm.js', 'utf8');
js = applyFixes(js, JS_FIXES);
fs.writeFileSync('resources/js/liForm.js', js, 'utf8');
console.log('Done: liForm.js');

console.log('\nAll done!');
