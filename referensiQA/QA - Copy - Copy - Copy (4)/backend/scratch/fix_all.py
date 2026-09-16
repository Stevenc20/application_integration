import io
import os

files = [
    'resources/js/liForm.js',
    'resources/js/app.js',
    'resources/views/li/form.blade.php',
    'resources/views/item-check/form.blade.php'
]

for file_path in files:
    if os.path.exists(file_path):
        with io.open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()

        new_content = content.replace('âœ”ï¸', '✔️')
        new_content = new_content.replace('âš ï¸', '⚠️')
        new_content = new_content.replace('â€”', '—')
        new_content = new_content.replace('ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â', '-')

        if new_content != content:
            with io.open(file_path, 'w', encoding='utf-8') as f:
                f.write(new_content)
            print('Fixed', file_path)
        else:
            print('No mangled text in', file_path)
