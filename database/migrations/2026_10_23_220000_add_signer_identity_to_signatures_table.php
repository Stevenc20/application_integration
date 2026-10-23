<?php

use App\Models\LineAssignment;
use App\Models\LkhCorrection;
use App\Models\ShiftSubmission;
use App\Models\Signature;
use App\Support\SignatureScopeNormalizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('signatures', function (Blueprint $table) {
            if (! Schema::hasColumn('signatures', 'signed_by_user_id')) {
                $table->unsignedBigInteger('signed_by_user_id')->nullable()->after('shift_name')->index();
            }
            if (! Schema::hasColumn('signatures', 'signer_name')) {
                $table->string('signer_name')->nullable()->after('signed_by_user_id');
            }
            if (! Schema::hasColumn('signatures', 'signer_role')) {
                $table->string('signer_role', 100)->nullable()->after('signer_name');
            }
            if (! Schema::hasColumn('signatures', 'signed_at')) {
                $table->timestamp('signed_at')->nullable()->after('signer_role');
            }
        });

        // Deterministically backfill historical signatures where signer_name is null
        // using audit trail (ShiftSubmission, LkhCorrection, LineAssignment) without guessing.
        $this->backfillHistoricalSignatures();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('signatures', function (Blueprint $table) {
            $columns = array_filter(
                ['signed_by_user_id', 'signer_name', 'signer_role', 'signed_at'],
                fn (string $col) => Schema::hasColumn('signatures', $col)
            );
            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }

    private function backfillHistoricalSignatures(): void
    {
        if (! Schema::hasTable('signatures') || ! Schema::hasTable('users')) {
            return;
        }

        $seedNames = SignatureScopeNormalizer::seedDummyNames();
        $signatures = Signature::whereNull('signer_name')
            ->orWhereIn('signer_name', $seedNames)
            ->get();

        foreach ($signatures as $sig) {
            $resolved = SignatureScopeNormalizer::resolveHistoricalSignerIdentity(
                (string) $sig->role,
                (string) $sig->work_date,
                (string) ($sig->line_name ?? ''),
                (string) ($sig->shift_name ?? ''),
                $sig->updated_at ?? $sig->created_at
            );

            $updates = [];
            if ($sig->signed_at === null && $sig->created_at !== null) {
                $updates['signed_at'] = $sig->created_at;
            }
            if ($resolved['user_id'] !== null && ($sig->signed_by_user_id === null || in_array($sig->signer_name, $seedNames, true))) {
                $updates['signed_by_user_id'] = $resolved['user_id'];
            }
            if ($resolved['name'] !== '' && ($sig->signer_name === null || $sig->signer_name === '' || in_array($sig->signer_name, $seedNames, true))) {
                $updates['signer_name'] = $resolved['name'];
            }
            if ($resolved['role'] !== '' && ($sig->signer_role === null || $sig->signer_role === '' || in_array($sig->signer_name, $seedNames, true))) {
                $updates['signer_role'] = $resolved['role'];
            }

            if (! empty($updates)) {
                Signature::whereKey($sig->id)->update($updates);
            }
        }
    }
};
