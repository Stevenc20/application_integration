<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\RoleFeature;
use Illuminate\Database\Seeder;

class FeaturePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $features = [
            ['feature_code' => 'dashboard',              'feature_name' => 'Dashboard',              'group_name' => 'Dashboard'],
            ['feature_code' => 'input_harian',            'feature_name' => 'Input Harian',           'group_name' => 'Operational'],
            ['feature_code' => 'dandori',                 'feature_name' => 'Dandori',                'group_name' => 'Operational'],
            ['feature_code' => 'breaktime',               'feature_name' => 'Break Time',             'group_name' => 'Operational'],
            ['feature_code' => 'handwork',                'feature_name' => 'Handwork',               'group_name' => 'Operational'],
            ['feature_code' => 'qcheck',                  'feature_name' => 'Q-Check',                'group_name' => 'Operational'],
            ['feature_code' => 'repair_reject',           'feature_name' => 'Repair & Reject',        'group_name' => 'Operational'],
            ['feature_code' => 'data_job',                'feature_name' => 'Data Job',               'group_name' => 'Operational'],
            ['feature_code' => 'idletime',                'feature_name' => 'Idle Time',              'group_name' => 'Operational'],
            ['feature_code' => 'downtime',                'feature_name' => 'Downtime',               'group_name' => 'Operational'],
            ['feature_code' => 'line_monitoring',         'feature_name' => 'Line Monitoring',        'group_name' => 'Monitoring'],
            ['feature_code' => 'quality_dashboard',       'feature_name' => 'Quality Dashboard',      'group_name' => 'Monitoring'],
            ['feature_code' => 'quality_achievement',     'feature_name' => 'Quality Achievement',    'group_name' => 'Monitoring'],
            ['feature_code' => 'quality_control_defect',  'feature_name' => 'QC - Defect Monitoring', 'group_name' => 'Quality Control'],
            ['feature_code' => 'quality_control_reject',  'feature_name' => 'QC - Reject Analysis',   'group_name' => 'Quality Control'],
            ['feature_code' => 'grafik_downtime_item',    'feature_name' => 'Grafik Downtime Item',   'group_name' => 'Grafik'],
            ['feature_code' => 'grafik_downtime_type',    'feature_name' => 'Grafik Downtime Type',   'group_name' => 'Grafik'],
            ['feature_code' => 'grafik_downtime_machine', 'feature_name' => 'Grafik Downtime Machine','group_name' => 'Grafik'],
            ['feature_code' => 'quality_achievement',      'feature_name' => 'Quality Achievement',       'group_name' => 'Grafik'],
            ['feature_code' => 'job_master',              'feature_name' => 'Job Master',             'group_name' => 'Master Data'],
            ['feature_code' => 'production_line',         'feature_name' => 'Production Line',        'group_name' => 'Master Data'],
            ['feature_code' => 'data_karyawan',           'feature_name' => 'Data Karyawan',          'group_name' => 'Master Data'],
            ['feature_code' => 'user_management',         'feature_name' => 'User Management',        'group_name' => 'User Management'],
            ['feature_code' => 'daily_report',            'feature_name' => 'Laporan Kerja Harian',   'group_name' => 'Reports'],
            ['feature_code' => 'performance_report',      'feature_name' => 'Performance Report',     'group_name' => 'Reports'],
            ['feature_code' => 'production_recap',        'feature_name' => 'Production Recap',       'group_name' => 'Reports'],
            ['feature_code' => 'audit_trail',             'feature_name' => 'Audit Trail',            'group_name' => 'Reports'],
            ['feature_code' => 'production_plan',         'feature_name' => 'Production Plan',        'group_name' => 'Planning'],
            ['feature_code' => 'breaktime_planning',      'feature_name' => 'Break Time Planning',    'group_name' => 'Planning'],
            ['feature_code' => 'trouble_history',         'feature_name' => 'Trouble History',        'group_name' => 'Trouble'],
            ['feature_code' => 'hambatan_jalur',          'feature_name' => 'Hambatan Jalur',         'group_name' => 'Hambatan Jalur'],
            ['feature_code' => 'production_analytics',    'feature_name' => 'Production Analytics',   'group_name' => 'Analytics'],
        ];

        $allRoles = ['admin', 'supervisor', 'ppc', 'foreman', 'operator', 'leader', 'quality', 'production', 'manager', 'kadiv', 'direktur', 'presdir', 'superadmin', 'hambatan', 'dies_shop', 'plant_service', 'irm', 'logistik', 'produksi'];

        foreach ($features as $f) {
            $feature = Feature::firstOrCreate(
                ['feature_code' => $f['feature_code']],
                $f
            );

            foreach ($allRoles as $role) {
                RoleFeature::firstOrCreate([
                    'role' => $role,
                    'feature_id' => $feature->id,
                ], [
                    'enabled' => true,
                ]);
            }
        }
    }
}
