from django.db import models
from django.utils import timezone
from django.core.validators import MinValueValidator, MaxValueValidator
import os
from django.utils.deconstruct import deconstructible

# upload foto dan simpan berdasarkan problem
@deconstructible
class PathForFotoProblem(object):
    def __call__(self, instance, filename):
        return os.path.join("photo_problems", f"problem_{instance.id_problem.id_problem}", filename)

@deconstructible
class PathForFotoRC(object):
    def __call__(self, instance, filename):
        return os.path.join("rootcause_problems", f"problem_{instance.id_rc.id_problem.id_problem}", filename)

# DATA MASTER
class karyawan(models.Model):
    opsi_jabatan = [
        ('admin', 'admin'),
        ('operator', 'operator'),
        ('leader', 'leader'),
        ('foreman', 'foreman'),
        ('supervisor', 'supervisor'),
    ]
    id_karyawan = models.AutoField(primary_key=True)
    nama_karyawan = models.CharField(max_length=100)
    nrp_karyawan = models.CharField(max_length=20, unique=True)
    jabatan = models.CharField(choices=opsi_jabatan, max_length=25)

    def __str__(self):
        return str(self.id_karyawan)
    
class productionline(models.Model):
    namaline = models.CharField(max_length=50)
    shift = models.IntegerField() 
    kapasitasline = models.PositiveIntegerField(null=True, blank=True)

    def __str__(self):
        return f"{self.namaline} - Shift {self.shift}"
    
    def get_slug(self):
        return self.namaline.replace(' ', '-').lower()

    def __str__(self):
        return self.namaline

    # Menambahkan constraint untuk mencegah data duplikat
    class Meta:
        constraints = [
            models.UniqueConstraint(fields=['namaline', 'shift'], name='unique_line_shift_combination')
        ]

class itemproduksi(models.Model):
    id_itemproduksi = models.AutoField(primary_key=True)
    job_number = models.CharField(max_length=50)
    part_number = models.CharField(max_length=50)
    customer = models.CharField(max_length=100)
    
    production_lines = models.ManyToManyField('productionline', blank=True, related_name='items_produksi')
    cycle_time = models.FloatField(default=0.0)

    def __str__(self):
        return self.part_number

# DATA OPERASIONAL
class job(models.Model):
    id_job = models.AutoField(primary_key=True)
    id_productionline = models.ForeignKey(productionline, on_delete=models.CASCADE)
    id_karyawan = models.ForeignKey(karyawan, on_delete=models.CASCADE)
    date = models.DateField()
    plan_repair_percentage = models.FloatField(default=0.0, null=True, blank=True)
    plan_reject_percentage = models.FloatField(default=0.0, null=True, blank=True)


    def __str__(self):
        return str(self.id_job)
    
class productionplan(models.Model):
    id_production_plan = models.AutoField(primary_key=True)
    id_job = models.OneToOneField(job, on_delete=models.CASCADE, related_name='productionplan')
    
    # --- Data Plan ---
    gsph_plan = models.PositiveIntegerField()
    stroke_plan = models.PositiveIntegerField()
    repair_plan = models.DecimalField(max_digits=5, decimal_places=2, validators=[MinValueValidator(0), MaxValueValidator(100)], default=0.00)
    reject_plan = models.DecimalField(max_digits=5, decimal_places=2, validators=[MinValueValidator(0), MaxValueValidator(100)], default=0.00)
    idle_plan = models.PositiveIntegerField()
    mp = models.PositiveIntegerField()
    ot_plan = models.PositiveIntegerField()

    gsph_actual = models.PositiveIntegerField(default=0)
    stroke_actual = models.PositiveIntegerField(default=0)
    ot_actual = models.PositiveIntegerField(default=0)

    def __str__(self):
        return f"Production Plan for Job {self.id_job.id_job}"
    
    # biar dinamis, jadi diakumulasikan dg downtime aja.. bukan input manual
    @property
    def total_downtimes(self):
        downtimes = downtime.objects.filter(id_detail_job__id_job = self.id_job)
        result = {
            'prod_t' : 0,
            'mat_t' : 0,
            'dies_t' : 0,
            'mach_t' : 0,
            'log_t' : 0,
        }
        for dt in downtimes:
            if dt.jenisdowntime in result:
                result[dt.jenisdowntime] += dt.duration_minutes

        return result
    
    @property
    def hitung_prod (self):
        return self.total_downtimes['prod_t']
    
    @property
    def hitung_mat (self):
        return self.total_downtimes['mat_t']
    
    @property
    def hitung_dies (self):
        return self.total_downtimes['dies_t']
    
    @property
    def hitung_mach (self):
        return self.total_downtimes['mach_t']
    
    @property
    def hitung_log (self):
        return self.total_downtimes['log_t']

class machine(models.Model):
    code = models.CharField(max_length=10, unique = True)

    def __str__(self):
        return self.code

class detailjob(models.Model):
    id_detailjob = models.AutoField(primary_key=True)
    id_job = models.ForeignKey(job, on_delete=models.CASCADE, related_name="detail_jobs")
    id_itemproduksi = models.ForeignKey('itemproduksi', on_delete=models.CASCADE)
    machine_used = models.ManyToManyField(machine, related_name = "detail_jobs")
    plan_qty = models.PositiveIntegerField()
    plan_ct = models.DecimalField(max_digits=6, decimal_places=2)
    actual_qty = models.PositiveIntegerField(
        default=0, 
        help_text="Jumlah kuantitas aktual yang sudah selesai dikerjakan."
    )
    urutan = models.PositiveIntegerField(default=0, help_text="Urutan job dalam satu hari (0, 1, 2, ...)")
    repair_staging = models.PositiveIntegerField(default=0, verbose_name="Repair (Staging)")
    reject_staging = models.PositiveIntegerField(default=0, verbose_name="Reject (Staging)")

    @property
    def actual_good(self):
        return self.actual_qty
        
    @property
    def actual_repair(self):
        return sum(hw.qty_ok for hw in self.handworks.all()) or 0
    
    @property
    def reject_stamping(self):
        return sum(p.qty_stamping for p in self.problems.all() if hasattr(p, "qty_stamping")) or 0

    @property
    def reject_handwork(self):
        return sum(hw.qty_reject for hw in self.handworks.all()) or 0
    
    @property
    def actual_reject(self):
        return (self.reject_staging + self.reject_handwork + self.reject_stamping) or 0

    @property
    def total_downtime_minutes(self):
        total = 0
        for downtime in self.downtimes.all():
            details = downtime.detail_dt_set.all()  
            for dt in details:
                total += dt.duration_downtime or 0
        return total

    @property
    def total_dandori_minutes(self):
    # Ini adalah cara baru yang benar
        return sum(d.duration for d in self.dandoris.all() if d.duration is not None)

    @property
    def total_idle_minutes(self):
        return sum([idle.total_idle or 0 for idle in self.idle_times.all()], 0)

    @property
    def total_break_minutes(self):
        return sum((br.total_break or 0) for br in self.break_times.all())
    
    @property
    def total_problems(self):
        return self.problems.count()
    
    @property
    def total_qcheck_minutes(self):
        return sum(qc.duration for qc in self.qchecks.all())

    def __str__(self):
        return f"Detail Job {self.id_detailjob}"
    
class ActualItem(models.Model):
    id_actual_item = models.AutoField(primary_key=True)
    id_detailjob = models.ForeignKey(detailjob, on_delete=models.CASCADE, related_name="actual_items")
    waktu_selesai = models.DateTimeField(auto_now_add=True)
    
    def __str__(self):
        return f"Item selesai untuk Job {self.id_detailjob.id_detailjob} pada {self.waktu_selesai}"

class problem(models.Model):
    id_problem = models.AutoField(primary_key=True)
    id_detailjob = models.ForeignKey(detailjob, on_delete=models.CASCADE, related_name="problems")
    jenis_masalah = models.CharField(max_length=100)
    action_prob = models.TextField(blank=True, null=True)
    notes_prob = models.TextField(blank=True, null=True)
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"Problem {self.id} - DetailJob {self.id_detailjob_id}"

class fotoproblem(models.Model):
    id_fotoprob = models.AutoField(primary_key=True)
    id_problem = models.ForeignKey(problem, on_delete=models.CASCADE, related_name="photos")
    foto_problem = models.ImageField(upload_to=PathForFotoProblem(), blank=True, null=True)

    def __str__(self):
        return self.foto_problem.name
    
class rootcause(models.Model):
    id_rc = models.AutoField(primary_key=True)
    id_problem = models.ForeignKey(problem, on_delete=models.CASCADE, related_name="root_causes")
    deskripsi_rc = models.CharField(max_length=500)

    def __str__(self):
        return f"RootCause {self.id} - Problem {self.problem_id}"

class fotorc(models.Model):
    id_fotorc = models.AutoField(primary_key=True)
    id_rc = models.ForeignKey(rootcause, on_delete=models.CASCADE, related_name="photos")
    fotorc = models.ImageField(upload_to=PathForFotoRC(), blank=True, null=True)

    def __str__(self):
        return self.fotorc.name

class downtime(models.Model):
    id_downtime = models.AutoField(primary_key=True)
    id_detailjob = models.ForeignKey(detailjob, on_delete=models.CASCADE, related_name="downtimes")

    def __str__(self):
        return str(self.id_downtime)

    @property
    def total_duration(self, as_float=True):
        total = sum(dt.duration_downtime(as_float=as_float) for dt in self.downtimes.all())
        return total

    @property
    def count_opsidt(self):
        count_dt = {code: 0 for code, _ in detail_dt.opsi_downtime}  # inisialisasi semua jenis
        for dt in self.downtimes.all():
            if dt.jenisdowntime in count_dt:
                count_dt[dt.jenisdowntime] += 1
        return count_dt

class detail_dt(models.Model):
    opsi_downtime = [
        ('prod_t', 'prod_t'),
        ('mat_t', 'mat_t'),
        ('dies_t', 'dies_t'),
        ('mach_t', 'mach_t'),
        ('log_t', 'log_t')
    ]
    id_detaildt = models.AutoField(primary_key=True)
    id_downtime = models.ForeignKey(downtime, on_delete=models.CASCADE)
    jenisdowntime = models.CharField(choices=opsi_downtime, max_length = 10)
    
    problem_dt = models.TextField(blank=True, null=True)
    penyebab_dt = models.TextField(blank=True, null=True)
    action_dt = models.TextField(blank=True, null=True)

    start_downtime = models.DateTimeField(default=timezone.now)
    finish_downtime = models.DateTimeField(blank=True, null=True)
    pic_downtime = models.CharField(max_length=100, blank=True, null=True)

    @property
    def duration_downtime(self, as_float=True):
        if self.start_downtime and self.finish_downtime:
            total_minutes = (self.finish_downtime - self.start_downtime).total_seconds() / 60
            return total_minutes if as_float else int(total_minutes // 1)
        return 0
    
    def __str__(self):
        return str(self.id_detaildt)


class dandori(models.Model):
    DANDORI_TYPES = [
        ("waktu_dandori", "Waktu Dandori"),
        ("material_change", "Material Change Time"),
        ("variant_change", "Variant Change Time"),
    ]
    
    id_dandori = models.AutoField(primary_key=True)
    id_detailjob = models.ForeignKey(detailjob, on_delete=models.CASCADE, related_name='dandoris')
    jenis_dandori = models.CharField(max_length=30, choices=DANDORI_TYPES)

    # --- Field baru untuk timer ---
    start_time = models.DateTimeField(null=True, blank=True)
    finish_time = models.DateTimeField(null=True, blank=True)

    def __str__(self):
        return f"Dandori {self.get_jenis_dandori_display()} for {self.id_detailjob}"
    
    # --- Property baru untuk durasi ---
    @property
    def duration(self):
        if self.start_time and self.finish_time:
            return round((self.finish_time - self.start_time).total_seconds() / 60, 2)
        elif self.start_time and not self.finish_time:
            return round((timezone.now() - self.start_time).total_seconds() / 60, 2)
        return 0

class idletime(models.Model):
    id_idle = models.AutoField(primary_key=True)
    id_detailjob = models.ForeignKey(detailjob, on_delete=models.CASCADE, related_name="idle_times")

    def __str__(self):
        return str(self.id_idle)
    
    @property
    def total_idle(self):
        return sum(d.duration_idle for d in self.detail_idle_set.all())
    
class detail_idle(models.Model):
    id_detaild = models.AutoField(primary_key=True)
    id_idle = models.ForeignKey(idletime, on_delete=models.CASCADE)
    
    start_idle = models.DateTimeField(default=timezone.now)
    finish_idle = models.DateTimeField(blank=True, null=True)
    reason_idle = models.TextField(blank=True, null=True)  # opsional

    @property
    def duration_idle(self, as_float=True):
        if self.start_idle and self.finish_idle:
            total_minutes = (self.finish_idle - self.start_idle).total_seconds() / 60
            return total_minutes if as_float else int(total_minutes // 1)
        return 0

    def __str__(self):
        return str(self.id_detaild)


class BreakTime(models.Model):
    # Pilihan hari kerja dari Senin - Sabtu
    class Hari(models.IntegerChoices):
        SENIN = 0, 'Senin'
        SELASA = 1, 'Selasa'
        RABU = 2, 'Rabu'
        KAMIS = 3, 'Kamis'
        JUMAT = 4, 'Jumat'
        SABTU = 5, 'Sabtu'

    nama_istirahat = models.CharField(max_length=100, default='istirahat')
    waktu_mulai = models.TimeField()
    waktu_selesai = models.TimeField()
    shift = models.IntegerField(default=1)
    hari = models.IntegerField(
        choices=Hari.choices, 
        blank=True, 
        null=True, 
        help_text="Kosongkan jika istirahat ini berlaku setiap hari (kecuali Minggu)"
    )

    def __str__(self):
        return f"{self.nama_istirahat} (Shift {self.shift})"

class handwork(models.Model):
    id_handwork = models.AutoField(primary_key=True)
    id_detailjob = models.ForeignKey(detailjob, on_delete=models.CASCADE, related_name="handworks")
    qty_repair = models.PositiveIntegerField(default=0)

    ct1_start = models.DateTimeField(null=True, blank=True)
    ct1_finish = models.DateTimeField(null=True, blank=True)
    ct2_start = models.DateTimeField(null=True, blank=True)
    ct2_finish = models.DateTimeField(null=True, blank=True)
    ct3_start = models.DateTimeField(null=True, blank=True)
    ct3_finish = models.DateTimeField(null=True, blank=True)


    @property
    def qty_ok(self):
        return self.detail_items.filter(is_ok=True).count()

    @property
    def qty_reject(self):
        return self.detail_items.filter(is_ok=False).count()
    
    def __str__(self):
        return str(self.id_handwork)
    
class detailhandwork(models.Model):
    id_detailhw = models.AutoField(primary_key=True)
    id_handwork = models.ForeignKey(handwork, on_delete=models.CASCADE, related_name='detail_items')
    problem_hw = models.TextField(null=True, blank=True)
    is_ok = models.BooleanField(default=False)
    foto_sebelum = models.ImageField(upload_to='handwork_photos/', null=True, blank=True)
    foto_sesudah = models.ImageField(upload_to='handwork_photos/', null=True, blank=True)
    
    created_at = models.DateTimeField(auto_now_add=True, null=True, blank=True)

    def __str__(self):
        return f"Detail Handwork for {self.id_handwork}"

class qcheck(models.Model):
    QC_TYPES = [
        ("initial_qcheck", "Initial Q Check"),
        ("material_change_check", "Material Change Check"),
        ("variant_change_check", "Variant Change Check (SME)"),
    ]
    
    id_qcheck = models.AutoField(primary_key=True)
    id_detailjob = models.ForeignKey(detailjob, on_delete=models.CASCADE, related_name="qchecks")
    jenis_qcheck = models.CharField(max_length=30, choices=QC_TYPES)
    hasil_qcheck = models.CharField(max_length=100)
    keterangan = models.TextField(blank=True, null=True)
    
    # --- FIELD BARU UNTUK WAKTU ---
    start_time = models.DateTimeField(null=True, blank=True)
    finish_time = models.DateTimeField(null=True, blank=True)

    def __str__(self):
        return f"{self.get_jenis_qcheck_display()} - {self.hasil_qcheck}"

    # --- PROPERTY BARU UNTUK MENGHITUNG DURASI DALAM MENIT ---
    @property
    def duration(self):
        if self.start_time and self.finish_time:
            return round((self.finish_time - self.start_time).total_seconds() / 60, 2)
        elif self.start_time and not self.finish_time:
            # Jika sedang berjalan, hitung durasi sampai sekarang
            return round((timezone.now() - self.start_time).total_seconds() / 60, 2)
        return 0