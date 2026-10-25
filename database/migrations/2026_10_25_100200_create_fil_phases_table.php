<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * The languages a phase is named in. Euskera is the language a phase always has, so its
     * column is the only one that is not nullable.
     *
     * @var list<string>
     */
    private const NAME_COLUMNS = ['name_eu', 'name_es', 'name_fr', 'name_en'];

    public function up(): void
    {
        Schema::create('FIL_phases', function (Blueprint $table) {
            $table->id();
            $table->string('name_eu');
            $table->string('name_es')->nullable();
            $table->string('name_fr')->nullable();
            $table->string('name_en')->nullable();
            // Files go through their phases in this order; the next phase of a file is the one with
            // the next higher order.
            $table->unsignedInteger('order')->default(0)->index();
            $table->string('color', 7)->nullable();
            $table->boolean('show_calendar')->default(false);
            // A phase that accumulates days counts its alert days towards a deadline; only the
            // orders 1 and 2 take part in the count.
            $table->boolean('accumulate_days')->default(false);
            $table->unsignedInteger('accumulate_days_order')->nullable();

            addCommonColumns($table);
            addAuditColumns($table);
        });

        // One unique index per language: a name has to be unique inside its own language only.
        foreach (self::NAME_COLUMNS as $column) {
            addUniqueActiveColumnIndex('FIL_phases', $column);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('FIL_phases');
    }
};
