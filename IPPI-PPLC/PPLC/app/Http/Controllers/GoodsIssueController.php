<?php

namespace App\Http\Controllers;

use App\Models\GoodsIssue;
use App\Models\GoodsIssueItem;
use App\Models\StorageLocation;
use App\Models\Material;
use App\Exports\GoodsIssueExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class GoodsIssueController extends Controller
{
    public function index(Request $request)
    {
        $search = trim($request->get('search', ''));
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $locationId = $request->get('location_id');

        $query = GoodsIssue::with(['storageLocation', 'items.material']);

        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('no_gi', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%")
                  ->orWhereHas('storageLocation', function($locQ) use ($search) {
                      $locQ->where('nama', 'like', "%{$search}%");
                  });
            });
        }

        if ($startDate) {
            $query->whereDate('tanggal_issue', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('tanggal_issue', '<=', $endDate);
        }
        if ($locationId && $locationId !== 'Semua Lokasi') {
            $query->where('storage_location_id', $locationId);
        }

        $goodsIssues = $query->orderBy('no_gi', 'desc')->paginate(15)->appends($request->query());
        $locations = StorageLocation::orderBy('nama', 'asc')->get();
        $materials = Material::orderBy('kode', 'asc')->get();

        return view('goods_issues.index', compact('goodsIssues', 'search', 'startDate', 'endDate', 'locationId', 'locations', 'materials'));
    }

    public function create()
    {
        $locations = StorageLocation::orderBy('nama', 'asc')->get();
        $materials = Material::where('status', 'Aktif')->orderBy('kode', 'asc')->get();
        return view('goods_issues.create', compact('locations', 'materials'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'no_gi' => 'nullable|string|unique:goods_issues,no_gi',
            'tanggal_issue' => 'required|date',
            'storage_location_id' => 'required|exists:storage_locations,id',
            'tipe_issue' => 'required|string|in:Pemakaian Internal,Kirim ke Vendor,Kirim ke Customer',
            'dest_location' => 'nullable|exists:storage_locations,id',
            'keterangan' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.qty' => 'required|numeric|min:0.001',
            'items.*.note' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            if (empty($validated['no_gi'])) {
                $year = date('Y');
                $lastGi = GoodsIssue::where('no_gi', 'like', "GI-{$year}-%")->orderBy('no_gi', 'desc')->first();
                if ($lastGi) {
                    $lastNum = intval(substr($lastGi->no_gi, -5));
                    $newNum = str_pad($lastNum + 1, 5, '0', STR_PAD_LEFT);
                } else {
                    $newNum = '00001';
                }
                $validated['no_gi'] = "GI-{$year}-{$newNum}";
            }

            // Prep prefix with issue type and optional destination location info
            $prefix = "Tipe: " . $validated['tipe_issue'];
            if (!empty($validated['dest_location'])) {
                $destLoc = StorageLocation::find($validated['dest_location']);
                if ($destLoc) {
                    $prefix .= " (Tujuan: {$destLoc->nama})";
                }
            }

            // If there are item notes, we can concatenate them
            $keteranganBody = $validated['keterangan'] ?? '';
            $itemNotes = [];
            foreach ($validated['items'] as $item) {
                if (!empty($item['note'])) {
                    $mat = Material::find($item['material_id']);
                    $matCode = $mat ? $mat->kode : '';
                    $itemNotes[] = "{$matCode}: {$item['note']}";
                }
            }
            if (!empty($itemNotes)) {
                $keteranganNotes = "Notes: " . implode(', ', $itemNotes);
                $keteranganBody = empty($keteranganBody) ? $keteranganNotes : $keteranganBody . " | " . $keteranganNotes;
            }

            $finalKeterangan = empty($keteranganBody) ? $prefix : $prefix . " | " . $keteranganBody;

            $gi = GoodsIssue::create([
                'no_gi' => $validated['no_gi'],
                'tanggal_issue' => $validated['tanggal_issue'],
                'storage_location_id' => $validated['storage_location_id'],
                'keterangan' => $finalKeterangan,
            ]);

            foreach ($validated['items'] as $item) {
                GoodsIssueItem::create([
                    'goods_issue_id' => $gi->id,
                    'material_id' => $item['material_id'],
                    'qty' => $item['qty'],
                ]);

                // Update stock level in material_stocks
                $mStock = \App\Models\MaterialStock::firstOrCreate([
                    'material_id' => $item['material_id'],
                    'storage_location_id' => $gi->storage_location_id,
                ]);
                $mStock->decrement('qty', $item['qty']);
            }

            DB::commit();
            return redirect()->route('goods_issues.index')->with('success', 'Goods Issue berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal membuat Goods Issue: ' . $e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
        $gi = GoodsIssue::with(['storageLocation', 'items.material'])->findOrFail($id);
        
        return response()->json([
            'id' => $gi->id,
            'no_gi' => $gi->no_gi,
            'tanggal_issue' => $gi->tanggal_issue->format('Y-m-d'),
            'storage_location_id' => $gi->storage_location_id,
            'storage_location_nama' => $gi->storageLocation->nama ?? '-',
            'keterangan' => $gi->keterangan ?? '-',
            'items' => $gi->items->map(function($item) {
                return [
                    'id' => $item->id,
                    'material_id' => $item->material_id,
                    'material_kode' => $item->material->kode ?? '-',
                    'material_nama' => $item->material->nama ?? '-',
                    'material_uom' => $item->material->uom ?? '-',
                    'qty' => $item->qty,
                ];
            }),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:goods_issues,id',
            'no_gi' => 'required|string|unique:goods_issues,no_gi,' . $request->id,
            'tanggal_issue' => 'required|date',
            'storage_location_id' => 'required|exists:storage_locations,id',
            'keterangan' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.qty' => 'required|numeric|min:0.001',
        ]);

        try {
            DB::beginTransaction();

            $gi = GoodsIssue::findOrFail($request->id);

            // Revert stock level in material_stocks from previous items
            foreach ($gi->items as $oldItem) {
                $mStock = \App\Models\MaterialStock::where('material_id', $oldItem->material_id)
                    ->where('storage_location_id', $gi->storage_location_id)
                    ->first();
                if ($mStock) {
                    $mStock->increment('qty', $oldItem->qty);
                }
            }

            $gi->update([
                'no_gi' => $validated['no_gi'],
                'tanggal_issue' => $validated['tanggal_issue'],
                'storage_location_id' => $validated['storage_location_id'],
                'keterangan' => $validated['keterangan'] ?? null,
            ]);

            // Rebuild items
            GoodsIssueItem::where('goods_issue_id', $gi->id)->delete();

            foreach ($validated['items'] as $item) {
                GoodsIssueItem::create([
                    'goods_issue_id' => $gi->id,
                    'material_id' => $item['material_id'],
                    'qty' => $item['qty'],
                ]);

                // Update stock level in material_stocks with new quantity
                $mStock = \App\Models\MaterialStock::firstOrCreate([
                    'material_id' => $item['material_id'],
                    'storage_location_id' => $gi->storage_location_id,
                ]);
                $mStock->decrement('qty', $item['qty']);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Goods Issue berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memperbarui Goods Issue: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $gi = GoodsIssue::findOrFail($id);

            // Revert stock level in material_stocks
            foreach ($gi->items as $item) {
                $mStock = \App\Models\MaterialStock::where('material_id', $item->material_id)
                    ->where('storage_location_id', $gi->storage_location_id)
                    ->first();
                if ($mStock) {
                    $mStock->increment('qty', $item->qty);
                }
            }

            $gi->delete();

            DB::commit();
            return redirect()->back()->with('success', 'Goods Issue berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menghapus Goods Issue: ' . $e->getMessage());
        }
    }

    public function exportExcel(Request $request)
    {
        $search = $request->get('search');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $locationId = $request->get('location_id');

        return Excel::download(new GoodsIssueExport($search, $startDate, $endDate, $locationId), 'goods_issues_' . now()->format('Ymd_His') . '.xlsx');
    }

    public function printPdf(Request $request)
    {
        $search = trim($request->get('search', ''));
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $locationId = $request->get('location_id');

        $query = GoodsIssue::with(['storageLocation', 'items.material']);

        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('no_gi', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%")
                  ->orWhereHas('storageLocation', function($locQ) use ($search) {
                      $locQ->where('nama', 'like', "%{$search}%");
                  });
            });
        }

        if ($startDate) {
            $query->whereDate('tanggal_issue', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('tanggal_issue', '<=', $endDate);
        }
        if ($locationId && $locationId !== 'Semua Lokasi') {
            $query->where('storage_location_id', $locationId);
        }

        $goodsIssues = $query->orderBy('no_gi', 'desc')->get();

        $dateStr = now()->format('d M Y, H:i') . ' WIB';
        $filterStr = "Semua data";
        
        $filters = [];
        if ($search !== '') $filters[] = "Cari: '$search'";
        if ($startDate) $filters[] = "Mulai: $startDate";
        if ($endDate) $filters[] = "Sampai: $endDate";
        if ($locationId && $locationId !== 'Semua Lokasi') {
            $loc = StorageLocation::find($locationId);
            if ($loc) $filters[] = "Lokasi: {$loc->nama}";
        }
        
        if (count($filters) > 0) {
            $filterStr = implode(' | ', $filters);
        }

        $pdf = Pdf::loadView('goods_issues.pdf', [
            'goodsIssues' => $goodsIssues,
            'dateStr' => $dateStr,
            'filterStr' => $filterStr,
        ]);

        $uploadDir = storage_path('app' . DIRECTORY_SEPARATOR . 'uploads');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = 'goods_issues_' . now()->format('Ymd') . '.pdf';
        $savePath = $uploadDir . DIRECTORY_SEPARATOR . $filename;
        $pdf->save($savePath);

        return $pdf->download($filename);
    }
}
