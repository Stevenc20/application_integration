<?php

namespace Tests\Feature;

use App\Models\LineMaster;
use App\Models\MasterShift;
use App\Models\ProductionPlan;
use App\Models\Role;
use App\Models\RoleFeature;
use App\Models\Feature;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LineShiftSubmitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        MasterShift::firstOrCreate(
            ['name' => 'Shift Pagi'],
            ['planned_start_time' => '07:30', 'planned_end_time' => '16:30']
        );
        MasterShift::firstOrCreate(
            ['name' => 'Shift Malam'],
            ['planned_start_time' => '21:00', 'planned_end_time' => '06:00']
        );
    }

    public function test_submit_shift_across_all_lines()
    {
        $lines = [
            'Line A' => 'Press A',
            'Line B' => 'Press B',
            'Line C' => 'Press C',
            'Line D' => 'Press D',
        ];

        $shift = MasterShift::where('name', 'Shift Pagi')->first();
        $date = Carbon::today()->toDateString();

        foreach ($lines as $lineKey => $lineName) {
            $lineMaster = LineMaster::create([
                'line_code' => str_replace(' ', '_', strtoupper($lineName)),
                'line_name' => $lineName,
            ]);

            // Test with a Leader user specifically for this line
            $user = User::factory()->create([
                'name' => "Leader $lineKey",
                'role' => 'leader',
            ]);

            // Submit using line ID
            $response = $this->actingAs($user)->postJson("/operational/shift/{$lineMaster->id}/submit", [
                'date' => $date,
                'shift' => 'Shift Pagi',
                'confirm_review' => true,
            ]);

            $this->assertEquals(200, $response->status(), "Line $lineKey failed with status " . $response->status() . ": " . $response->getContent());
            $data = $response->json();
            $this->assertTrue($data['success'], "Line $lineKey returned success=false: " . json_encode($data));

            // Also test submit using slug/string line name (e.g., 'Line A', 'Press A')
            $responseName = $this->actingAs($user)->postJson("/operational/shift/{$lineKey}/submit", [
                'date' => $date,
                'shift' => 'Shift Pagi',
                'confirm_review' => true,
            ]);
            // Should be idempotent success
            $this->assertEquals(200, $responseName->status());
            $this->assertTrue($responseName->json('success'));
        }
    }
}
