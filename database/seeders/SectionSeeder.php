<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Section;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    public function run(): void
    {
        $sections = [
            ['department' => 'Corporate',    'sections' => ['Direksi']],
            ['department' => 'PPIC',         'sections' => ['PPC & RM']],
            ['department' => 'Produksi',     'sections' => ['Stamping', 'Sub Assy']],
            ['department' => 'Maintenance',  'sections' => ['Plant Service', 'Dies Shop']],
            ['department' => 'Quality',      'sections' => ['Incoming Quality', 'Process Quality', 'Final Quality']],
        ];

        foreach ($sections as $item) {
            $dept = Department::where('department_name', $item['department'])->first();
            if ($dept) {
                foreach ($item['sections'] as $sectionName) {
                    Section::firstOrCreate([
                        'department_id' => $dept->id,
                        'section_name' => $sectionName,
                    ]);
                }
            }
        }
    }
}
