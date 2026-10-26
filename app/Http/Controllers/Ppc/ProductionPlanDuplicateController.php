<?php

namespace App\Http\Controllers\Ppc;

use App\Http\Controllers\Controller;
use App\Services\ProductionPlanDuplicateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductionPlanDuplicateController extends Controller
{
    public function __construct(private ProductionPlanDuplicateService $service) {}

    public function index(Request $request): JsonResponse
    {
        $data = $this->service->scan(
            $request->get('date'),
            $request->get('press'),
            $request->get('shift')
        );

        return response()->json([
            'success' => true,
            'meta' => [
                'date' => $request->get('date'),
                'press' => $request->get('press'),
                'shift' => $request->get('shift'),
                'group_count' => count($data),
                'item_count' => array_sum(array_map(fn (array $group) => (int) $group['count'], $data)),
            ],
            'data' => $data,
        ]);
    }

    public function show(int|string $id): JsonResponse
    {
        $data = $this->service->detail((int) $id);

        if ($data === null) {
            return response()->json([
                'success' => false,
                'message' => 'Item tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $validated = $request->validate([
            'duplicate_of_id' => 'required|integer|min:1',
            'reason' => 'required|string|min:5|max:500',
            'confirm_identity' => 'required|accepted',
            'confirm_review' => 'nullable|boolean',
        ], [
            'duplicate_of_id.required' => 'Pasangan duplikat wajib dipilih.',
            'reason.required' => 'Alasan penghapusan wajib diisi.',
            'reason.min' => 'Alasan penghapusan minimal 5 karakter.',
            'confirm_identity.accepted' => 'Konfirmasi identitas item wajib dicentang.',
        ]);

        $result = $this->service->deleteDuplicate(
            (int) $id,
            (int) $validated['duplicate_of_id'],
            trim((string) $validated['reason']),
            Auth::id(),
            (bool) ($validated['confirm_review'] ?? false)
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
