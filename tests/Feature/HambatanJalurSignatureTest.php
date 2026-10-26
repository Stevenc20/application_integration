<?php

namespace Tests\Feature;

use App\Models\Downtime;
use App\Models\HambatanJalur;
use App\Models\JobMaster;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HambatanJalurSignatureTest extends TestCase
{
    use RefreshDatabase;

    private function createDowntime(string $suffix): Downtime
    {
        $job = JobMaster::create([
            'job_number' => 'HJ-'.$suffix.'-'.uniqid(),
            'job_name' => 'Hambatan Job '.$suffix,
            'line' => 'Press A',
        ]);

        return Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => 'dies',
            'pic' => 'Giyono',
            'start_time' => now(),
        ]);
    }

    public function test_pic_hambatan_displays_from_record_not_signer(): void
    {
        if (! Schema::hasTable('hambatan_jalur')) {
            $this->markTestSkipped('hambatan_jalur table not migrated in test environment');
        }
        $pic = User::factory()->create(['name' => 'PIC User', 'nrp' => '12345']);
        $data = [
            'line_name' => 'Press A',
            'job_no' => 'J001',
            'nama_part' => 'PART1',
            'jenis_hambatan' => 'DT',
            'pic_hambatan' => 'Giyono',
            'status' => 'open',
            'signed_by' => $pic->id,
            'signature_image' => 'data:image/png;base64,abc',
        ];
        if (Schema::hasColumn('hambatan_jalur', 'downtime_id')) {
            $data['downtime_id'] = $this->createDowntime('pic')->id;
        }
        $hj = HambatanJalur::create($data);
        $hj->load('signer');
        $this->assertEquals('Giyono', $hj->pic_hambatan);
        $this->assertNotEquals($hj->signer->name, $hj->pic_hambatan);
    }

    public function test_signatures_persist_with_relations(): void
    {
        $leader = User::factory()->create(['name' => 'Sapto Gunawan', 'nrp' => 'L001', 'role' => 'leader a']);
        $foreman = User::factory()->create(['name' => 'Giyono', 'nrp' => 'F001', 'role' => 'foreman']);
        $pic = User::factory()->create(['name' => 'Other PIC', 'nrp' => 'P001']);

        $data2 = [
            'line_name' => 'Press A',
            'job_no' => 'J002',
            'nama_part' => 'PART2',
            'jenis_hambatan' => 'DT',
            'pic_hambatan' => 'Budi',
            'status' => 'pic_signed',
            'signed_by' => $pic->id,
            'signature_image' => 'data:image/png;base64,pic',
            'leader_signed_by' => $leader->id,
            'leader_signature_image' => 'data:image/png;base64,lead',
            'leader_signed_at' => now(),
        ];
        if (Schema::hasColumn('hambatan_jalur', 'downtime_id')) {
            $data2['downtime_id'] = $this->createDowntime('signature')->id;
        }
        $hj = HambatanJalur::create($data2);

        $hj->update([
            'status' => 'signed',
            'foreman_signed_by' => $foreman->id,
            'foreman_signature_image' => 'data:image/png;base64,fore',
            'foreman_signed_at' => now(),
        ]);

        $hj = $hj->fresh(['signer', 'leaderSigner', 'foremanSigner']);
        $this->assertEquals('Sapto Gunawan', $hj->leaderSigner->name);
        $this->assertEquals('Giyono', $hj->foremanSigner->name);
        $this->assertEquals('Budi', $hj->pic_hambatan);
        $this->assertNotNull($hj->leader_signature_image);
        $this->assertNotNull($hj->foreman_signature_image);
        $this->assertNotNull($hj->signature_image);
    }
}
