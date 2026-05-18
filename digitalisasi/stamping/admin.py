from django.contrib import admin
from . import models

# Register your models here.
admin.site.register(models.karyawan)
admin.site.register(models.productionline)
admin.site.register(models.itemproduksi)

admin.site.register(models.job)
admin.site.register(models.productionplan)
admin.site.register(models.machine)
admin.site.register(models.detailjob)

admin.site.register(models.downtime)
admin.site.register(models.detail_dt)
admin.site.register(models.dandori)
#admin.site.register(models.detail_dd)
admin.site.register(models.idletime)
admin.site.register(models.detail_idle)
admin.site.register(models.BreakTime)
admin.site.register(models.handwork)
admin.site.register(models.detailhandwork)
admin.site.register(models.qcheck)
