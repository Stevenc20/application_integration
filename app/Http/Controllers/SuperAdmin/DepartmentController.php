<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Section;
use App\Models\Position;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::with('sections')->orderBy('department_name')->get();
        $positions = Position::orderBy('level')->orderBy('position_name')->get();
        return view('super_admin.departments.index', compact('departments', 'positions'));
    }

    public function store(Request $request)
    {
        $request->validate(['department_name' => 'required|unique:departments,department_name']);
        Department::create($request->only('department_name'));
        return back()->with('success', 'Department created.');
    }

    public function update(Request $request, Department $department)
    {
        $request->validate(['department_name' => 'required|unique:departments,department_name,' . $department->id]);
        $department->update($request->only('department_name'));
        return back()->with('success', 'Department updated.');
    }

    public function destroy(Department $department)
    {
        $department->delete();
        return back()->with('success', 'Department deleted.');
    }

    // Sections
    public function storeSection(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'section_name' => 'required|unique:sections,section_name,NULL,id,department_id,' . $request->department_id,
        ]);
        Section::create($request->only('department_id', 'section_name'));
        return back()->with('success', 'Section created.');
    }

    public function updateSection(Request $request, Section $section)
    {
        $request->validate([
            'section_name' => 'required|unique:sections,section_name,' . $section->id . ',id,department_id,' . $section->department_id,
        ]);
        $section->update($request->only('section_name'));
        return back()->with('success', 'Section updated.');
    }

    public function destroySection(Section $section)
    {
        $section->delete();
        return back()->with('success', 'Section deleted.');
    }

    // Positions
    public function storePosition(Request $request)
    {
        $request->validate([
            'position_name' => 'required|unique:positions,position_name',
            'level' => 'required|integer|min:1|max:5',
        ]);
        Position::create($request->only('position_name', 'level'));
        return back()->with('success', 'Position created.');
    }

    public function updatePosition(Request $request, Position $position)
    {
        $request->validate([
            'position_name' => 'required|unique:positions,position_name,' . $position->id,
            'level' => 'required|integer|min:1|max:5',
        ]);
        $position->update($request->only('position_name', 'level'));
        return back()->with('success', 'Position updated.');
    }

    public function destroyPosition(Position $position)
    {
        $position->delete();
        return back()->with('success', 'Position deleted.');
    }

    // API: get sections by department
    public function getSections(Department $department)
    {
        return response()->json($department->sections()->orderBy('section_name')->get());
    }
}
