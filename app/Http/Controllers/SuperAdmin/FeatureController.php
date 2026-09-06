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
        $positions = Position::orderBy('level')->get();
        $sections = Section::orderBy('section_name')->get();
        return view('super_admin.features.index', compact('positions', 'sections'));
    }

    public function getPermissions(Request $request)
    {
        $positionId = $request->query('position_id') ?: null;
        $sectionId = $request->query('section_id') ?: null;

        $features = Feature::orderBy('group_name')->orderBy('feature_name')->get();
        
        $matrices = PermissionMatrix::where('position_id', $positionId)
            ->where('section_id', $sectionId)
            ->get()
            ->keyBy('feature_id');

        $result = [];
        $groups = [];

        foreach ($features as $f) {
            $matrix = $matrices->get($f->id);
            $hasAccess = $matrix ? $matrix->can_view : false;

            $groupName = $f->group_name ?: 'Other';
            if (!isset($groups[$groupName])) {
                $groups[$groupName] = [];
            }

            $groups[$groupName][] = [
                'feature_id' => $f->id,
                'feature_name' => $f->feature_name,
                'feature_code' => $f->feature_code,
                'is_active' => $hasAccess,
                'actions' => [
                    'can_view' => $matrix ? $matrix->can_view : false,
                    'can_create' => $matrix ? $matrix->can_create : false,
                    'can_edit' => $matrix ? $matrix->can_edit : false,
                    'can_delete' => $matrix ? $matrix->can_delete : false,
                    'can_approve' => $matrix ? $matrix->can_approve : false,
                    'can_export' => $matrix ? $matrix->can_export : false,
                ]
            ];
        }

        return response()->json([
            'success' => true,
            'groups' => $groups
        ]);
    }

    public function togglePermission(Request $request)
    {
        $request->validate([
            'position_id' => 'nullable|exists:positions,id',
            'section_id' => 'nullable|exists:sections,id',
            'feature_id' => 'required|exists:features,id',
            'action' => 'required|string|in:is_active,can_view,can_create,can_edit,can_delete,can_approve,can_export',
            'state' => 'required|boolean'
        ]);

        $posId = $request->position_id ?: null;
        $secId = $request->section_id ?: null;
        $featureId = $request->feature_id;
        $action = $request->action;
        $state = $request->state;

        $matrix = PermissionMatrix::firstOrNew([
            'position_id' => $posId,
            'section_id' => $secId,
            'feature_id' => $featureId,
        ]);

        if ($action === 'is_active') {
            // Turning off main switch turns everything off
            if (!$state) {
                $matrix->can_view = false;
                $matrix->can_create = false;
                $matrix->can_edit = false;
                $matrix->can_delete = false;
                $matrix->can_approve = false;
                $matrix->can_export = false;
            } else {
                // Turning on turns on can_view by default
                $matrix->can_view = true;
            }
        } else {
            $matrix->{$action} = $state;
            // If they enable an action, ensure can_view is true
            if ($state && $action !== 'can_view') {
                $matrix->can_view = true;
            }
        }

        $matrix->save();

        return response()->json(['success' => true]);
    }
}
