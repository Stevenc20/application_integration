<?php

namespace App\Http\Controllers\Qa\Api;

use App\Http\Controllers\Controller;
use App\Models\DefectMaster;
use Illuminate\Http\Request;

class DefectMasterController extends Controller
{
    public function index()
    {
        return response()->json(DefectMaster::orderBy('category')->orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:defect_masters',
            'name' => 'required|string|max:100',
            'category' => 'required|string|max:100',
        ]);
        
        $defect = DefectMaster::create($validated);
        return response()->json($defect, 201);
    }

    public function show($id)
    {
        return response()->json(DefectMaster::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $defect = DefectMaster::findOrFail($id);
        
        $validated = $request->validate([
            'code' => 'sometimes|required|string|max:50|unique:defect_masters,code,'.$id,
            'name' => 'sometimes|required|string|max:100',
            'category' => 'sometimes|required|string|max:100',
            'is_active' => 'boolean'
        ]);

        $defect->update($validated);
        return response()->json($defect);
    }

    public function destroy($id)
    {
        $defect = DefectMaster::findOrFail($id);
        $defect->delete();
        return response()->json(['message' => 'Defect deleted successfully']);
    }
}
