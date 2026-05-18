# digitalisasi/urls.py

from django.contrib import admin
from django.urls import path, include
from django.conf import settings
from django.conf.urls.static import static
from stamping import views

urlpatterns = [
    # Dashboard
    path('', views.dashboard, name='home'),
    path('dashboard/', views.dashboard, name='dashboard'),

    # Data Master
    path('karyawan/', views.karyawan, name='karyawan'),
    path('productionline/', views.productionline, name='productionline'),
    path('itemproduksi/', views.itemproduksi, name='itemproduksi'),

    # Data Operasional
    path('job/', views.job, name='job'),
    path('downtime/', views.downtime, name='downtime'),
    #path('idletime/', views.idletime, name='idletime'),
    path('breaktime/', views.break_time_list, name='break_time_list'),
    
    # Laporan
    path('laporan/', views.laporan, name='laporan'),

    # Admin
    path('admin/', admin.site.urls),
    
    path('', include('stamping.urls')),
] + static(settings.STATIC_URL, document_root=settings.STATIC_ROOT)

if settings.DEBUG:
    urlpatterns += static(settings.MEDIA_URL, document_root=settings.MEDIA_ROOT)