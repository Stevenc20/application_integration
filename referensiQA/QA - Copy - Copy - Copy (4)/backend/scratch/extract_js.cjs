const fs = require('fs');
let content = fs.readFileSync('resources/views/dashboard.blade.php', 'utf8');

// Replace blade tags to make it valid JS
content = content.replace(/{{.*?}}/g, '1');
content = content.replace(/@json\((.*?)\)/g, '[]');
content = content.replace(/@auth|@endauth|@if.*?|@else|@endif/g, '');

let jsCode = "";
const scriptRegex = /<script>([\s\S]*?)<\/script>/g;
let match;
while ((match = scriptRegex.exec(content)) !== null) {
    jsCode += match[1] + "\n";
}

fs.writeFileSync('test_js.js', jsCode);
