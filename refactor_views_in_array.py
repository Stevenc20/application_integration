import os, re
def refactor_roles(directory):
    for root, _, files in os.walk(directory):
        for f in files:
            if not f.endswith('.blade.php'): continue
            path = os.path.join(root, f)
            with open(path, 'r', encoding='utf-8') as file:
                content = file.read()
            
            new_content = re.sub(r'in_array\(strtolower\(\->role\),\s*(\[[^\]]+\])\)', r'\->isRole(\1)', content)
            new_content = re.sub(r'in_array\(auth\(\)->user\(\)->role,\s*(\[[^\]]+\])\)', r'auth()->user()->isRole(\1)', new_content)
            new_content = re.sub(r'in_array\(strtolower\(auth\(\)->user\(\)->role\),\s*(\[[^\]]+\])\)', r'auth()->user()->isRole(\1)', new_content)

            if new_content != content:
                with open(path, 'w', encoding='utf-8') as file:
                    file.write(new_content)
                print('Refactored:', path)

refactor_roles('resources/views')
