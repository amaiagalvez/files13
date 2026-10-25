<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('FIL_location_days', function (Blueprint $table) {
            $table->id();
            // A day only exists as part of its location: deleting the location for good takes its
            // days with it. Trashing or archiving the location leaves them alone.
            $table->foreignId('location_id')->constrained('FIL_locations')->cascadeOnDelete();
            $table->date('date');
            $table->string('name');
            $table->timestamps();

            $table->unique(['location_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('FIL_location_days');
    }
};
