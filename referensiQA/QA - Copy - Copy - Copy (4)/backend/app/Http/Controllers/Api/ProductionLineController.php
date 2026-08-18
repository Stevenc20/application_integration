<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionLine;
use Illuminate\Http\Request;

class ProductionLineController extends Controller
{
    public function index()
    {
        return response()->json(ProductionLine::orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:production_lines',
            'name' => 'required|string|max:100',
            'department' => 'nullable|string|max:100',
        ]);
        
        $line = ProductionLine::create($validated);
        return response()->json($line, 201);
    }

    public function show($id)
    {
        return response()->json(ProductionLine::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $line = ProductionLine::findOrFail($id);
        
        $validated = $request->validate([
            'code' => 'sometimes|required|string|max:50|unique:production_lines,code,'.$id,
            'name' => 'sometimes|required|string|max:100',
            'department' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'is_stopped' => 'boolean'
        ]);

        $line->update($validated);
        return response()->json($line);
    }

    public function destroy($id)
    {
        $line = ProductionLine::findOrFail($id);
        $line->delete();
        return response()->json(['message' => 'Line deleted successfully']);
    }
}
