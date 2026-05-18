from django.urls import path
from . import views
from django.contrib.auth import views as auth_views
from django.urls import path, reverse_lazy

urlpatterns = [
    path('login/', views.custom_login_view, name='login'),
    
    path('logout/', auth_views.LogoutView.as_view(next_page=reverse_lazy('login')), name='logout'),

    path("dashboard/", views.dashboard, name="dashboard"),
    path("dashboard/<str:line_name>/", views.dashboard_detail, name="dashboard_detail"),

    #input harian
    path("input-harian/", views.input_harian, name="input_harian"),
    path('input-harian/update/<int:id>/', views.update_single_item, name='update_single_item'),
    # karyawan
    path('karyawan', views.karyawan, name='karyawan'),
    path('karyawan/create_karyawan', views.create_karyawan, name = 'create_karyawan'),
    path('karyawan/update_karyawan/<str:id>', views.update_karyawan, name = 'update_karyawan'),
    path('delete_karyawan/<str:id>', views.delete_karyawan, name='delete_karyawan'),

    path('productionline', views.productionline, name='productionline'),
    path('productionline/create_productionline', views.create_productionline, name = 'create_productionline'),
    path('productionline/update_productionline/<str:id>', views.update_productionline, name = 'update_productionline'),
    path('delete_productionline/<str:id>', views.delete_productionline, name='delete_productionline'),
    path('productionline/rekap_productionline/<str:id>', views.rekap_productionline, name='rekap_productionline'),

    path('itemproduksi', views.itemproduksi, name='itemproduksi'),
    path('itemproduksi/create_itemproduksi', views.create_itemproduksi, name = 'create_itemproduksi'),
    path('itemproduksi/update_itemproduksi/<str:id>', views.update_itemproduksi, name = 'update_itemproduksi'),
    path('delete_itemproduksi/<str:id>', views.delete_itemproduksi, name='delete_itemproduksi'),

    path('job', views.job, name='job'),
    path('job/create_job', views.create_jobdetails, name = 'create_job'),
    path('job/update_job/<str:id>', views.update_job, name = 'update_job'),
    path('delete_job/<str:id>', views.delete_job, name='delete_job'),
    
    path('productionplan/rekap/<int:id>/', views.rekap_productionplan, name='rekap_productionplan'),
    path('productionplan/update/<int:id>/', views.update_productionplan, name='update_productionplan'),

    path('detailjob/rekap_detailjob/<str:id>', views.rekap_detailjob, name='rekap_detailjob'),
    path('detailjob/create_detailjob/<str:id>', views.create_detailjob, name='create_detailjob'),
    path('detailjob/update_qty_single/<int:id>/', views.update_actual_qty_single, name='update_actual_qty_single'),
    path('delete_detailjob/<str:id>', views.delete_detailjob, name='delete_detailjob'),
    #tambahan
    path('detailjob/update_qty/', views.update_actual_qty, name='update_actual_qty'),
    path('detailjob/update_detailjob/<str:id>/', views.update_detailjob, name='update_detailjob'),
    
    path('downtime/pilih-job/', views.pilih_job_untuk_downtime, name='pilih_job_downtime'),
    path('downtime/rekap_detailjob/<str:id>', views.rekap_downtime, name='rekap_downtime'),
    path('downtime', views.downtime, name='downtime'),
    path('downtime/create/', views.create_downtime, name='create_downtime'),
    path('delete_downtime/<int:id>/', views.delete_downtime, name='delete_downtime'),
    path('stop_downtime/<int:id>/', views.stop_downtime, name='stop_downtime'), 
    path('downtime/update_detailjob/<str:id>', views.update_downtime, name='update_downtime'),

    # DANDORI - REWORK
    path('dandori/', views.dandori_main, name='dandori_main'),
    path('dandori/list/<int:id>/', views.list_dandori, name='list_dandori'),
    path('dandori/start/<int:id>/<str:dandori_type>/', views.start_dandori, name='start_dandori'),
    path('dandori/stop/<int:id>/', views.stop_dandori, name='stop_dandori'),
    path('dandori/restart/<int:id>/', views.restart_dandori, name='restart_dandori'),
    
    path('idletime/', views.idletime_main, name='idletime_main'),
    path('idletime/rekap_idletime/<int:id>/', views.rekap_idletime, name='rekap_idletime'),
    path('idletime/create_idletime/<str:id>', views.create_idletime, name='create_idletime'),
    path('stop_idle/<str:id>', views.stop_idletime, name='stop_idle'),
    path('idletime/update_idletime/<str:id>', views.update_idletime, name='update_idletime'),
    path('delete_idletime/<str:id>', views.delete_idletime, name='delete_idletime'),

    # URL untuk Break Time
    path('breaktime/', views.break_time_list, name='break_time_list'),
    path('breaktime/create/', views.break_time_create, name='break_time_create'),
    path('breaktime/update/<int:id>/', views.break_time_update, name='break_time_update'),
    path('breaktime/delete/<int:id>/', views.break_time_delete, name='break_time_delete'),

    path('handwork/', views.handwork_main, name='handwork_main'),
    path('handwork/rekap/<int:id>/', views.rekap_handwork, name='rekap_handwork'),
    path('handwork/delete/<int:id>/', views.delete_handwork_item, name='delete_handwork_item'),

    #QCHECK - FINAL
    path('qcheck/', views.qcheck_main, name='qcheck_main'),
    path('qcheck/select-item/', views.qcheck_select_item, name='qcheck_select_item'),
    path('qcheck/list/<int:id>/', views.list_qcheck, name='list_qcheck'),
    path('qcheck/start/<int:id>/<str:qcheck_type>/', views.start_qcheck, name='start_qcheck'),
    path('qcheck/restart/<int:id>/', views.restart_qcheck, name='restart_qcheck'),
    path('qcheck/control/<int:id>/<str:action>/', views.control_qcheck, name='control_qcheck'),
    path('qcheck/edit/<int:id>/', views.edit_qcheck, name='edit_qcheck'),
    path('qcheck/delete/<int:id>/', views.delete_qcheck, name='delete_qcheck'),

    path('laporan', views.laporan_harian, name='laporan'),

    path('api/get_jobs/', views.api_get_jobs_by_line_shift, name='api_get_jobs'),
    path('api/get-cycle-time/<int:item_id>/', views.get_cycle_time, name='api_get_cycle_time'),

    path('laporan/export/excel/', views.export_laporan_excel, name='export_laporan_excel'),
    path('laporan/export/pdf/', views.export_laporan_pdf, name='export_laporan_pdf'),
]