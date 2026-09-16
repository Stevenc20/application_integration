const fs = require('fs');

// Peta karakter kontrol → karakter yang benar berdasarkan konteks
// 0x13 = ✓ checkmark  |  0x14 = — (em dash) atau emoji  |  0x15 = ✕ (cross/close)
// 0x0D = carriage return (sisa CRLF)  |  0x0F = emoji prefix  |  0x1D = > arrow

function fixContent(content) {
    // 1. Normalize line endings first (remove stray \r)
    content = content.replace(/\r\n/g, '\n').replace(/\r/g, '\n');

    // --- SPECIFIC CONTEXT-BASED FIXES ---

    // toast type icon: 0x13 = success ✓, 0x15 = error ✕
    content = content.replace(
        /toast\.type==='success' \? '[\x13]' : '[\x15]'/g,
        "toast.type==='success' ? '\u2713' : '\u2715'"
    );

    // "Selesaikan Revisi" button prefix (0x13 was ✓)
    content = content.replace(/[\x13]\s*Selesaikan Revisi/g, '\u2713 Selesaikan Revisi');

    // SIGN buttons (0x0D before SIGN)
    content = content.replace(/[\x0D]\s*SIGN/g, ' SIGN');

    // "Edit & Tandai Zona" prefix (0x0F\x0F)
    content = content.replace(/[\x0F]+\s*Edit & Tandai Zona/g, '\u270F Edit & Tandai Zona');

    // placeholders & x-text with 0x14 (was —)
    content = content.replace(/placeholder="[\x14]"/g, 'placeholder="-"');
    content = content.replace(/modeLabel \|\| '[\x14]'/g, "modeLabel || '-'");
    content = content.replace(/samplingCalc\.divisor : '[\x14]'/g, "samplingCalc.divisor : '-'");
    content = content.replace(/samplingCalc\.interval1 : '[\x14]'/g, "samplingCalc.interval1 : '-'");
    content = content.replace(/samplingCalc\.interval2 : '[\x14]'/g, "samplingCalc.interval2 : '-'");

    // "Pilih Line" option with 0x14 used as arrow/bullet
    content = content.replace(/[\x14]\s*Pilih Line\s*[\x14]/g, '— Pilih Line —');

    // Comment dashes (0x14 used as — in comments)
    content = content.replace(/PANEL\s*[\x14]\s*tampil/g, 'PANEL — tampil');
    content = content.replace(/Action bar\s*[\x14]\s*tidak/g, 'Action bar — tidak');
    content = content.replace(/REVISI PANEL\s*[\x14]/g, 'REVISI PANEL —');

    // span with > 0x1D  
    content = content.replace(/<span class="text-lg">\s*[\x1D]\s*<\/span>/g, '<span class="text-lg">&rsaquo;</span>');

    // 0x15 as × close/clear button content  
    content = content.replace(/<button[^>]*>\s*[\x15]\s*<\/button>/g, (match) => {
        return match.replace(/[\x15]/g, '\u00D7');
    });

    // 0x14 in x-text for user name (was placeholder —)
    content = content.replace(/\.nama \|\| '[\x14]'/g, ".nama || '-'");
    // Pilih Foreman / Pilih GL placeholders
    content = content.replace(/[\x14]\s*Pilih Foreman\s*[\x14]/g, '— Pilih Foreman —');
    content = content.replace(/[\x14]\s*Pilih GL\s*[\x14]/g, '— Pilih GL —');

    // TTD Mandiri button prefix
    content = content.replace(/[\x0F]\s*TTD Mandiri Sah/g, '\u270D TTD Mandiri Sah');
    content = content.replace(/[\x0F]\s*TTD/g, '\u270D TTD');

    // Generic: any remaining isolated control chars (0x00-0x1F except tab, already handled \n)
    // Replace with empty string as they're invisible/corrupt
    content = content.replace(/[\x00-\x08\x0B\x0C\x0E-\x12\x16-\x1C\x1E\x1F]/g, '');
    // 0x13, 0x14, 0x15 remaining (unmatched)
    content = content.replace(/[\x13]/g, '\u2713');   // ✓
    content = content.replace(/[\x14]/g, '\u2014');   // —
    content = content.replace(/[\x15]/g, '\u00D7');   // ×
    content = content.replace(/[\x1D]/g, '>');

    return content;
}

// Fix form.blade.php
let blade = fs.readFileSync('resources/views/li/form.blade.php', 'utf8');
const bladeBefore = blade.length;
blade = fixContent(blade);
fs.writeFileSync('resources/views/li/form.blade.php', blade, 'utf8');
console.log('form.blade.php: ' + bladeBefore + ' → ' + blade.length + ' bytes');

// Fix liForm.js  
let js = fs.readFileSync('resources/js/liForm.js', 'utf8');
const jsBefore = js.length;
js = fixContent(js);
fs.writeFileSync('resources/js/liForm.js', js, 'utf8');
console.log('liForm.js: ' + jsBefore + ' → ' + js.length + ' bytes');

// Verify - count remaining bad chars
function countBad(content) {
    let count = 0;
    for (let i = 0; i < content.length; i++) {
        const c = content.charCodeAt(i);
        if ((c < 0x20 && c !== 0x09 && c !== 0x0A) || c === 0xFFFD) count++;
    }
    return count;
}

const bladeRemaining = countBad(fs.readFileSync('resources/views/li/form.blade.php', 'utf8'));
const jsRemaining    = countBad(fs.readFileSync('resources/js/liForm.js', 'utf8'));
console.log('Remaining bad chars → blade:', bladeRemaining, '| js:', jsRemaining);
