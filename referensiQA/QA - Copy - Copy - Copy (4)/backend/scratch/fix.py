import io

with io.open('resources/js/itemCheckForm.js', 'r', encoding='utf-8') as f:
    content = f.read()

# Replace the mangled texts that are literally in the file
content = content.replace('âœ”ï¸', '✔️')
content = content.replace('âš ï¸', '⚠️')
content = content.replace('â€”', '—')
content = content.replace('ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â', '-')

# Note: sometimes there is a space, e.g. 'âœ”ï¸ ' -> '✔️ '
content = content.replace('✔️ ', '✔️')
content = content.replace('Selesai ✔️', 'Selesai ✔️')

with io.open('resources/js/itemCheckForm.js', 'w', encoding='utf-8') as f:
    f.write(content)

print('Done')
