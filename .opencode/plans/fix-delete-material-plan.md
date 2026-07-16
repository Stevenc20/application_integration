# Plan: Fix Delete Material & Cleanup

## Priority 1: Fix Delete Material (3 sub-tasks)

### Task 1.1: Fix Konflik JS `openDeleteModal`

**File:** `resources/views/materials/index.blade.php`

**Perubahan:**
1. Line 157 — ganti `onclick="openDeleteModal(this)"` jadi `onclick="openMaterialDeleteModal(this)"`
2. Line 435-441 — ganti `function openDeleteModal(button) {` jadi `function openMaterialDeleteModal(button) {`

**File:** `resources/views/materials/show.blade.php`

**Perubahan:**
1. Line 116 — ganti `onclick="openDeleteModal(this)"` jadi `onclick="openMaterialDeleteModal(this)"`
2. Line 325-331 — ganti `function openDeleteModal(button) {` jadi `function openMaterialDeleteModal(button) {`

---

### Task 1.2: Tambah `cascadeOnDelete` di `skm_order_items.material_id`

**Buat file baru:** `database/migrations/YYYY_MM_DD_HHMMSS_add_cascade_delete_to_skm_order_items.php`

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skm_order_items', function (Blueprint $table) {
            $table->dropForeign(['material_id']);
            $table->foreign('material_id')->references('id')->on('materials')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('skm_order_items', function (Blueprint $table) {
            $table->dropForeign(['material_id']);
            $table->foreign('material_id')->references('id')->on('materials');
        });
    }
};
```

**Jalankan:** `php artisan migrate`

---

### Task 1.3: Fix Asset Loading Fallback di `app.blade.php`

**File:** `resources/views/layouts/app.blade.php`

Masalah: Logic `$isLocal && $hasHot` kaku — kalau `hot` file ada tapi Vite mati, atau kalau `hot` file & manifest tidak ada, halaman tanpa CSS/JS.

**Perbaikan:**

Tambahkan conditional logic yang lebih baik:

```php
@php
    $isLocal = in_array(request()->getHost(), ['localhost', '127.0.0.1', '::1']);
    $hasHot = file_exists(public_path('hot'));
    
    $useVite = $isLocal && $hasHot;
    
    $buildCssUrl = '';
    $buildJsUrl = '';
    
    if (!$useVite) {
        // Try reading manifest if not using Vite dev server
        $manifestPath = public_path('build/manifest.json');
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            if (isset($manifest['resources/css/app.css']['file'])) {
                $buildCssUrl = asset('build/' . $manifest['resources/css/app.css']['file']);
            }
            if (isset($manifest['resources/js/app.js']['file'])) {
                $buildJsUrl = asset('build/' . $manifest['resources/js/app.js']['file']);
            }
        }
    }
@endphp

@if($useVite)
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@else
    @if($buildCssUrl)
        <link rel="stylesheet" href="{{ $buildCssUrl }}">
    @endif
    @if($buildJsUrl)
        <script type="module" src="{{ $buildJsUrl }}"></script>
    @endif
@endif
```

---

## Priority 2: Cleanup File Ad-hoc di Root

### Hapus file one-time/tidak terpakai:

```
fix_*.php           (9 files)
modernize_*.php     (6 files)
check_*.php         (2 files)
inject_trycatch.php
_debug_*.php        (2 files)
_test_view.php
test_*.php          (2 files)
test_*.js           (2 files)
test_*.html         (2 files)
html_output.txt
clean.json
output.json
update_role.php
route_info.php
scratch_seed_line_a.php
```

**Pindahkan yang masih berguna:**
- `route_info.php` → `tools/route_info.php`
- `scratch_seed_line_a.php` → `tools/scratch_seed_line_a.php`

**Hapus folder:**
- `scratch/` (25 file internal)
- `scripts/` (3 file internal)

---

## Priority 3: Reorganisasi Controller PPC

Pindahkan controller berikut dari `app/Http/Controllers/` ke `app/Http/Controllers/Ppc/`:

| File | Asal | Tujuan |
|------|------|--------|
| StockController.php | `app/Http/Controllers/` | `app/Http/Controllers/Ppc/StockController.php` |
| RundownController.php | `app/Http/Controllers/` | `app/Http/Controllers/Ppc/RundownController.php` |
| RundownPressController.php | `app/Http/Controllers/` | `app/Http/Controllers/Ppc/RundownPressController.php` |
| BomController.php | `app/Http/Controllers/` | `app/Http/Controllers/Ppc/BomController.php` |
| ProductionOrderController.php | `app/Http/Controllers/` | `app/Http/Controllers/Ppc/ProductionOrderController.php` |
| MrpController.php | `app/Http/Controllers/` | `app/Http/Controllers/Ppc/MrpController.php` |
| MasterStampingController.php | `app/Http/Controllers/` | `app/Http/Controllers/Ppc/MasterStampingController.php` |
| ProductionPlanController.php | `app/Http/Controllers/Planning/` | `app/Http/Controllers/Ppc/ProductionPlanController.php` |

**Catatan:** Controller ini mungkin dipakai bersama role lain. Update semua `use` statement di file yang mereferensi dan route di `web.php`.

---

## Urutan Eksekusi

1. **Task 1.1** → Fix JS conflict (2 files, rename function)
2. **Task 1.2** → Buat migration + jalankan `php artisan migrate`
3. **Task 1.3** → Fix asset loading fallback
4. **Test delete material** → Verifikasi sudah berfungsi
5. **Task 2** → Cleanup file ad-hoc (hapus/pindahkan satu per satu)
6. **Task 3** → Reorganisasi PPC controllers
7. **Final test** → Pastikan semua tetap berfungsi
