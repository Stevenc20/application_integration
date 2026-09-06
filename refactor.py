import os, re

def refactor_roles(directory):
    for root, _, files in os.walk(directory):
        for f in files:
            if not f.endswith('.php'): continue
            path = os.path.join(root, f)
            with open(path, 'r', encoding='utf-8') as file:
                content = file.read()
            
            new_content = re.sub(r'(\$\w+(?:->user\(\))?)->role\s*===\s*([\'"][^\'"]+[\'"])', r'\1->isRole(\2)', content)
            new_content = re.sub(r'(\$\w+(?:->user\(\))?)->role\s*!==\s*([\'"][^\'"]+[\'"])', r'!\1->isRole(\2)', new_content)
            
            new_content = re.sub(r'(auth\(\)->user\(\)\?)->role\s*===\s*([\'"][^\'"]+[\'"])', r'\1->isRole(\2)', new_content)
            new_content = re.sub(r'(auth\(\)->user\(\)\?)->role\s*!==\s*([\'"][^\'"]+[\'"])', r'!\1->isRole(\2)', new_content)
            
            new_content = re.sub(r'in_array\(\s*(\$\w+(?:->user\(\))?(?:\?)?)->role\s*,\s*(\[[^\]]+\])(?:\s*,\s*(?:true|false))?\s*\)', r'\1->isRole(\2)', new_content)
            new_content = re.sub(r'!\s*in_array\(\s*(\$\w+(?:->user\(\))?(?:\?)?)->role\s*,\s*(\[[^\]]+\])(?:\s*,\s*(?:true|false))?\s*\)', r'!\1->isRole(\2)', new_content)

            if new_content != content:
                with open(path, 'w', encoding='utf-8') as file:
                    file.write(new_content)
                print('Refactored:', path)

refactor_roles('app/Http/Controllers')
