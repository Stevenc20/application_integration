<?php

namespace App\Http\Controllers\Qa\Api;

use App\Http\Controllers\Controller;
use App\Models\LiTemplate;
use Illuminate\Http\Request;

class LiTemplateController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q');
        $query = LiTemplate::orderByDesc('updated_at');
        
        if ($q) {
            $query->where('part_no', 'like', "%$q%")
                  ->orWhere('part_name', 'like', "%$q%");
        }
        
        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'job_no'           => 'nullable|string|max:255',
            'part_no'          => 'required|string|max:255',
            'part_name'        => 'nullable|string|max:255',
            'type'             => 'nullable|string|max:255',
            'spec_material'    => 'nullable|string|max:255',
            'type_pallet'      => 'nullable|string|max:255',
            'view_package'     => 'nullable|string|max:255',
            'image_path'       => 'nullable|string',
            'tact_time'        => 'nullable|numeric|min:0',
            'ct_dimensi'       => 'nullable|numeric|min:0',
            'ct_tanpa_dimensi' => 'nullable|numeric|min:0',
            // Dimensi & Appearance fields as needed...
        ]);

        // Upsert by part_no
        $data = $request->only([
            'job_no', 'part_no', 'part_name', 'type', 'spec_material', 'type_pallet',
            'view_package', 'image_path',
            'tact_time', 'ct_dimensi', 'ct_tanpa_dimensi',
        ]);
        
        // Add Dimensions & Appearance
        for ($i = 1; $i <= 7; $i++) {
            $data["dimensi{$i}"] = $request->input("dimensi{$i}");
            $data["dimensi{$i}_item"] = $request->input("dimensi{$i}_item");
            $data["dimensi{$i}_method"] = $request->input("dimensi{$i}_method");
            // Structured tolerance fields (auto-parsed or user-set)
            $nominal = $request->input("dimensi{$i}_nominal");
            $plus    = $request->input("dimensi{$i}_plus");
            $minus   = $request->input("dimensi{$i}_minus");
            $data["dimensi{$i}_nominal"] = is_numeric($nominal) ? (float)$nominal : null;
            $data["dimensi{$i}_plus"]    = is_numeric($plus)    ? (float)$plus    : null;
            $data["dimensi{$i}_minus"]   = is_numeric($minus)   ? (float)$minus   : null;
        }
        for ($i = 6; $i <= 14; $i++) {
            $data["appearance{$i}"] = $request->input("appearance{$i}");
        }
        
        $data['created_by'] = $request->user()->id;

        // Handle Base64 Image
        if (!empty($data['image_path']) && strpos($data['image_path'], 'data:image') === 0) {
            $imageParts = explode(';base64,', $data['image_path']);
            if (count($imageParts) == 2) {
                $imageTypeAux = explode('image/', $imageParts[0]);
                $imageType = $imageTypeAux[1] ?? 'png';
                $imageBase64 = base64_decode($imageParts[1]);
                $fileName = 'li_templates/' . uniqid() . '.' . $imageType;
                \Illuminate\Support\Facades\Storage::disk('public')->put($fileName, $imageBase64);
                $data['image_path'] = $fileName;
            }
        }

        $template = LiTemplate::updateOrCreate(

            ['part_no' => $request->part_no],
            $data
        );

        return response()->json([
            'message' => 'Template berhasil disimpan',
            'data' => $template
        ]);
    }

    public function showByPartNo($partNo)
    {
        $template = LiTemplate::where('part_no', $partNo)->first();
        if (!$template) {
            return response()->json(['message' => 'Template tidak ditemukan'], 404);
        }
        return response()->json($template);
    }

    public function destroyByPartNo($partNo)
    {
        $template = LiTemplate::where('part_no', $partNo)->first();
        if (!$template) {
            return response()->json(['message' => 'Template tidak ditemukan'], 404);
        }
        $template->delete(); // soft delete
        return response()->json(['message' => 'Template berhasil dihapus']);
    }

    /**
     * POST /api/li-templates/sync-from-li
     * Migrate semua unique part_no dari lembar_inspeksi ke li_templates.
     * Hanya Admin / Leader yang bisa akses.
     */
    public function syncFromLi(Request $request)
    {
        $user = $request->user();

        // Ambil satu LI terbaru per part_no (yang paling banyak datanya)
        $liItems = \App\Models\LembarInspeksi::whereNotNull('part_no')
            ->whereNull('deleted_at')
            ->orderByDesc('updated_at')
            ->get()
            ->unique('part_no'); // ambil record paling baru per part_no

        $synced  = 0;
        $skipped = 0;

        foreach ($liItems as $li) {
            $partNo = trim($li->part_no);
            if (empty($partNo)) { $skipped++; continue; }

            // Jika template dengan part_no ini sudah ada, lewati agar hasil editan user tidak tertimpa
            if (LiTemplate::where('part_no', $partNo)->exists()) {
                $skipped++;
                continue;
            }

            $data = [
                'job_no'           => $li->job_no,
                'part_no'          => $partNo,
                'part_name'        => $li->part_name,
                'type'             => $li->type,
                'spec_material'    => $li->spec_material,
                'type_pallet'      => $li->type_pallet,
                'image_path'       => $li->image_path,
                'tact_time'        => $li->tact_time,
                'ct_dimensi'       => $li->ct_dimensi,
                'ct_tanpa_dimensi' => $li->ct_tanpa_dimensi,
                'created_by'       => $user->id,
            ];

            for ($i = 1; $i <= 7; $i++) {
                $data["dimensi{$i}"]        = $li->{"dimensi{$i}"};
                $data["dimensi{$i}_item"]   = $li->{"dimensi{$i}_item"};
                $data["dimensi{$i}_method"] = $li->{"dimensi{$i}_method"};
                // Copy parsed tolerance fields if they exist in LembarInspeksi
                $data["dimensi{$i}_nominal"] = $li->{"dimensi{$i}_nominal"} ?? null;
                $data["dimensi{$i}_plus"]    = $li->{"dimensi{$i}_plus"}    ?? null;
                $data["dimensi{$i}_minus"]   = $li->{"dimensi{$i}_minus"}   ?? null;
            }

            for ($i = 6; $i <= 14; $i++) {
                $data["appearance{$i}"] = $li->{"appearance{$i}"};
            }

            LiTemplate::create($data);
            $synced++;
        }

        return response()->json([
            'message' => "Sinkronisasi selesai: {$synced} template berhasil disimpan, {$skipped} dilewati.",
            'synced'  => $synced,
            'skipped' => $skipped,
        ]);
    }
}
