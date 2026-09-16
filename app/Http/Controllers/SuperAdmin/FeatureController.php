<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\RoleFeature;
use Illuminate\Http\Request;

class FeatureController extends Controller
{
    public function index()
    {
        $features = Feature::orderBy('group_name')->orderBy('feature_name')->get();
        $groups = $features->groupBy('group_name');
        $roles = ['admin', 'supervisor', 'ppc', 'foreman', 'operator', 'leader', 'quality', 'production', 'manager', 'kadiv', 'direktur', 'presdir', 'hambatan'];
        $permissions = RoleFeature::all()->keyBy(function ($item) {
            return $item->role . '_' . $item->feature_id;
        });

        return view('super_admin.features.index', compact('features', 'groups', 'roles', 'permissions'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'permissions' => 'array',
            'permissions.*.*' => 'boolean',
        ]);

        $permissions = $data['permissions'] ?? [];

        foreach ($permissions as $role => $features) {
            foreach ($features as $featureId => $enabled) {
                RoleFeature::updateOrCreate(
                    ['role' => $role, 'feature_id' => $featureId],
                    ['enabled' => $enabled]
                );
            }
        }

        return redirect()->back()->with('success', 'Feature permissions updated successfully.');
    }
}
