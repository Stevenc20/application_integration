import os, re

def refactor_roles(directory):
    for root, _, files in os.walk(directory):
        for f in files:
            if not f.endswith('.blade.php'): continue
            path = os.path.join(root, f)
            with open(path, 'r', encoding='utf-8') as file:
                content = file.read()
            
            # Auth::user()->role
            new_content = re.sub(r'(Auth::user\(\)(?:\?)?)->role\s*==(?:=)?\s*([\'"][^\'"]+[\'"])', r'\1->isRole(\2)', content, flags=re.IGNORECASE)
            new_content = re.sub(r'(Auth::user\(\)(?:\?)?)->role\s*!=(?:=)?\s*([\'"][^\'"]+[\'"])', r'!\1->isRole(\2)', new_content, flags=re.IGNORECASE)
            
            # auth()->user()->role
            new_content = re.sub(r'(auth\(\)->user\(\)(?:\?)?)->role\s*==(?:=)?\s*([\'"][^\'"]+[\'"])', r'\1->isRole(\2)', new_content, flags=re.IGNORECASE)
            new_content = re.sub(r'(auth\(\)->user\(\)(?:\?)?)->role\s*!=(?:=)?\s*([\'"][^\'"]+[\'"])', r'!\1->isRole(\2)', new_content, flags=re.IGNORECASE)

            # \->role
            new_content = re.sub(r'(\\\$\w+)->role\s*==(?:=)?\s*([\'"][^\'"]+[\'"])', r'\1->isRole(\2)', new_content)
            new_content = re.sub(r'(\\\$\w+)->role\s*!=(?:=)?\s*([\'"][^\'"]+[\'"])', r'!\1->isRole(\2)', new_content)

            if new_content != content:
                with open(path, 'w', encoding='utf-8') as file:
                    file.write(new_content)
                print('Refactored:', path)

refactor_roles('resources/views')
