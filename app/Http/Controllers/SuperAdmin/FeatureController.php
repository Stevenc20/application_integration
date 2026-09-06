<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\Position;
use App\Models\Section;
use App\Models\PermissionMatrix;
use Illuminate\Http\Request;

class FeatureController extends Controller
{
    public function index()
    {
        $features = Feature::orderBy('group_name')->orderBy('feature_name')->get();
        $positions = Position::orderBy('level')->get();
        $sections = Section::orderBy('section_name')->get();
        
        $matrices = PermissionMatrix::with(['position', 'section', 'feature'])
            ->orderBy('feature_id')
            ->get()
            ->groupBy('feature_id');

        return view('super_admin.features.index', compact('features', 'positions', 'sections', 'matrices'));
    }

    public function update(Request $request)
    {
        // For backwards compatibility with the previous form structure or new structure
        if ($request->has('matrices')) {
            foreach ($request->matrices as $id => $perms) {
                PermissionMatrix::where('id', $id)->update([
                    'can_view' => isset($perms['can_view']),
                    'can_create' => isset($perms['can_create']),
                    'can_edit' => isset($perms['can_edit']),
                    'can_delete' => isset($perms['can_delete']),
                    'can_approve' => isset($perms['can_approve']),
                    'can_export' => isset($perms['can_export']),
                ]);
            }
        }
        
        // Handle creating a new matrix row
        if ($request->has('new_feature_id') && $request->new_feature_id) {
            $request->validate([
                'new_feature_id' => 'required|exists:features,id',
                'new_position_id' => 'nullable|exists:positions,id',
                'new_section_id' => 'nullable|exists:sections,id',
            ]);

            PermissionMatrix::updateOrCreate([
                'feature_id' => $request->new_feature_id,
                'position_id' => $request->new_position_id ?: null,
                'section_id' => $request->new_section_id ?: null,
            ], [
                'can_view' => $request->has('new_can_view'),
                'can_create' => $request->has('new_can_create'),
                'can_edit' => $request->has('new_can_edit'),
                'can_delete' => $request->has('new_can_delete'),
                'can_approve' => $request->has('new_can_approve'),
                'can_export' => $request->has('new_can_export'),
            ]);
        }

        return redirect()->back()->with('success', 'Permissions updated successfully.');
    }

    public function destroyMatrix($id)
    {
        PermissionMatrix::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Permission matrix row deleted.');
    }
}
