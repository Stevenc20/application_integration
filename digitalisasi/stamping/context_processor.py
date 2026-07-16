from .models import productionline

def is_admin(request):
    result = request.user.groups.filter(name='Admin').exists()
    return {'is_admin': result}

def is_operator(request):
     result = request.user.groups.filter(name='Operator').exists()
     return {'is_operator': result}

def is_leader(request):
    result = request.user.groups.filter(name='Leader').exists()
    return {'is_leader': result}

def is_foreman(request):
    result = request.user.groups.filter(name='Foreman').exists()
    return {'is_foreman': result}

def is_supervisor(request):
    result = request.user.groups.filter(name='Supervisor').exists()
    return {'is_supervisor': result}

def is_manager(request):
    result = request.user.groups.filter(name='Manager').exists()
    return {'is_manager': result}

def is_kadiv(request):
    result = request.user.groups.filter(name='Kadiv').exists()
    return {'is_kadiv': result}

def is_direktur(request):
    result = request.user.groups.filter(name='Direktur').exists()
    return {'is_direktur': result}

def is_presdir(request):
    result = request.user.groups.filter(name='Presdir').exists()
    return {'is_presdir': result}

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
