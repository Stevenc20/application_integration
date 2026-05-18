from .models import productionline

def is_admin(request):
    result = request.user.groups.filter(name='Admin').exists() # 'Admin' bukan 'admin'
    print(f"DEBUG: is_admin = {result}") 
    return {'is_admin': result}

def is_operator(request):
     result = request.user.groups.filter(name='Operator').exists() 
     print(f"DEBUG: is_operator = {result}") 
     return {'is_operator': result}

def is_leader(request):
    result = request.user.groups.filter(name='Leader').exists() # 'Leader' bukan 'leader'
    print(f"DEBUG: is_leader = {result}") 
    return {'is_leader': result}

def is_foreman(request):
    result = request.user.groups.filter(name='Foreman').exists() # 'Foreman' bukan 'foreman'
    print(f"DEBUG: is_foreman = {result}") 
    return {'is_foreman': result}

def is_supervisor(request):
    result = request.user.groups.filter(name='Supervisor').exists() # 'Supervisor' bukan 'supervisor'
    print(f"DEBUG: is_supervisor = {result}") 
    return {'is_supervisor': result}

#def production_lines_processor(request):
    lines = productionline.objects.all().order_by('namaline')
    return {'all_production_lines': lines}

def sidebar_context(request):
    all_lines = productionline.objects.all().order_by('namaline')
    
    unique_lines_for_sidebar = []
    seen_names = set()

    for line in all_lines:
        if line.namaline not in seen_names:
            unique_lines_for_sidebar.append(line)
            seen_names.add(line.namaline)
            
    return {
        'sidebar_lines': unique_lines_for_sidebar
    }
