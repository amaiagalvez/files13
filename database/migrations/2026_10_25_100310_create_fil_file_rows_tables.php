<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // The users a file is assigned to, once each.
        Schema::create('FIL_file_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained('FIL_files')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['file_id', 'user_id']);
        });

        // The phases of a file. A phase in use by a file cannot be deleted for good.
        Schema::create('FIL_file_phase', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained('FIL_files')->cascadeOnDelete();
            $table->foreignId('phase_id')->constrained('FIL_phases')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('alert_date')->nullable();
            $table->integer('accumulate_pending_days')->nullable();
            $table->longText('notes')->nullable();
            $table->timestamps();

            $table->unique(['file_id', 'phase_id']);
        });

        // The applicants of a file: any model the host registers in its morph map, once each.
        Schema::create('FIL_file_applicant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained('FIL_files')->cascadeOnDelete();
            $table->morphs('applicant');
            $table->timestamps();

            $table->unique(['file_id', 'applicant_type', 'applicant_id']);
        });

        // The values a file had before each change.
        Schema::create('FIL_file_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained('FIL_files')->cascadeOnDelete();
            $table->json('previous');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('FIL_file_changes');
        Schema::dropIfExists('FIL_file_applicant');
        Schema::dropIfExists('FIL_file_phase');
        Schema::dropIfExists('FIL_file_user');
    }
};
