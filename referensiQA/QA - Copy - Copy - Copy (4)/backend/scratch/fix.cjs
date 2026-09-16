const fs = require('fs');
let c = fs.readFileSync('resources/views/li/form.blade.php', 'utf8');

c = c.replace(/=\?\s*Darurat Foreman/g, '⚠️ Darurat Foreman');
c = c.replace(/\?\s*Kirim ke Foreman/g, '📤 Kirim ke Foreman');
c = c.replace(/'Kirim ke Foreman'/g, '\'📤 Kirim ke Foreman\'');
c = c.replace(/'\?\s*Simpan & Selesai'/g, '\'✅ Simpan & Selesai\'');
c = c.replace(/Simpan Draft/g, '💾 Simpan Draft');
c = c.replace(/ðŸ’¾/g, '💾');
c = c.replace(/âœ…/g, '✅');
c = c.replace(/â Œ/g, '❌');
c = c.replace(/ðŸ”„/g, '🔄');
c = c.replace(/âœ ï¸ /g, '✏️');
c = c.replace(/ðŸ” /g, '🔍');
c = c.replace(/âš ï¸ /g, '⚠️');
c = c.replace(/ðŸ“¤/g, '📤');

// Also fix the database values for dimStd in li_templates if needed, or just let them re-save it.
// The user noted the "Ã~ 14 MM+0.1/-0.1". This is in the DB, not blade. 
// "Ø 14 MM+0.1/-0.1" got saved as "Ã~ 14 MM+0.1/-0.1".

fs.writeFileSync('resources/views/li/form.blade.php', c, 'utf8');
console.log('Fixed blade file');
