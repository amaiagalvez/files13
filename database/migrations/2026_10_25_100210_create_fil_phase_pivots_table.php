<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // The file types that go through a phase. A type in use by a phase cannot be deleted for
        // good; deleting the phase for good takes its rows with it.
        Schema::create('FIL_phase_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phase_id')->constrained('FIL_phases')->cascadeOnDelete();
            $table->foreignId('type_id')->constrained('FIL_types')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['phase_id', 'type_id']);
        });

        // The subjects of a phase, each with the alert that sets the deadline of the phase.
        Schema::create('FIL_phase_subject', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phase_id')->constrained('FIL_phases')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('FIL_subjects')->restrictOnDelete();
            $table->unsignedInteger('alert')->default(0);
            $table->string('alert_unit', 10)->default('days');
            $table->timestamps();

            $table->unique(['phase_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('FIL_phase_subject');
        Schema::dropIfExists('FIL_phase_type');
    }
};
