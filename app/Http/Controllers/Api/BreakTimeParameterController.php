<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MasterBreakTime;
use App\Services\TimelineGenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class BreakTimeParameterController extends Controller
{
    public function index(): JsonResponse
    {
        if (!Schema::hasTable('master_break_times')) {
            return response()->json(['data' => [], 'message' => 'Tabel master_break_times belum ada. Jalankan migrasi.']);
        }

        $items = MasterBreakTime::orderBy('sort_order')->orderBy('hari')->get()->map(fn ($b) => $this->formatRow($b));

        return response()->json(['data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $break = MasterBreakTime::create($data);
        $this->regeneratePpcTimelines();

        return response()->json([
            'success' => true,
            'message' => 'Parameter breaktime ditambahkan.',
            'data' => $this->formatRow($break),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $break = MasterBreakTime::findOrFail($id);
        $break->update($this->validated($request));
        $this->regeneratePpcTimelines();

        return response()->json([
            'success' => true,
            'message' => 'Parameter breaktime diperbarui.',
            'data' => $this->formatRow($break->fresh()),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        MasterBreakTime::findOrFail($id)->delete();
        $this->regeneratePpcTimelines();

        return response()->json(['success' => true, 'message' => 'Parameter breaktime dihapus. Timeline PPC diregenerate.']);
    }

    public function toggle(int $id): JsonResponse
    {
        $break = MasterBreakTime::findOrFail($id);
        $break->is_active = !$break->is_active;
        $break->save();
        $this->regeneratePpcTimelines();

        return response()->json([
            'success' => true,
            'message' => $break->is_active ? 'Parameter diaktifkan.' : 'Parameter dinonaktifkan.',
            'data' => $this->formatRow($break),
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        $date = $request->get('date', now()->toDateString());
        $shift = $request->get('shift', 'Shift Pagi');
        $hari = $request->get('hari');

        $service = app(TimelineGenerationService::class);
        $windows = $service->resolveBreakWindows($date, $shift, $hari);

        return response()->json(['data' => $windows]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'label' => 'required|string|max:120',
            'hari' => 'required|string|max:20',
            'waktu_mulai' => 'required',
            'waktu_selesai' => 'required',
            'type' => 'required|in:istirahat,cinkorak,break',
            'shift' => 'nullable|string|max:80',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        return [
            'label' => $data['label'],
            'hari' => strtolower($data['hari']),
            'waktu_mulai' => substr($data['waktu_mulai'], 0, 5),
            'waktu_selesai' => substr($data['waktu_selesai'], 0, 5),
            'type' => $data['type'] === 'break' ? 'istirahat' : $data['type'],
            'shift' => $data['shift'] ?: null,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function regeneratePpcTimelines(): void
    {
        if (!Schema::hasTable('master_break_times')) {
            return;
        }

        app(TimelineGenerationService::class)->regenerateAllSections(true);
    }

    private function formatRow(MasterBreakTime $b): array
    {
        return [
            'id' => $b->id,
            'label' => $b->label,
            'hari' => $b->hari,
            'shift' => $b->shift,
            'waktu_mulai' => substr((string) $b->waktu_mulai, 0, 5),
            'waktu_selesai' => substr((string) $b->waktu_selesai, 0, 5),
            'type' => $b->type,
            'is_active' => (bool) $b->is_active,
            'sort_order' => (int) $b->sort_order,
        ];
    }

    public function simulate(Request $request): \Illuminate\Http\JsonResponse
    {
        $proposed = $request->validate([
            'label' => 'required|string|max:120',
            'hari' => 'required|string|max:20',
            'waktu_mulai' => 'required',
            'waktu_selesai' => 'required',
            'type' => 'required|in:istirahat,cinkorak,break',
            'shift' => 'nullable|string|max:80',
            'is_active' => 'nullable|boolean',
            'date' => 'nullable|date',
        ]);

        $proposed['is_active'] = $request->boolean('is_active', true);
        $proposed['shift'] = $proposed['shift'] ?: 'Shift Pagi';
        $proposed['date'] = $proposed['date'] ?? now()->toDateString();

        $service = app(TimelineGenerationService::class);
        $affected = $service->simulateProposedBreak($proposed);

        return response()->json(['affected' => $affected]);
    }
}
