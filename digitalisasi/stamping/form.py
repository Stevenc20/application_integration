from django import forms
from . import models


# ----------------- Production Achievement -----------------
class ProductionAchievementForm(forms.ModelForm):
    class Meta:
        model = models.ProductionAchievement
        fields = ["shift", "actual", "current"]


# ----------------- Karyawan -----------------
class KaryawanForm(forms.ModelForm):
    class Meta:
        model = models.karyawan
        fields = ["nama_karyawan", "nrp_karyawan", "jabatan"]


# ----------------- Production Line -----------------
class ProductionLineForm(forms.ModelForm):
    class Meta:
        model = models.productionline
        fields = ["namaline", "shift", "kapasitasline"]


# ----------------- Item Produksi -----------------
class ItemProduksiForm(forms.ModelForm):
    class Meta:
        model = models.itemproduksi
        fields = ["job_number", "part_number", "customer"]


# ----------------- Job -----------------
class JobForm(forms.ModelForm):
    class Meta:
        model = models.job
        fields = ["id_productionline", "id_karyawan", "date"]


# ----------------- Production Plan -----------------
class ProductionPlanForm(forms.ModelForm):
    class Meta:
        model = models.productionplan
        fields = [
            "gsph_plan", "stroke_plan", "repair_plan",
            "reject_plan", "idle_plan", "mp", "ot_plan"
        ]


# ----------------- Detail Job -----------------
class DetailJobForm(forms.ModelForm):
    class Meta:
        model = models.detailjob
        fields = ["id_job", "id_itemproduksi", "plan_qty", "plan_ct", "machine_used"]


# ----------------- Downtime -----------------
class DetailDowntimeForm(forms.ModelForm):
    class Meta:
        model = models.detail_dt
        fields = [
            "jenisdowntime", "problem_dt", "penyebab_dt",
            "action_dt", "start_downtime", "finish_downtime"
        ]


# ----------------- Idle Time -----------------
class DetailIdleForm(forms.ModelForm):
    class Meta:
        model = models.detail_idle
        fields = ["start_idle", "finish_idle", "reason_idle"]


# ----------------- Break Time -----------------
class DetailBreakForm(forms.ModelForm):
    class Meta:
        model = models.detail_breakt
        fields = ["start_break", "finish_break", "break_type"]
