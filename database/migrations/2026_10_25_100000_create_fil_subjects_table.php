<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * The languages a subject is named in. Euskera is the language a subject always has, so its
     * column is the only one that is not nullable.
     *
     * @var list<string>
     */
    private const NAME_COLUMNS = ['name_eu', 'name_es', 'name_fr', 'name_en'];

    public function up(): void
    {
        Schema::create('FIL_subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name_eu');
            $table->string('name_es')->nullable();
            $table->string('name_fr')->nullable();
            $table->string('name_en')->nullable();

            addCommonColumns($table);
            addAuditColumns($table);
        });

        // One unique index per language: a name has to be unique inside its own language only.
        foreach (self::NAME_COLUMNS as $column) {
            addUniqueActiveColumnIndex('FIL_subjects', $column);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('FIL_subjects');
    }
};
