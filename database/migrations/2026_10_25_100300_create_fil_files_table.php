<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('FIL_files', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('number')->nullable();
            $table->boolean('impugn')->default(false);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // A catalogue in use by a file cannot be deleted for good.
            $table->foreignId('type_id')->constrained('FIL_types')->restrictOnDelete();
            $table->foreignId('status_id')->nullable()->constrained('FIL_statuses')->restrictOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('FIL_subjects')->restrictOnDelete();
            $table->foreignId('ending_id')->nullable()->constrained('FIL_endings')->restrictOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('FIL_locations')->restrictOnDelete();
            // The district comes from locations13, which knows nothing of files: deleting it for good
            // leaves the file without a district instead of being refused.
            $table->foreignId('district_id')->nullable()->constrained('LOC_districts')->nullOnDelete();

            addCommonColumns($table);
            addAuditColumns($table);
        });

        addUniqueActiveNameIndex('FIL_files');
    }

    public function down(): void
    {
        Schema::dropIfExists('FIL_files');
    }
};
